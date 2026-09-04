<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Helpers\LogHelper;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\LaporanProyek;
use App\Exceptions\PerbaikanDataDitolak;
use App\Models\ApprovalKendala;
use App\Models\AuditPerubahanData;
use App\Models\PerbaikanData;
use App\Models\PerbaikanDataTarget;
use App\Services\PerbaikanDataService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\LampiranPerbaikanData;
use App\Jobs\SendWhatsAppNotification;
use Illuminate\Support\Facades\Storage;

class PerbaikanDataController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-perbaikan-data', ['only' => ['index', 'show']]);
        // Dua permission dipisah pipe, bukan hanya 'tambah-': dropdown kode
        // transaksi sekarang dipakai form edit juga, dan pemegang
        // `edit-perbaikan-data` yang tidak memegang `tambah-` akan melihat
        // dropdown yang selalu menjawab 403 tanpa keterangan apa pun.
        $this->middleware('permission:tambah-perbaikan-data|edit-perbaikan-data', ['only' => ['opsiRecord']]);
        $this->middleware('permission:eksekusi-perbaikan-data', ['only' => ['eksekusi']]);
    }

    /**
     * Daftar kode record yang bisa dipilih pada satu modul, untuk dropdown.
     *
     * Menggantikan ketik-kode-lalu-tekan-Cari. Mengetik kode secara manual
     * menuntut pengaju menghafal formatnya, dan salah satu karakter berarti
     * pesan "tidak ditemukan" tanpa petunjuk apa yang salah — pada form yang
     * tugasnya justru memperbaiki salah ketik.
     *
     * Hasilnya selalu dibatasi: pencariannya dikerjakan server, bukan dengan
     * mengirim seluruh tabel ke browser lalu memfilternya di sana.
     */
    public function opsiRecord(Request $request, PerbaikanDataService $perbaikan)
    {
        $validated = $request->validate([
            // Salah satu harus ada. `jenis` dipakai form pengajuan: kode
            // transaksi dipilih sebelum kolomnya, jadi yang diketahui saat
            // mencari baru jenis pengajuannya. `modul` dipertahankan untuk
            // pemanggil yang sudah tahu tabelnya.
            'jenis' => 'required_without:modul|array',
            'jenis.*' => 'string|max:100',
            'modul' => 'required_without:jenis|string',
            'q' => 'nullable|string|max:100',
        ]);

        try {
            $opsi = isset($validated['modul'])
                ? $perbaikan->opsiRecord($validated['modul'], $validated['q'] ?? null)
                : $perbaikan->opsiRecordJenis($validated['jenis'], $validated['q'] ?? null);

            return response()->json(['opsi' => $opsi]);
        } catch (PerbaikanDataDitolak $e) {
            return response()->json(['pesan' => $e->getMessage()], 422);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pages.perbaikan-data.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(PerbaikanDataService $perbaikan)
    {
        return view('pages.perbaikan-data.create', $this->bekalForm($perbaikan) + [
            'perbaikanData' => null,
            'isEdit' => false,
            'targetBisaDiubah' => true,
            'barisAwal' => [],
        ]);
    }

    /**
     * Bekal yang sama dibutuhkan form tambah dan form edit.
     *
     * Disatukan supaya keduanya tidak bisa berbeda: sebelum ini edit() tidak
     * mengirim daftar modulnya sama sekali, sehingga blok "Data yang Ingin
     * Diubah" hilang dari layar tanpa ada yang menyadarinya.
     *
     * @return array<string, mixed>
     */
    private function bekalForm(PerbaikanDataService $perbaikan): array
    {
        return [
            'daftarJenis' => $perbaikan->jenisPengajuan(),
            // Penyambung checkbox Jenis Pengajuan dengan pilihan kolom: kolom
            // yang muncul disaring oleh jenis yang dicentang.
            'modulPerJenis' => $perbaikan->modulPerJenis(),
            // Modul dan kolom digabung jadi satu pilihan. Dikirim utuh sekali,
            // bukan ditembak per perubahan: seluruh daftar putihnya cuma
            // belasan baris, dan menembak server untuk itu jauh lebih mahal
            // daripada mengirimnya sekalian.
            'daftarKolom' => $perbaikan->katalogKolom(),
        ];
    }

    /**
     * Baris perubahan yang sudah tersimpan, untuk mengisi ulang repeater di edit.
     *
     * Kode transaksinya dihitung ulang lewat service, bukan disimpan di
     * `perbaikan_data_target`: kode itu milik record aslinya, dan menyalinnya ke
     * baris target akan menampilkan kode lama begitu record itu sendiri
     * dikoreksi. rescue() dipakai karena recordnya bisa saja sudah tidak ada —
     * form yang gagal dibuka seluruhnya karena satu kode tidak terbaca lebih
     * buruk daripada satu kotak kode yang kosong.
     *
     * @return array<int, array<string, mixed>>
     */
    private function barisAwal(PerbaikanData $perbaikanData, PerbaikanDataService $perbaikan): array
    {
        return $perbaikanData->target->map(function (PerbaikanDataTarget $baris) use ($perbaikan) {
            $kode = rescue(
                fn () => $perbaikan->kodeRecord($baris->modul, (int) $baris->modul_id),
                null,
                false
            );

            return [
                'modul' => $baris->modul,
                // Tabelnya ikut dikirim: form menyaring pilihan kolom per tabel,
                // bukan per modul, karena beberapa modul menunjuk baris yang
                // sama — lihat PerbaikanDataService::tabelModul().
                'tabel' => $perbaikan->tabelModul($baris->modul),
                'modul_id' => (string) $baris->modul_id,
                'field' => $baris->field,
                'nilai_lama' => $baris->nilai_lama,
                'nilai_baru' => $baris->nilai_baru,
                'alasan' => $baris->alasan,
                'label' => $kode ?: ('#' . $baris->modul_id),
            ];
        })->values()->all();
    }

    /**
     * Store a newly created resource in storage.
     */
    // Layanannya sengaja tidak dinamai $perbaikan di sini: variabel itu sudah
    // dipakai untuk model PerbaikanData yang dibuat di bawah.
    public function store(Request $request, PerbaikanDataService $layanan)
    {
        // Validasi input
        $validated = $request->validate([
            'jenis' => 'required|array',
            'jenis.*' => 'string',
            // Wajib, bukan nullable. Surat permohonan perubahan data yang
            // bertanda tangan adalah satu-satunya bagian pengajuan yang tidak
            // bisa dibuat sendiri oleh pengaju di dalam aplikasi, dan itu yang
            // membedakan koreksi resmi dari orang yang sekadar menekan tombol.
            // Formatnya tersedia lewat tombol "Download Format Surat" di
            // halaman daftar.
            'form_pengajuan' => 'required|file|mimes:pdf|max:5120', // maksimal 5 MB
            'lampiran.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
            // Baris perubahan dikirim sebagai JSON dalam satu input tersembunyi,
            // mengikuti cara modul transaksi lain di aplikasi ini mengirim
            // keranjangnya. Boleh kosong: pengajuan deskriptif yang hanya
            // melampirkan dokumen tetap bisa dibuat seperti sebelumnya.
            'perubahan' => 'nullable|string',
        ], [
            'form_pengajuan.required' => 'Form pengajuan wajib diunggah dalam bentuk PDF.',
        ]);

        try {
            $barisPerubahan = $this->bacaBarisPerubahan($request->input('perubahan'), $layanan);
        } catch (PerbaikanDataDitolak $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        // Pemeriksaan `wajib_lampiran` per kolom tidak lagi dilakukan di sini:
        // `form_pengajuan` sudah wajib untuk semua pengajuan, jadi syarat
        // "harus ada dokumen" selalu terpenuhi sebelum sampai ke titik ini.
        // Daftar putihnya tetap menyimpan penandanya karena PerbaikanDataService
        // memakainya lewat wajibLampiran() untuk jalur koreksi di luar form ini.

        try {
            DB::beginTransaction();

            // Generate kode pengajuan unik
            $kodePengajuan = 'PD-' . now()->setTimezone('Asia/Jakarta')->format('YmdHis') . '-' . strtoupper(Str::random(4));

            // Upload form pengajuan (jika ada)
            $formPath = null;
            if ($request->hasFile('form_pengajuan')) {
                $file = $request->file('form_pengajuan');
                $fileName = $kodePengajuan . '_form_' . now()->setTimezone('Asia/Jakarta')->format('YmdHis') . '.' . $file->getClientOriginalExtension();
                $formPath = $file->storeAs('form_pengajuan', $fileName, 'public');
            }

            // Simpan ke tabel perbaikan_data
            $perbaikan = PerbaikanData::create([
                'kode_pengajuan' => $kodePengajuan,
                'jenis' => implode(', ', $request->jenis),
                'pengaju' => Auth::user()->name ?? 'Unknown',
                // Kolom `pengaju` yang berisi nama dipertahankan supaya baris
                // lama dan tampilan yang sudah ada tidak berubah, tapi yang
                // dipakai sebagai acuan jejak audit adalah id ini: nama bisa
                // berubah dan bisa kembar.
                'user_id' => Auth::id(),
                'tgl_pengajuan' => Carbon::now(),
                'form_pengajuan' => $formPath,
                'status' => 'Diajukan',
            ]);

            // Baris perubahan yang akan dicatat setelah disetujui. `nilai_lama`
            // dibaca ulang dari database di sini, bukan diambil dari kiriman
            // browser: nilai itu jadi dasar pemeriksaan saat eksekusi, jadi
            // tidak boleh berasal dari sisi yang bisa diubah pengirimnya.
            foreach ($barisPerubahan as $baris) {
                PerbaikanDataTarget::create([
                    'perbaikan_data_id' => $perbaikan->id,
                    'modul' => $baris['modul'],
                    'modul_id' => $baris['modul_id'],
                    'field' => $baris['field'],
                    'nilai_lama' => $layanan->nilaiSekarang($baris['modul'], $baris['modul_id'], $baris['field']),
                    'nilai_baru' => $baris['nilai_baru'],
                    'alasan' => $baris['alasan'],
                    'status' => 'diajukan',
                ]);
            }

            // Upload dan simpan semua lampiran (jika ada)
            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $fileName = $kodePengajuan . '_lampiran_' . now()->setTimezone('Asia/Jakarta')->format('YmdHis') . '_' . $file->getClientOriginalName();
                    $lampiranPath = $file->storeAs('lampiran_perbaikan_data', $fileName, 'public');

                    LampiranPerbaikanData::create([
                        'perbaikan_data_id' => $perbaikan->id,
                        'lampiran' => $lampiranPath,
                    ]);
                }
            }

            // Kirim notifikasi ke semua user role "software" dengan job_level = 3
            $softwareUsers = User::role('software')
                ->where('job_level', 3)
                ->whereNotNull('telephone')
                ->get();

            foreach ($softwareUsers as $user) {
                $message = "Halo {$user->name},\n\n";
                $message .= "Terdapat *pengajuan perbaikan data baru* dengan kode: *{$kodePengajuan}*.\n";
                $message .= "Jenis pengajuan: " . implode(', ', $request->jenis) . "\n";
                $message .= "Diajukan oleh: *" . (Auth::user()->name ?? 'Unknown') . "*\n";
                $message .= "Status saat ini: *Diajukan*\n\n";
                $message .= "Silakan cek detail di sistem:\n";
                $message .= "https://inventory.beacontelemetry.com/";

                SendWhatsAppNotification::dispatch($user->telephone, $message, $user->name);
            }

            DB::commit();

            LogHelper::success('Pengajuan perbaikan data berhasil disimpan dan notifikasi dikirim ke user software level 3. Kode: ' . $kodePengajuan);
            return redirect()->back()->with('success', 'Pengajuan perbaikan data berhasil disimpan. Kode: ' . $kodePengajuan);
        } catch (\Exception $e) {
            DB::rollBack();
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }



    /**
     * Baca dan periksa baris perubahan dari input tersembunyi berbentuk JSON.
     *
     * Setiap baris diperiksa terhadap daftar putih di sini juga, bukan hanya saat
     * eksekusi. Menolaknya sedini mungkin berarti pengaju langsung tahu kolomnya
     * tidak bisa dikoreksi, alih-alih menunggu approval lalu gagal di ujung.
     *
     * @return array<int, array{modul: string, modul_id: int, field: string, nilai_baru: ?string}>
     *
     * @throws PerbaikanDataDitolak
     */
    private function bacaBarisPerubahan(?string $json, PerbaikanDataService $layanan): array
    {
        if (blank($json)) {
            return [];
        }

        $baris = json_decode($json, true);

        if (! is_array($baris)) {
            throw new PerbaikanDataDitolak('Daftar perubahan tidak terbaca. Muat ulang halaman dan coba lagi.');
        }

        $hasil = [];
        $dilihat = [];

        foreach ($baris as $nomor => $item) {
            $modul = (string) ($item['modul'] ?? '');
            $modulId = (int) ($item['modul_id'] ?? 0);
            $field = (string) ($item['field'] ?? '');

            if ($modul === '' || $modulId <= 0 || $field === '') {
                throw new PerbaikanDataDitolak('Baris perubahan ke-' . ($nomor + 1) . ' belum lengkap.');
            }

            // Melempar PerbaikanDataDitolak kalau kolomnya di luar daftar putih.
            $definisi = $layanan->definisiField($modul, $field);

            $alasan = trim((string) ($item['alasan'] ?? ''));

            if ($alasan === '') {
                throw new PerbaikanDataDitolak(
                    'Alasan wajib diisi untuk perubahan ' . $definisi['label']
                    . '. Tanpa alasan, jejak auditnya tidak menjelaskan apa pun.'
                );
            }

            // Dua baris atas kolom yang sama akan saling menimpa saat dicatat,
            // dan yang kedua pasti gagal karena nilai lamanya sudah berubah.
            $kunci = $modul . '#' . $modulId . '#' . $field;

            if (isset($dilihat[$kunci])) {
                throw new PerbaikanDataDitolak(
                    'Kolom ' . $layanan->definisiField($modul, $field)['label']
                    . ' diajukan dua kali untuk record yang sama.'
                );
            }

            $dilihat[$kunci] = true;

            $hasil[] = [
                'modul' => $modul,
                'modul_id' => $modulId,
                'field' => $field,
                'nilai_baru' => $item['nilai_baru'] ?? null,
                'alasan' => $alasan,
            ];
        }

        return $hasil;
    }

    /**
     * Halaman detail satu pengajuan: daftar perubahan dan tombol eksekusinya.
     */
    public function show($id)
    {
        $perbaikanData = PerbaikanData::with([
            'lampiran',
            'target',
            'approvalKendalas',
            'penunjukan.pelaksana',
        ])->findOrFail($id);

        return view('pages.perbaikan-data.show', [
            'perbaikanData' => $perbaikanData,
        ]);
    }

    /**
     * Catat seluruh baris perubahan pada satu pengajuan yang sudah disetujui.
     *
     * TIDAK mengubah data yang dikoreksi. Perubahan datanya dikerjakan tim
     * software langsung di database; yang dilakukan di sini menutup tiketnya dan
     * menulis jejaknya ke Audit Perubahan Data. Lihat PerbaikanDataService.
     *
     * Tiap baris dicatat terpisah. Satu baris yang gagal — biasanya karena
     * nilai lamanya sudah tidak cocok lagi dengan database — tidak boleh
     * membatalkan baris lain yang sah, dan sebab gagalnya perlu tersimpan per
     * baris supaya pengaju tahu mana yang harus diajukan ulang.
     */
    public function eksekusi(Request $request, $id, PerbaikanDataService $layanan)
    {
        $perbaikanData = PerbaikanData::with('target')->findOrFail($id);

        if (! $perbaikanData->bolehDieksekusi()) {
            return redirect()->back()->with(
                'error',
                'Pengajuan ini belum disetujui atau sudah dibatalkan, jadi belum bisa dicatat.'
            );
        }

        $menunggu = $perbaikanData->target->where('status', '!=', 'dicatat');

        if ($menunggu->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada baris perubahan yang perlu dicatat.');
        }

        // Approver dari barisnya sendiri, bukan dari `approval_kendalas`.
        // Tabel itu menyimpan CATATAN KENDALA dan barisnya dihapus begitu
        // kendalanya dikosongkan, jadi approver hanya tercatat kalau dia
        // kebetulan menulis catatan — dan hilang lagi kalau catatannya dihapus.
        $approverId = $perbaikanData->approver_id;

        $berhasil = 0;
        $gagal = [];

        foreach ($menunggu as $target) {
            try {
                $layanan->terapkan([
                    'perbaikan_data_id' => $perbaikanData->id,
                    'modul' => $target->modul,
                    'modul_id' => $target->modul_id,
                    'field' => $target->field,
                    'nilai_lama' => $target->nilai_lama,
                    'nilai_baru' => $target->nilai_baru,
                    // Alasan per baris lebih dulu. `catatan` di tiket dikosongkan
                    // oleh approval untuk setiap status selain 'Ditolak', jadi
                    // mengandalkannya akan membuat semua baris audit beralasan
                    // sama — dan alasan yang sama di setiap baris tidak
                    // menjelaskan apa pun.
                    'alasan' => $target->alasan
                        ?: ($perbaikanData->catatan ?: 'Perbaikan data ' . $perbaikanData->kode_pengajuan),
                    'pengaju_id' => $perbaikanData->user_id,
                    'approver_id' => $approverId,
                    'ip_address' => $request->ip(),
                ]);

                $target->update(['status' => 'dicatat', 'catatan' => null]);
                $berhasil++;
            } catch (PerbaikanDataDitolak $e) {
                $target->update(['status' => 'gagal', 'catatan' => $e->getMessage()]);
                $gagal[] = $target->labelField() . ': ' . $e->getMessage();
            } catch (\Exception $e) {
                $target->update(['status' => 'gagal', 'catatan' => $e->getMessage()]);
                $gagal[] = $target->labelField() . ': ' . $e->getMessage();
                LogHelper::error('Gagal eksekusi perbaikan data: ' . $e->getMessage());
            }
        }

        if ($berhasil > 0) {
            LogHelper::success(
                "Perbaikan data {$perbaikanData->kode_pengajuan}: {$berhasil} perubahan dicatat oleh "
                . (Auth::user()->name ?? 'Tidak diketahui') . '.'
            );
        }

        if (empty($gagal)) {
            $perbaikanData->status = 'Selesai';
            $perbaikanData->tgl_diubah = now()->setTimezone('Asia/Jakarta');
            $perbaikanData->save();

            return redirect()->route('perbaikan-data.show', $perbaikanData->id)
                ->with('success', "{$berhasil} perubahan tercatat di Audit Perubahan Data. Perubahan datanya sendiri "
                    . 'dikerjakan tim software langsung di database.');
        }

        return redirect()->route('perbaikan-data.show', $perbaikanData->id)
            ->with('error', "{$berhasil} berhasil, " . count($gagal) . ' gagal. ' . implode(' | ', $gagal));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id, PerbaikanDataService $perbaikan)
    {
        $perbaikanData = PerbaikanData::with(['lampiran', 'target'])->findOrFail($id);

        return view('pages.perbaikan-data.create', $this->bekalForm($perbaikan) + [
            'perbaikanData' => $perbaikanData,
            'isEdit' => true,
            // Barisnya selalu ditampilkan; yang dibatasi hanya boleh-tidaknya
            // diubah, lihat PerbaikanData::targetMasihBisaDiubah().
            'targetBisaDiubah' => $perbaikanData->targetMasihBisaDiubah(),
            'barisAwal' => $this->barisAwal($perbaikanData, $perbaikan),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id, PerbaikanDataService $layanan)
    {
        // Validasi input
        $validated = $request->validate([
            'jenis' => 'required|array',
            'jenis.*' => 'string',
            'form_pengajuan' => 'nullable|file|mimes:pdf|max:5120', // maksimal 5 MB
            'lampiran.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
            // Sama seperti store(): baris perubahan dikirim sebagai JSON dalam
            // satu input tersembunyi. Boleh kosong, dan kosong di sini berarti
            // "tidak ada baris perubahan" — bukan "jangan disentuh".
            'perubahan' => 'nullable|string',
        ]);

        try {
            $barisPerubahan = $this->bacaBarisPerubahan($request->input('perubahan'), $layanan);
        } catch (PerbaikanDataDitolak $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        try {
            $perbaikan = PerbaikanData::with(['lampiran', 'target'])->findOrFail($id);

            // Daftar baris perubahan hanya boleh disunting sebelum tiketnya
            // masuk tahap persetujuan. Kalau form tetap mengirimnya sesudah itu,
            // permintaannya ditolak alih-alih diabaikan diam-diam: pengaju yang
            // sudah menekan Update berhak tahu suntingannya tidak tersimpan.
            if ($request->filled('perubahan') && ! $perbaikan->targetMasihBisaDiubah()) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'Baris perubahan tidak bisa diubah lagi: tiket ini sudah masuk tahap '
                    . 'persetujuan atau sebagian barisnya sudah diterapkan. Approver menyetujui '
                    . 'daftar yang lama, jadi yang dicatat harus daftar yang sama. Ajukan '
                    . 'tiket baru untuk koreksi tambahan.'
                );
            }

            // === Update field dasar ===
            $perbaikan->jenis = implode(', ', $request->jenis);
            $perbaikan->pengaju = Auth::user()->name ?? $perbaikan->pengaju;
            $perbaikan->tgl_pengajuan = $perbaikan->tgl_pengajuan ?? now();
            $perbaikan->status = $perbaikan->status ?? 'Menunggu';

            // === Update Form Pengajuan (PDF) ===
            if ($request->hasFile('form_pengajuan')) {
                // Hapus file lama kalau ada
                if ($perbaikan->form_pengajuan && Storage::disk('public')->exists($perbaikan->form_pengajuan)) {
                    Storage::disk('public')->delete($perbaikan->form_pengajuan);
                }

                // Upload baru
                $file = $request->file('form_pengajuan');
                $fileName = $perbaikan->kode_pengajuan . '_form_' . now()->setTimezone('Asia/Jakarta')->format('YmdHis') . '.' . $file->getClientOriginalExtension();
                $formPath = $file->storeAs('form_pengajuan', $fileName, 'public');
                $perbaikan->form_pengajuan = $formPath;
            }

            $perbaikan->save();

            // === Tulis ulang baris perubahan ===
            // Dihapus lalu dibuat ulang, bukan dicocokkan satu-satu: form
            // mengirim daftar utuh tanpa id baris, dan pencocokan berdasarkan
            // urutan akan menempelkan nilai lama satu baris ke baris lain begitu
            // ada yang dihapus di tengah. Aman dilakukan karena blok ini hanya
            // jalan selama belum ada baris yang diterapkan.
            if ($perbaikan->targetMasihBisaDiubah()) {
                $perbaikan->target()->delete();

                // `nilai_lama` dibaca ulang dari database, bukan dari kiriman
                // browser: nilai itu jadi dasar pemeriksaan saat eksekusi.
                foreach ($barisPerubahan as $baris) {
                    PerbaikanDataTarget::create([
                        'perbaikan_data_id' => $perbaikan->id,
                        'modul' => $baris['modul'],
                        'modul_id' => $baris['modul_id'],
                        'field' => $baris['field'],
                        'nilai_lama' => $layanan->nilaiSekarang($baris['modul'], $baris['modul_id'], $baris['field']),
                        'nilai_baru' => $baris['nilai_baru'],
                        'alasan' => $baris['alasan'],
                        'status' => 'diajukan',
                    ]);
                }
            }

            // === Upload Lampiran Baru (kalau ada) ===
            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $fileName = $perbaikan->kode_pengajuan . '_lampiran_' . now()->setTimezone('Asia/Jakarta')->format('YmdHis') . '_' . $file->getClientOriginalName();
                    $lampiranPath = $file->storeAs('lampiran_perbaikan_data', $fileName, 'public');

                    LampiranPerbaikanData::create([
                        'perbaikan_data_id' => $perbaikan->id,
                        'lampiran' => $lampiranPath,
                    ]);
                }
            }

            $softwareUsers = User::role('software')
                ->where('job_level', 3)
                ->whereNotNull('telephone')
                ->get();

            foreach ($softwareUsers as $user) {
                $message = "Halo {$user->name},\n\n";
                $message .= "Perbaikan data dengan kode " . $perbaikan->kode_pengajuan . " telah diperbarui oleh " . Auth::user()->name . ".";
                $message .= "Silakan cek detail di sistem:\n";
                $message .= "https://inventory.beacontelemetry.com/";

                SendWhatsAppNotification::dispatch($user->telephone, $message, $user->name);
            }

            // === Logging & redirect ===
            LogHelper::success('Perbaikan data berhasil diperbarui. Kode: ' . $perbaikan->kode_pengajuan);
            return redirect()->route('perbaikan-data.index')->with('success', 'Perbaikan data berhasil diperbarui.');

        } catch (\Exception $e) {
            LogHelper::error('Gagal update perbaikan data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function updateApproval(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'catatan' => 'nullable|string',
            'kendala' => 'nullable|string|max:2000',
        ]);

        try {
            DB::beginTransaction();

            $data = PerbaikanData::with(['lampiran', 'target'])->findOrFail($id);
            $tgl_diubah = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            // 'Selesai' boleh dipilih manual, termasuk saat masih ada baris
            // perubahan yang belum dicatat.
            //
            // Larangan itu pernah ada di sini, dari masa modul ini masih ikut
            // menulis koreksinya sendiri: waktu itu "Selesai" berarti datanya
            // sudah berubah, jadi memilihnya lebih awal akan berbohong.
            //
            // Sekarang tidak lagi. Perubahan datanya dikerjakan tim software
            // langsung di database, dan hanya orang yang mengerjakannya yang
            // tahu kapan tiketnya benar-benar selesai. Sebagian koreksi juga
            // tidak punya baris terstruktur sama sekali — isinya cuma dokumen
            // lampiran — dan tiket seperti itu tidak akan pernah bisa ditutup
            // kalau penutupannya menunggu baris yang memang tidak ada.

            // Update status dan tanggal perubahan
            $data->status = $validated['status'];
            $data->tgl_diubah = $tgl_diubah;

            // Siapa yang menyetel status ini, dan kapan. Inilah approver yang
            // dirujuk baris audit — sebelumnya identitasnya tidak disimpan di
            // mana pun, dan audit terpaksa menebaknya dari tabel catatan
            // kendala.
            //
            // Dicatat untuk setiap status, bukan hanya 'Disetujui': yang menolak
            // pun mengambil keputusan yang perlu bisa ditelusuri, dan tiket yang
            // ditolak lalu diajukan ulang meninggalkan riwayat yang lebih
            // terbaca kalau penolaknya ikut tercatat.
            $data->approver_id = Auth::id();
            $data->tgl_approve = $tgl_diubah;
            $kendalaMessage = $this->saveApprovalKendala($id, 'Approval', $data->status, $request);

            // Jika ditolak, tambahkan catatan
            if ($data->status === 'Ditolak') {
                $data->catatan = $validated['catatan'] ?? '-';
            } else {
                $data->catatan = null;
            }

            $data->save();

            // Cari user berdasarkan nama pengaju
            $user = User::where('name', $data->pengaju)->first();

            if ($user && !empty($user->telephone)) {
                $targetPhone = $user->telephone;
                $recipientName = $user->name;

                // Tentukan pesan berdasarkan status
                $statusMessage = match ($data->status) {
                    'Diajukan' => "telah *Diajukan* oleh Administrator.",
                    'Disetujui' => "telah *Disetujui* oleh Administrator.",
                    'Ditolak' => "telah *Ditolak* dengan alasan: {$data->catatan}",
                    'Dalam Pemeriksaan' => "sedang *dalam pemeriksaan* oleh Administrator.",
                    'Sedang Diperbaiki' => "sedang *diperbaiki oleh Administrator*.",
                    'Selesai' => "telah *selesai diproses*.",
                    default => "memiliki status yang tidak dikenal.",
                };

                // Format pesan WA
                $message = "Halo *{$recipientName}*, \n\n";
                $message .= "Pengajuan perbaikan data Anda dengan *Kode Pengajuan* *{$data->kode_pengajuan}* {$statusMessage}\n\n";
                $message .= $kendalaMessage ? "{$kendalaMessage}\n\n" : '';
                $message .= "Tanggal update: {$tgl_diubah}\n\n";
                $message .= "_Pesan otomatis dari sistem Inventory_\n";
                $message .= "https://inventory.beacontelemetry.com/";

                // Kirim notifikasi via WhatsApp Queue
                SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
            } else {
                LogHelper::error("Tidak ditemukan nomor telepon untuk pengaju {$data->pengaju}");
            }

            DB::commit();

            LogHelper::success('Status approval berhasil diubah.');
            $page = $request->input('page', 1);
            return redirect()
                ->route('perbaikan-data.index', ['page' => $page])
                ->with('success', 'Status approval berhasil diubah.');

        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan: $errorMessage");
        }
    }

    private function saveApprovalKendala(int $id, string $role, ?string $status, Request $request): string
    {
        $note = ApprovalKendala::saveFor('perbaikan_data', $id, $role, $status, $request->input('kendala'), Auth::id());

        return $note ? "\nKendala: {$note->kendala}" : '';
    }



    /**
     * Remove the specified resource from storage.
     */
    /**
     * Batalkan tiket perbaikan data. Tidak menghapus apa pun.
     *
     * Sebelumnya method ini benar-benar menghapus baris beserta seluruh
     * lampirannya. Begitu tiket ini jadi dasar jejak audit, itu tidak boleh lagi:
     * tiket yang bisa dihapus berarti jejak yang bisa dihapus, dan pertanyaan
     * "kenapa angka ini pernah berubah" akan kehilangan jawabannya justru pada
     * kasus yang paling perlu diperiksa.
     *
     * Route-nya tetap DELETE supaya tombol dan modal yang sudah ada tidak perlu
     * dirombak; yang berubah akibatnya. Baris audit yang menunjuk tiket ini pun
     * tetap utuh karena `audit_perubahan_data.perbaikan_data_id` sengaja tidak
     * diberi foreign key bercascade.
     */
    public function destroy($id)
    {
        try {
            $perbaikanData = PerbaikanData::findOrFail($id);

            if ($perbaikanData->dibatalkan_pada) {
                return redirect()->back()->with('error', 'Pengajuan ini sudah dibatalkan sebelumnya.');
            }

            // Tiket yang koreksinya sudah diterapkan tidak boleh dibatalkan:
            // pembatalannya tidak mengembalikan data, jadi statusnya akan
            // berbohong tentang apa yang sebenarnya terjadi.
            if (AuditPerubahanData::where('perbaikan_data_id', $perbaikanData->id)->exists()) {
                return redirect()->back()->with(
                    'error',
                    'Pengajuan ini sudah dicatat ke jejak audit, jadi tidak bisa dibatalkan. '
                    . 'Ajukan koreksi baru kalau hasilnya perlu diperbaiki.'
                );
            }

            $perbaikanData->dibatalkan_pada = now()->setTimezone('Asia/Jakarta');
            $perbaikanData->status = 'Dibatalkan';
            $perbaikanData->save();

            LogHelper::success(
                "Pengajuan perbaikan data {$perbaikanData->kode_pengajuan} dibatalkan oleh "
                . (Auth::user()->name ?? 'Tidak diketahui') . '.'
            );

            return redirect()->back()->with('success', 'Pengajuan perbaikan data dibatalkan. Berkasnya tetap tersimpan sebagai arsip.');
        } catch (\Exception $e) {
            LogHelper::error('Gagal membatalkan pengajuan perbaikan data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membatalkan pengajuan.');
        }
    }
}
