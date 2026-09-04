<?php

namespace App\Http\Controllers;

use App\Exports\SuratPenunjukanWord;
use App\Helpers\LogHelper;
use App\Jobs\SendWhatsAppNotification;
use App\Models\PenunjukanPerbaikanData;
use App\Models\PerbaikanData;
use App\Models\User;
use App\Services\PerbaikanDataService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Surat penunjukan pelaksana untuk pengajuan perbaikan data.
 *
 * Alurnya tiga tangan, dan pemisahannya sengaja:
 *
 * 1. Pengaju membuat pengajuan di tab Pengajuan.
 * 2. Pemegang pintu perbaikan data menerbitkan surat penunjukan di sini,
 *    mencetaknya sebagai PDF sesuai format
 *    public/templates/SURAT PENUNJUKAN PERUBAHAN DATA.docx, lalu mengunggah
 *    kembali berkas yang sudah ditandatangani.
 * 3. Pelaksana yang tertulis di surat mengisi tanggal pelaksanaan, nama
 *    petugas, status, dan keterangannya setelah pekerjaannya dilakukan.
 *
 * Yang menerbitkan surat tidak boleh sekaligus menyatakan pekerjaannya
 * selesai — kalau boleh, surat penunjukan berhenti jadi bukti bahwa ada dua
 * pihak yang terlibat, dan tinggal jadi formalitas yang diisi satu orang.
 * Pembatasannya ada di permission yang berbeda, bukan di kesepakatan.
 *
 * Penunjukan tidak mengubah data apa pun. Perubahan datanya tetap dikerjakan
 * lewat tombol Eksekusi di halaman detail pengajuan, yang satu-satunya jalan
 * tulisnya adalah PerbaikanDataService — jadi jejaknya tetap masuk Audit
 * Perubahan Data. Surat ini yang menjelaskan siapa yang diberi wewenang
 * menekan tombol itu.
 */
class PenunjukanPerbaikanDataController extends Controller
{
    /**
     * Status pengajuan yang tidak bisa ditunjuk pelaksananya.
     *
     * Pengajuan yang ditolak atau dibatalkan tidak punya pekerjaan untuk
     * ditugaskan: menerbitkan suratnya berarti memberi wewenang melakukan
     * perubahan yang justru sudah ditolak.
     */
    private const STATUS_TERTUTUP = ['Ditolak', 'Dibatalkan'];

    /**
     * Bulan dalam angka Romawi, untuk nomor surat "008/ACC-PD/IX/2026".
     */
    private const BULAN_ROMAWI = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
    ];

    public function __construct()
    {
        $this->middleware('permission:lihat-penunjukan-perbaikan-data', [
            'only' => ['show', 'exportPdf'],
        ]);
        $this->middleware('permission:tambah-penunjukan-perbaikan-data', [
            'only' => ['create', 'store', 'opsiPengajuan'],
        ]);
        $this->middleware('permission:edit-penunjukan-perbaikan-data', [
            'only' => ['edit', 'update'],
        ]);
        $this->middleware('permission:hapus-penunjukan-perbaikan-data', [
            'only' => ['destroy'],
        ]);
    }

    /**
     * Daftar pengajuan yang masih bisa ditunjuk pelaksananya, untuk dropdown.
     *
     * Isi pengajuannya ikut dikirim — jenis, pengaju, tanggal, dan daftar
     * perubahan yang diminta — supaya form penunjukan terisi sendiri begitu
     * kodenya dipilih. Yang dikirim di sini hanya untuk dilihat: saat
     * penyimpanan, satu-satunya yang dipakai adalah perbaikan_data_id, dan
     * isinya dibaca ulang dari database.
     */
    public function opsiPengajuan(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:100',
        ]);

        $kata = trim((string) ($validated['q'] ?? ''));

        $pengajuan = PerbaikanData::with('target')
            ->whereNull('dibatalkan_pada')
            ->whereNotIn('status', self::STATUS_TERTUTUP)
            // Pengajuan yang sudah punya surat tidak muncul lagi: satu
            // pengajuan hanya boleh punya satu penunjukan, dan menampilkannya
            // hanya memancing simpan yang pasti ditolak.
            ->whereDoesntHave('penunjukan')
            ->when($kata !== '', fn ($query) => $query->where('kode_pengajuan', 'like', '%' . $kata . '%'))
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return response()->json([
            'opsi' => $pengajuan->map(fn (PerbaikanData $data) => [
                'id' => $data->id,
                'kode' => $data->kode_pengajuan,
                'label' => $data->kode_pengajuan . ' — ' . ($data->pengaju ?: 'tanpa pengaju'),
                'jenis' => $data->jenis ?: '-',
                'pengaju' => $data->pengaju ?: '-',
                'status' => $data->status,
                'tgl_pengajuan' => optional($data->tgl_pengajuan)->format('d/m/Y H:i') ?? '-',
                'perubahan' => $data->target->map(fn ($target) => [
                    'modul' => $target->labelModul(),
                    'field' => $target->labelField(),
                    'nilai_lama' => $target->nilai_lama,
                    'nilai_baru' => $target->nilai_baru,
                    'alasan' => $target->alasan,
                ])->all(),
            ])->all(),
        ]);
    }

    public function create(Request $request)
    {
        // Dua sumber untuk pengajuan yang sudah terpilih: query string, dipakai
        // kalau halaman ini dibuka dari tombol di baris pengajuan, dan old input,
        // dipakai saat form ini dikembalikan karena validasi gagal. Tanpa yang
        // kedua, kode pengajuan yang tadi dipilih hilang dari tampilan sementara
        // input tersembunyinya masih terisi — kotaknya kosong tapi datanya ada.
        $idTerpilih = (int) ($request->input('perbaikan_data_id') ?: old('perbaikan_data_id'));

        $pengajuanTerpilih = $idTerpilih > 0
            ? PerbaikanData::with('target')->find($idTerpilih)
            : null;

        // Pengajuan yang tidak bisa ditunjuk tidak diprapilih. Menampilkannya
        // hanya menuntun ke simpan yang pasti ditolak, dan pesan penolakannya
        // baru muncul setelah seluruh form diisi.
        if ($pengajuanTerpilih && $pesan = $this->alasanTidakBisaDitunjuk($pengajuanTerpilih)) {
            return redirect()->route('perbaikan-data.index', ['tab' => 'penunjukan'])
                ->with('error', $pesan);
        }

        return view('pages.penunjukan-perbaikan-data.create', [
            'penunjukan' => null,
            'isEdit' => false,
            'daftarPelaksana' => $this->daftarPelaksana(),
            'pengajuanTerpilih' => $pengajuanTerpilih,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->aturanSurat() + [
            'perbaikan_data_id' => 'required|integer|exists:perbaikan_data,id',
        ]);

        $pengajuan = PerbaikanData::findOrFail($validated['perbaikan_data_id']);

        if ($pesan = $this->alasanTidakBisaDitunjuk($pengajuan)) {
            return redirect()->back()->withInput()->with('error', $pesan);
        }

        try {
            $penunjukan = $this->simpanSuratBaru($request, $validated, $pengajuan);

            $this->kabariPelaksana($penunjukan);

            LogHelper::success(sprintf(
                'Penunjukan perbaikan data %s (%s) diterbitkan untuk pengajuan %s.',
                $penunjukan->kode_penunjukan,
                $penunjukan->nomor_surat,
                $pengajuan->kode_pengajuan
            ));

            return redirect()->route('penunjukan-perbaikan-data.show', $penunjukan->id)
                ->with('success', 'Surat penunjukan tersimpan dengan nomor ' . $penunjukan->nomor_surat
                    . '. Cetak PDF-nya, lalu unggah kembali setelah ditandatangani.');
        } catch (\Exception $e) {
            LogHelper::error('Gagal menyimpan penunjukan perbaikan data: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $penunjukan = PenunjukanPerbaikanData::with([
            'perbaikanData.target',
            'perbaikanData.lampiran',
            'pelaksana',
            'penunjuk',
            'pengisiPelaksanaan',
        ])->findOrFail($id);

        return view('pages.penunjukan-perbaikan-data.show', [
            'penunjukan' => $penunjukan,
        ]);
    }

    public function edit(int $id)
    {
        $penunjukan = PenunjukanPerbaikanData::with('perbaikanData.target')->findOrFail($id);

        return view('pages.penunjukan-perbaikan-data.create', [
            'penunjukan' => $penunjukan,
            'isEdit' => true,
            'daftarPelaksana' => $this->daftarPelaksana((int) $penunjukan->ditunjuk_user_id),
            'pengajuanTerpilih' => $penunjukan->perbaikanData,
        ]);
    }

    /**
     * Ubah isi suratnya: pelaksana, tanggal, tim pemohon, pokok perubahan,
     * catatan, dan berkas tandatangan.
     *
     * Pengajuan yang ditunjuk tidak bisa dipindah ke pengajuan lain. Surat yang
     * subjeknya berganti bukan lagi surat yang sama, dan nomor surat yang sudah
     * beredar akan merujuk permohonan yang berbeda dari yang tercetak.
     *
     * Nomor suratnya juga tidak ikut berubah walau tanggalnya digeser. Nomor
     * yang sudah terbit sudah masuk arsip Accounting; menghitungnya ulang
     * membuat dua dokumen dengan nomor berbeda untuk satu penunjukan.
     *
     * Boleh diubah pada status apa pun, termasuk setelah pelaksanaannya diisi.
     * Dulu ditutup di titik itu dengan alasan isinya sudah jadi catatan, bukan
     * instruksi. Yang terlewat: unggahan surat bertanda tangan juga lewat form
     * ini, dan kertas sering baru kembali dari meja tanda tangan setelah
     * softwarenya selesai mengerjakan — jadi jendelanya tertutup justru sebelum
     * berkas yang paling penting sempat masuk.
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate($this->aturanSurat());

        try {
            $penunjukan = PenunjukanPerbaikanData::findOrFail($id);

            $pelaksanaLama = (int) $penunjukan->ditunjuk_user_id;

            $penunjukan->ditunjuk_user_id = $validated['ditunjuk_user_id'];
            $penunjukan->tgl_penunjukan = Carbon::parse($validated['tgl_penunjukan']);
            $penunjukan->tim_pemohon = $validated['tim_pemohon'] ?? null;
            $penunjukan->perihal_perubahan = $validated['perihal_perubahan'] ?? null;
            $penunjukan->catatan_penunjukan = $validated['catatan_penunjukan'] ?? null;

            if ($request->hasFile('form_penunjukan')) {
                $this->hapusForm($penunjukan);
                $penunjukan->form_penunjukan = $this->simpanForm($request, $penunjukan->kode_penunjukan);
            }

            $penunjukan->save();

            if ($pelaksanaLama !== (int) $penunjukan->ditunjuk_user_id) {
                $this->kabariPelaksana($penunjukan);
            }

            LogHelper::success("Penunjukan perbaikan data {$penunjukan->kode_penunjukan} diperbarui.");

            return redirect()->route('penunjukan-perbaikan-data.show', $penunjukan->id)
                ->with('success', 'Surat penunjukan diperbarui.');
        } catch (\Exception $e) {
            LogHelper::error('Gagal memperbarui penunjukan perbaikan data: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Hapus surat penunjukan yang belum dijawab pelaksananya.
     *
     * Benar-benar dihapus, bukan ditandai batal seperti pengajuan. Bedanya:
     * pengajuan adalah dokumen milik pengaju yang jadi dasar audit, sedangkan
     * surat yang belum dijawab siapa pun tidak pernah menjadi dasar perubahan
     * data apa pun — tidak ada jejak yang ikut hilang. Nomor suratnya memang
     * jadi bolong di urutan; itu konsekuensi yang disengaja, karena nomor
     * dipakai ulang lebih berbahaya daripada nomor yang lompat.
     *
     * Begitu pelaksanaannya diisi, penghapusan ditolak: sejak saat itu suratnya
     * jadi dasar wewenang perubahan data yang jejaknya masih ada di Audit
     * Perubahan Data, dan menghilangkannya membuat jejak itu tidak bisa
     * dijelaskan.
     */
    public function destroy(int $id)
    {
        try {
            $penunjukan = PenunjukanPerbaikanData::findOrFail($id);

            $nomor = $penunjukan->nomorSuratCetak();

            $this->hapusForm($penunjukan);
            $penunjukan->delete();

            LogHelper::success("Penunjukan perbaikan data {$nomor} dihapus.");

            return redirect()->route('perbaikan-data.index', ['tab' => 'penunjukan'])
                ->with('success', 'Surat penunjukan ' . $nomor . ' dihapus.');
        } catch (\Exception $e) {
            LogHelper::error('Gagal menghapus penunjukan perbaikan data: ' . $e->getMessage());

            return redirect()->route('perbaikan-data.index', ['tab' => 'penunjukan'])
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Bagian pelaksanaan, diisi pelaksananya setelah pekerjaannya dilakukan.
     *
     * Tidak dipasangi middleware permission. Haknya diperiksa lewat
     * PenunjukanPerbaikanData::bolehDiisiOleh(): pelaksana yang namanya
     * tertulis di surat selalu boleh, dan itu berasal dari penunjukannya
     * sendiri, bukan dari daftar permission yang harus diberikan satu per satu
     * ke setiap orang yang mungkin ditunjuk.
     */
    public function pelaksanaan(Request $request, int $id)
    {
        $validated = $request->validate([
            'tgl_pelaksanaan' => 'required|date',
            // Daftarnya dari config, sumber yang sama dengan kotak centang di
            // PDF-nya. Status di luar daftar itu akan tercetak tanpa kotak.
            'status' => ['required', Rule::in(PenunjukanPerbaikanData::pilihanStatus())],
            'keterangan' => 'nullable|string|max:2000',
        ]);

        $penunjukan = PenunjukanPerbaikanData::with('perbaikanData')->findOrFail($id);

        if (! $penunjukan->bolehDiisiOleh(Auth::user())) {
            return redirect()->back()->with(
                'error',
                'Bagian pelaksanaan hanya bisa diisi oleh pelaksana yang ditunjuk di surat ini.'
            );
        }

        // Status yang menyatakan pekerjaannya tidak tuntas tanpa menyebut
        // sebabnya membuat suratnya berhenti tanpa jawaban: yang membacanya
        // nanti tahu pekerjaannya tidak selesai, tapi tidak tahu kenapa.
        if ($validated['status'] !== 'Selesai & Sesuai' && blank($validated['keterangan'] ?? null)) {
            return redirect()->back()->withInput()->with(
                'error',
                'Keterangan wajib diisi kalau statusnya bukan "Selesai & Sesuai" — sebabnya perlu tercatat.'
            );
        }

        try {
            $penunjukan->tgl_pelaksanaan = Carbon::parse($validated['tgl_pelaksanaan']);
            $penunjukan->status = $validated['status'];
            $penunjukan->keterangan = $validated['keterangan'] ?? null;
            $penunjukan->diisi_oleh_user_id = Auth::id();
            $penunjukan->save();

            $this->kabariPengaju($penunjukan);

            LogHelper::success(
                "Pelaksanaan penunjukan {$penunjukan->kode_penunjukan} diisi: {$penunjukan->status}."
            );

            return redirect()->route('penunjukan-perbaikan-data.show', $penunjukan->id)
                ->with('success', 'Bagian pelaksanaan tersimpan.');
        } catch (\Exception $e) {
            LogHelper::error('Gagal menyimpan pelaksanaan penunjukan: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Cetak surat penunjukan sesuai format dokumen resminya.
     *
     * Tata letaknya mengikuti public/templates/SURAT PENUNJUKAN PERUBAHAN
     * DATA.docx: kop, kepala surat bernomor, dua paragraf pembuka, tabel
     * rincian perubahan, instruksi pelaksanaan, ketentuan pengendalian, blok
     * tanda tangan, lalu halaman konfirmasi pelaksanaan.
     *
     * Kode transaksi tiap baris rincian dihitung di sini lewat
     * PerbaikanDataService, bukan disimpan di `perbaikan_data_target`: kode
     * milik record aslinya, dan menyalinnya akan membuat surat yang dicetak
     * ulang menampilkan kode lama kalau recordnya sudah dikoreksi.
     */
    /**
     * Lembar konfirmasi pelaksanaan saja, tanpa halaman instruksinya.
     *
     * Berguna setelah pelaksana menjawab: halaman instruksinya sudah
     * ditandatangani dan diarsipkan, yang tersisa dicetak, ditandatangani
     * pelaksana, lalu diarsipkan lagi. Mencetak surat lengkapnya untuk itu
     * berarti dua halaman terbuang dan dua versi halaman satu beredar — yang
     * kedua tanpa tanda tangan.
     */
    public function exportKonfirmasi(int $id, PerbaikanDataService $perbaikan)
    {
        $penunjukan = PenunjukanPerbaikanData::findOrFail($id);

        if (! $penunjukan->sudahDilaksanakan()) {
            return redirect()->back()->with(
                'error',
                'Lembar konfirmasi belum bisa diunduh: bagian pelaksanaannya belum diisi. '
                . 'Lembar kosong berisi kotak centang cuma mengundang orang mengisinya di luar sistem.'
            );
        }

        return $this->cetakWord($id, $perbaikan, true);
    }

    public function exportWord(int $id, PerbaikanDataService $perbaikan)
    {
        return $this->cetakWord($id, $perbaikan, false);
    }

    /**
     * Susun surat penunjukan dalam bentuk Word, utuh atau lembar konfirmasinya saja.
     *
     * Dua aksi memakai badan yang sama supaya rincian, kode transaksi, dan
     * penanganan record yang sudah hilang tidak ditulis dua kali lalu menyimpang
     * satu sama lain.
     */
    private function cetakWord(int $id, PerbaikanDataService $perbaikan, bool $hanyaKonfirmasi)
    {
        $penunjukan = PenunjukanPerbaikanData::with([
            'perbaikanData.target',
            'pelaksana',
            'penunjuk',
        ])->findOrFail($id);

        $target = optional($penunjukan->perbaikanData)->target ?? collect();

        $rincian = $target->map(function ($baris) use ($perbaikan) {
            // rescue(): recordnya bisa saja sudah tidak ada, dan surat yang
            // gagal dicetak seluruhnya karena satu kode tidak terbaca lebih
            // buruk daripada surat dengan satu kode kosong.
            $kode = rescue(
                fn () => $perbaikan->kodeRecord($baris->modul, (int) $baris->modul_id),
                null,
                false
            );

            return [
                'uraian' => trim($baris->labelModul() . ' — ' . $baris->labelField()),
                'kode' => $kode,
                'nilai_lama' => $baris->nilai_lama,
                'nilai_baru' => $baris->nilai_baru,
                'alasan' => $baris->alasan,
            ];
        })->values();

        $berkas = new SuratPenunjukanWord(
            $penunjukan,
            $rincian,
            // Kode transaksi yang disebut di paragraf pembuka. Unik dan dalam
            // urutan tampil, karena satu pengajuan bisa mengoreksi beberapa
            // kolom pada transaksi yang sama.
            $rincian->pluck('kode')->filter()->unique()->values(),
        );

        if ($hanyaKonfirmasi) {
            return $berkas->hanyaKonfirmasi()->unduh($penunjukan->namaBerkasKonfirmasi());
        }

        return $berkas->unduh($penunjukan->namaBerkasSurat());
    }

    /**
     * Aturan validasi yang sama untuk store dan update.
     *
     * Disatukan supaya keduanya tidak bisa berbeda diam-diam: kolom yang wajib
     * saat membuat tapi tidak diperiksa saat mengubah adalah cara kolom itu
     * jadi kosong tanpa ada yang menyadarinya.
     *
     * @return array<string, mixed>
     */
    private function aturanSurat(): array
    {
        return [
            'ditunjuk_user_id' => 'required|integer|exists:users,id',
            'tgl_penunjukan' => 'required|date',
            'tim_pemohon' => 'nullable|string|max:255',
            'perihal_perubahan' => 'nullable|string|max:1000',
            'catatan_penunjukan' => 'nullable|string|max:2000',
            // Nullable, bukan required. Berkas yang diunggah di sini adalah
            // surat yang sudah dicetak dan ditandatangani, dan cetakannya baru
            // ada setelah suratnya tersimpan. Mewajibkannya saat membuat berarti
            // meminta berkas yang belum mungkin dibuat.
            'form_penunjukan' => 'nullable|file|mimes:pdf|max:5120',
        ];
    }

    /**
     * Simpan surat baru beserta nomor resminya.
     *
     * Nomornya dihitung di dalam transaksi dan diulang kalau tabrakan: dua
     * penerbitan bersamaan bisa membaca nomor tertinggi yang sama, dan indeks
     * unique pada `nomor_surat` yang menangkapnya. Mengandalkan lock saja tidak
     * cukup — surat pertama pada satu tahun tidak punya baris untuk dikunci.
     */
    private function simpanSuratBaru(Request $request, array $validated, PerbaikanData $pengajuan): PenunjukanPerbaikanData
    {
        $tanggal = Carbon::parse($validated['tgl_penunjukan']);
        $formTersimpan = null;

        for ($percobaan = 1; ; $percobaan++) {
            try {
                return DB::transaction(function () use ($request, $validated, $pengajuan, $tanggal, &$formTersimpan) {
                    $kode = 'PN-' . now()->setTimezone('Asia/Jakarta')->format('YmdHis')
                        . '-' . strtoupper(Str::random(4));

                    // Berkasnya disimpan sekali saja walau transaksinya diulang:
                    // storage tidak ikut rollback, dan mengunggah ulang pada
                    // percobaan kedua akan meninggalkan berkas yatim.
                    $formTersimpan ??= $this->simpanForm($request, $kode);

                    // Dihitung sekali lalu dipakai dua kali: memanggilnya
                    // ulang untuk nomor_surat berarti query kedua yang bisa
                    // menjawab angka lain, dan nomor_urut jadi tidak cocok
                    // dengan nomor yang tercetak.
                    $urut = $this->nomorUrutBerikutnya($tanggal);

                    return PenunjukanPerbaikanData::create([
                        'perbaikan_data_id' => $pengajuan->id,
                        'kode_penunjukan' => $kode,
                        'nomor_urut' => $urut,
                        'tahun_surat' => (int) $tanggal->format('Y'),
                        'nomor_surat' => $this->nomorSurat($urut, $tanggal),
                        'ditunjuk_user_id' => $validated['ditunjuk_user_id'],
                        'ditunjuk_oleh_user_id' => Auth::id(),
                        'tgl_penunjukan' => $tanggal,
                        'tim_pemohon' => $validated['tim_pemohon'] ?? null,
                        'perihal_perubahan' => $validated['perihal_perubahan'] ?? null,
                        'catatan_penunjukan' => $validated['catatan_penunjukan'] ?? null,
                        'form_penunjukan' => $formTersimpan,
                        'status' => PenunjukanPerbaikanData::STATUS_AWAL,
                    ]);
                });
            } catch (QueryException $e) {
                // 23000 = pelanggaran integritas, termasuk duplicate key pada
                // `nomor_surat`. Diulang maksimal tiga kali; kalau masih gagal,
                // penyebabnya bukan tabrakan nomor dan tidak boleh disembunyikan.
                if ($percobaan >= 3 || $e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }
    }

    /**
     * Nomor urut surat berikutnya untuk tahun tanggal tersebut.
     *
     * Urutan dihitung per tahun, mengikuti nomor pada dokumen aslinya yang
     * berakhiran tahun. lockForUpdate menahan baris tahun berjalan selama
     * transaksi; indeks unique pada `nomor_surat` yang menjaga sisanya.
     */
    private function nomorUrutBerikutnya(Carbon $tanggal): int
    {
        $tahun = (int) $tanggal->format('Y');

        $maksimum = DB::table('perbaikan_data_penunjukan')
            ->where('tahun_surat', $tahun)
            ->lockForUpdate()
            ->max('nomor_urut');

        return (int) $maksimum + 1;
    }

    private function nomorSurat(int $urut, Carbon $tanggal): string
    {
        $digit = (int) config('surat_penunjukan.nomor.digit', 3);
        $kode = (string) config('surat_penunjukan.nomor.kode', 'ACC-PD');

        return sprintf(
            '%s/%s/%s/%s',
            str_pad((string) $urut, $digit, '0', STR_PAD_LEFT),
            $kode,
            self::BULAN_ROMAWI[(int) $tanggal->format('n')],
            $tanggal->format('Y')
        );
    }

    /**
     * Kandidat pelaksana: role software, ditambah pemegang izin eksekusi.
     *
     * Dua sumber karena keduanya menjawab pertanyaan berbeda. Role software
     * adalah orang yang pekerjaannya memang ini; pemegang
     * `eksekusi-perbaikan-data` adalah siapa pun yang benar-benar bisa menekan
     * tombol eksekusinya, termasuk yang diberi izin lewat halaman Role &
     * Permission tanpa memegang role software. Menunjuk orang yang tidak bisa
     * menekan tombolnya berarti surat yang tidak bisa dijalankan.
     *
     * Hanya user berstatus Aktif. Yang sudah keluar tetap memegang role dan
     * permission-nya di database — penonaktifan tidak mencabut keduanya — jadi
     * tanpa saringan ini namanya masih muncul di pilihan, dan surat dinas bisa
     * terbit menunjuk orang yang sudah tidak bekerja di sini.
     *
     * Saringannya di daftar pilihan, bukan di penyimpanan: surat lama yang
     * terlanjur menunjuk orang yang kini non-aktif tetap utuh apa adanya. Yang
     * sudah ditandatangani tidak berubah hanya karena orangnya keluar.
     */
    private function daftarPelaksana(?int $tetapIkut = null)
    {
        $idSoftware = User::role('software')->pluck('id');

        // rescue(): User::permission() melempar PermissionDoesNotExist kalau
        // migration permission-nya belum jalan di database yang dipakai. Form
        // ini masih berguna dengan daftar role software saja, jadi tidak perlu
        // ikut mati karenanya.
        $idEksekutor = rescue(
            fn () => User::permission('eksekusi-perbaikan-data')->pluck('id'),
            collect(),
            false
        );

        $kandidat = $idSoftware->merge($idEksekutor)->unique();

        return User::query()
            ->where(function ($cari) use ($kandidat, $tetapIkut) {
                $cari->where(function ($aktif) use ($kandidat) {
                    $aktif->whereIn('id', $kandidat->all())->where('status', 'Aktif');
                });

                // Pelaksana yang sekarang tertulis di surat selalu ikut, walau
                // sudah non-aktif. Kalau dia hilang dari pilihan, membuka form
                // ubah untuk mengunggah berkas saja akan memaksa surat itu
                // ditunjuk ulang ke orang lain — penunjukan berubah gara-gara
                // pekerjaan yang tidak ada hubungannya.
                if ($tetapIkut !== null) {
                    $cari->orWhere('id', $tetapIkut);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'status']);
    }

    /**
     * Kenapa pengajuan ini tidak bisa ditunjuk, atau null kalau bisa.
     */
    private function alasanTidakBisaDitunjuk(PerbaikanData $pengajuan): ?string
    {
        if ($pengajuan->dibatalkan_pada || in_array($pengajuan->status, self::STATUS_TERTUTUP, true)) {
            return 'Pengajuan ' . $pengajuan->kode_pengajuan . ' sudah ' . strtolower($pengajuan->status)
                . ', jadi tidak ada pekerjaan yang bisa ditugaskan.';
        }

        if ($pengajuan->penunjukan()->exists()) {
            return 'Pengajuan ' . $pengajuan->kode_pengajuan . ' sudah punya surat penunjukan. '
                . 'Ubah surat yang ada kalau pelaksananya perlu diganti.';
        }

        return null;
    }

    private function simpanForm(Request $request, string $kode): ?string
    {
        if (! $request->hasFile('form_penunjukan')) {
            return null;
        }

        $file = $request->file('form_penunjukan');
        $nama = $kode . '_form_penunjukan_'
            . now()->setTimezone('Asia/Jakarta')->format('YmdHis') . '.'
            . $file->getClientOriginalExtension();

        return $file->storeAs('form_penunjukan', $nama, 'public');
    }

    private function hapusForm(PenunjukanPerbaikanData $penunjukan): void
    {
        if ($penunjukan->form_penunjukan
            && Storage::disk('public')->exists($penunjukan->form_penunjukan)) {
            Storage::disk('public')->delete($penunjukan->form_penunjukan);
        }
    }

    private function kabariPelaksana(PenunjukanPerbaikanData $penunjukan): void
    {
        $pelaksana = User::find($penunjukan->ditunjuk_user_id);

        if (! $pelaksana || blank($pelaksana->telephone)) {
            return;
        }

        $kodePengajuan = optional($penunjukan->perbaikanData)->kode_pengajuan ?? '-';

        $pesan = "Halo *{$pelaksana->name}*,\n\n";
        $pesan .= "Anda *ditunjuk sebagai pelaksana* perubahan data.\n";
        $pesan .= 'Nomor surat: *' . $penunjukan->nomorSuratCetak() . "*\n";
        $pesan .= "Kode pengajuan: *{$kodePengajuan}*\n";
        $pesan .= 'Tanggal penunjukan: ' . $penunjukan->tgl_penunjukan->format('d/m/Y') . "\n\n";
        $pesan .= "Setelah dikerjakan, isi tanggal pelaksanaan, nama petugas, status, dan keterangannya di sistem:\n";
        $pesan .= 'https://inventory.beacontelemetry.com/';

        SendWhatsAppNotification::dispatch($pelaksana->telephone, $pesan, $pelaksana->name);
    }

    private function kabariPengaju(PenunjukanPerbaikanData $penunjukan): void
    {
        $pengajuan = $penunjukan->perbaikanData;

        if (! $pengajuan) {
            return;
        }

        // user_id lebih dulu; nama hanya cadangan untuk baris lama yang dibuat
        // sebelum kolom itu ada. Nama bisa berubah dan bisa kembar.
        $pengaju = $pengajuan->user_id
            ? User::find($pengajuan->user_id)
            : User::where('name', $pengajuan->pengaju)->first();

        if (! $pengaju || blank($pengaju->telephone)) {
            return;
        }

        $pesan = "Halo *{$pengaju->name}*,\n\n";
        $pesan .= "Pengajuan perbaikan data *{$pengajuan->kode_pengajuan}* sudah ditindaklanjuti.\n";
        $pesan .= "Status pelaksanaan: *{$penunjukan->status}*\n";
        $pesan .= 'Tanggal pelaksanaan: ' . $penunjukan->tgl_pelaksanaan->format('d/m/Y') . "\n";
        $pesan .= 'Petugas: ' . (optional($penunjukan->pelaksana)->name ?: '-') . "\n";
        $pesan .= $penunjukan->keterangan ? "Keterangan: {$penunjukan->keterangan}\n" : '';
        $pesan .= "\n_Pesan otomatis dari sistem Inventory_\n";
        $pesan .= 'https://inventory.beacontelemetry.com/';

        SendWhatsAppNotification::dispatch($pengaju->telephone, $pesan, $pengaju->name);
    }
}
