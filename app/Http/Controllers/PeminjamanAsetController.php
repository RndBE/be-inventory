<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\User;
use App\Models\Ruangan;
use App\Models\RekapAset;
use App\Helpers\LogHelper;
use App\Models\PeminjamanAset;
use Illuminate\Http\Request;
use App\Models\PeminjamanAsetDetails;
use App\Models\PeminjamanAsetBukti;
use App\Models\ApprovalKendala;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SendWhatsAppNotification;
use Illuminate\Validation\ValidationException;

class PeminjamanAsetController extends Controller
{
    /**
     * Definisi tahap approval. Urutannya menentukan siapa yang dinotifikasi berikutnya.
     * Satu method updateApproval() melayani semua tahap supaya tidak ada duplikasi
     * seperti di alur pengajuan pembelian.
     */
    private const TAHAP = [
        'leader' => [
            'kolom_status' => 'status_leader',
            'kolom_tanggal' => 'tgl_approve_leader',
            'label' => 'Leader',
            'permission' => 'approve-leader-peminjaman-aset',
        ],
        'manager' => [
            'kolom_status' => 'status_manager',
            'kolom_tanggal' => 'tgl_approve_manager',
            'label' => 'Manager',
            'permission' => 'approve-manager-peminjaman-aset',
        ],
        'ga' => [
            'kolom_status' => 'status',
            'kolom_tanggal' => 'tgl_approve_ga',
            'label' => 'General Affair',
            'permission' => 'approve-ga-peminjaman-aset',
        ],
        // Gerbang terakhir: aset baru boleh dikeluarkan/dipindahkan setelah HRD mengetahui.
        'hrd' => [
            'kolom_status' => 'status_hrd',
            'kolom_tanggal' => 'tgl_approve_hrd',
            'label' => 'HRD',
            'permission' => 'approve-hrd-peminjaman-aset',
        ],
    ];

    public function __construct()
    {
        $this->middleware('permission:lihat-peminjaman-aset', ['only' => ['index']]);
        $this->middleware('permission:lihat-approval-peminjaman-aset', ['only' => ['approval']]);
        // Edit memakai permission yang sama dengan tambah: siapa pun yang boleh
        // mengajukan, boleh membetulkan pengajuannya sendiri. Pembatasan bahwa
        // hanya pengaju & hanya sebelum approval ada di PeminjamanAset.
        $this->middleware('permission:tambah-peminjaman-aset', ['only' => ['create', 'store', 'edit', 'update']]);
        $this->middleware('permission:pengembalian-peminjaman-aset', ['only' => ['pengembalian']]);
    }

    public function index()
    {
        return view('pages.peminjaman-aset.index');
    }

    /**
     * Layar kerja approver. Dipisah dari layar pemohon supaya tombol persetujuan
     * tidak tercampur dengan daftar pengajuan milik sendiri.
     */
    public function approval()
    {
        // Middleware di constructor hanya memeriksa permission-nya. Batas jenjang
        // diperiksa di sini karena middleware 'permission:' tidak bisa membaca
        // job_level. Tanpa baris ini, staf level 4 yang sedivisi dengan leader
        // tetap bisa membuka layarnya lewat URL walau menunya disembunyikan.
        abort_unless(PeminjamanAset::bolehBukaLayarApproval(Auth::user()), 403);

        return view('pages.peminjaman-aset.approval');
    }

    public function create()
    {
        $dataRuangan = Ruangan::orderBy('nama_ruangan')->get();

        return view('pages.peminjaman-aset.create', compact('dataRuangan'));
    }

    public function store(Request $request)
    {
        try {
            $items = json_decode($request->items, true) ?: [];

            // Tanggal kembali tidak diminta di sini. Yang mencatatnya adalah General Affair
            // saat asetnya benar-benar dikembalikan.
            $validated = $request->validate([
                'divisi' => 'required|string|max:255',
                'ruangan_id' => 'required|exists:ruangan,id',
                'keperluan' => 'required|string|max:255',
                'tgl_pinjam' => 'required|date',
            ]);

            if (empty($items)) {
                return redirect()->back()
                    ->withErrors(['items' => 'Minimal pilih satu aset yang ingin dipinjam.'])
                    ->withInput();
            }

            $asetIds = collect($items)->pluck('rekap_aset_id')->filter()->unique();
            if ($asetIds->count() !== RekapAset::whereIn('id', $asetIds)->count()) {
                return redirect()->back()
                    ->withErrors(['items' => 'Ada aset yang dipilih tidak ditemukan.'])
                    ->withInput();
            }

            DB::beginTransaction();

            $user = Auth::user();
            $tglPengajuan = now()->setTimezone('Asia/Jakarta');

            // Tahap yang tidak punya penanggung jawab otomatis dianggap disetujui,
            // mengikuti perilaku alur pengajuan pembelian.
            $statusLeader = $user->atasan_level3_id ? 'Belum disetujui' : 'Disetujui';
            $statusManager = $user->atasan_level2_id ? 'Belum disetujui' : 'Disetujui';

            $peminjaman = PeminjamanAset::create([
                'kode_peminjaman' => $this->generateKode(),
                'tgl_pengajuan' => $tglPengajuan->format('Y-m-d H:i:s'),
                'pengaju' => $user->id,
                'divisi' => $validated['divisi'],
                'ruangan_id' => $validated['ruangan_id'],
                'keperluan' => $validated['keperluan'],
                'tgl_pinjam' => $validated['tgl_pinjam'],
                'status_leader' => $statusLeader,
                'status_manager' => $statusManager,
                'status' => 'Belum disetujui',
                'status_pengembalian' => 'Belum dikembalikan',
            ]);

            foreach ($items as $item) {
                PeminjamanAsetDetails::create([
                    'peminjaman_aset_id' => $peminjaman->id,
                    'rekap_aset_id' => $item['rekap_aset_id'],
                    'jumlah' => $item['jumlah'] ?? 1,
                    'keterangan' => $item['keterangan'] ?? null,
                    'status_pengembalian' => 'Belum dikembalikan',
                ]);
            }

            DB::commit();

            $this->notifikasiApproverBerikutnya($peminjaman->fresh());

            LogHelper::success('Berhasil Menambah Pengajuan Peminjaman Aset!');
            return redirect()->route('peminjaman-aset.index')
                ->with('success', 'Pengajuan peminjaman aset berhasil dibuat dengan kode ' . $peminjaman->kode_peminjaman . '.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            DB::rollBack();
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(int $id)
    {
        $peminjaman = PeminjamanAset::with('peminjamanAsetDetails.dataAset.barangAset')->findOrFail($id);

        if (!$peminjaman->bolehDiubahOleh(Auth::user())) {
            return redirect()->route('peminjaman-aset.index')
                ->with('error', $this->alasanTidakBolehDiubah($peminjaman));
        }

        $dataRuangan = Ruangan::orderBy('nama_ruangan')->get();

        return view('pages.peminjaman-aset.edit', compact('peminjaman', 'dataRuangan'));
    }

    public function update(Request $request, int $id)
    {
        $peminjaman = PeminjamanAset::with('peminjamanAsetDetails')->findOrFail($id);

        // Dicek ulang di sini, bukan hanya di edit(). Approval bisa masuk di antara
        // saat form dibuka dan saat disimpan.
        if (!$peminjaman->bolehDiubahOleh(Auth::user())) {
            return redirect()->route('peminjaman-aset.index')
                ->with('error', $this->alasanTidakBolehDiubah($peminjaman));
        }

        try {
            $items = json_decode($request->items, true) ?: [];

            $validated = $request->validate([
                'divisi' => 'required|string|max:255',
                'ruangan_id' => 'required|exists:ruangan,id',
                'keperluan' => 'required|string|max:255',
                'tgl_pinjam' => 'required|date',
            ]);

            if (empty($items)) {
                return redirect()->back()
                    ->withErrors(['items' => 'Minimal pilih satu aset yang ingin dipinjam.'])
                    ->withInput();
            }

            $asetIds = collect($items)->pluck('rekap_aset_id')->filter()->unique();
            if ($asetIds->count() !== RekapAset::whereIn('id', $asetIds)->count()) {
                return redirect()->back()
                    ->withErrors(['items' => 'Ada aset yang dipilih tidak ditemukan.'])
                    ->withInput();
            }

            DB::beginTransaction();

            $peminjaman->update([
                'divisi' => $validated['divisi'],
                'ruangan_id' => $validated['ruangan_id'],
                'keperluan' => $validated['keperluan'],
                'tgl_pinjam' => $validated['tgl_pinjam'],
            ]);

            // Sinkronisasi penuh, bukan updateOrCreate: aset yang dibuang pengaju
            // harus benar-benar hilang dari pengajuan, kalau tidak tombol hapus di
            // keranjang tidak berefek apa pun ke database.
            $peminjaman->peminjamanAsetDetails()
                ->whereNotIn('rekap_aset_id', $asetIds)
                ->delete();

            foreach ($items as $item) {
                $peminjaman->peminjamanAsetDetails()->updateOrCreate(
                    ['rekap_aset_id' => $item['rekap_aset_id']],
                    [
                        'jumlah' => $item['jumlah'] ?? 1,
                        'keterangan' => $item['keterangan'] ?? null,
                        'status_pengembalian' => 'Belum dikembalikan',
                    ]
                );
            }

            DB::commit();

            LogHelper::success('Berhasil Mengubah Pengajuan Peminjaman Aset!');
            return redirect()->route('peminjaman-aset.index')
                ->with('success', 'Pengajuan ' . $peminjaman->kode_peminjaman . ' berhasil diperbarui.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            DB::rollBack();
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    private function alasanTidakBolehDiubah(PeminjamanAset $peminjaman): string
    {
        if ((int) $peminjaman->pengaju !== (int) Auth::id()) {
            return 'Hanya pengaju yang boleh mengubah isi pengajuannya sendiri.';
        }

        return 'Pengajuan ' . $peminjaman->kode_peminjaman . ' sudah diproses approver, jadi isinya tidak dapat diubah lagi. Silakan buat pengajuan baru.';
    }

    /**
     * Satu pintu untuk semua tahap approval: leader, manager, dan general affair.
     *
     * Urutan argumen wajib mengikuti urutan segmen di route
     * (peminjaman-aset/{id}/approval/{tahap}) — Laravel mengoper parameter
     * route yang bertipe skalar secara posisional, bukan berdasarkan nama.
     */
    public function updateApproval(Request $request, int $id, string $tahap)
    {
        if (!array_key_exists($tahap, self::TAHAP)) {
            abort(404);
        }

        $konfigurasi = self::TAHAP[$tahap];

        if (!Auth::user()->can($konfigurasi['permission'])) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:Belum disetujui,Disetujui,Ditolak',
            'catatan' => 'required_if:status,Ditolak|nullable|string|max:255',
            'kendala' => 'required_if:status,Belum disetujui|nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $peminjaman = PeminjamanAset::with('dataUser', 'peminjamanAsetDetails.dataAset.barangAset')
                ->findOrFail($id);

            // Permission di atas cuma menyatakan "boleh jadi approver". Yang ini
            // menyatakan "approver untuk pengajuan ini" — tanpa pemeriksaan ini,
            // pemegang approve-leader bisa memutus pengajuan siapa pun, lintas
            // divisi, termasuk pengajuannya sendiri. Aturannya milik model supaya
            // tombol di Blade dan penegakan di sini tidak bisa melenceng.
            if (!$peminjaman->beradaDiGarisKomando(Auth::user(), $tahap)) {
                DB::rollBack();
                abort(403);
            }

            // Keputusan yang sudah jatuh tidak boleh ditimpa. Blade menyembunyikan
            // tombolnya, tapi tanpa gerbang ini POST langsung masih bisa membalik
            // Ditolak jadi Disetujui — dan meninggalkan tahap sesudahnya dalam
            // keadaan campur aduk.
            if (!$peminjaman->tahapBelumDiputus($tahap)) {
                DB::rollBack();
                return redirect()->back()->with('error',
                    'Tahap ' . $konfigurasi['label'] . ' sudah diputus, keputusannya tidak dapat diubah lagi.');
            }

            if (!$peminjaman->tahapSebelumnyaSudahDisetujui($tahap)) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Tahap approval sebelumnya belum disetujui.');
            }

            // Ketersediaan aset dicek di dua gerbang terakhir, karena aset bisa saja
            // sudah dipinjam orang lain sejak pengajuan dibuat. Tahap HRD paling krusial:
            // di situlah aset benar-benar boleh keluar.
            if (in_array($tahap, ['ga', 'hrd']) && $validated['status'] === 'Disetujui') {
                $bentrok = $this->cariAsetBentrok($peminjaman);

                if ($bentrok->isNotEmpty()) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'Tidak dapat disetujui. ' . $bentrok->implode(' '));
                }
            }

            $peminjaman->{$konfigurasi['kolom_status']} = $validated['status'];

            // Tanggal approval hanya distempel saat benar-benar diputus.
            // Status "Belum disetujui" berarti approver hanya mencatat kendala.
            if ($validated['status'] !== 'Belum disetujui') {
                $peminjaman->{$konfigurasi['kolom_tanggal']} = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            }

            if (!empty($validated['catatan'])) {
                $peminjaman->catatan = $validated['catatan'];
            }

            // Penolakan membatalkan tahap-tahap sesudahnya.
            // Khusus HRD: keputusan GA tidak diubah — GA memang menyetujui peminjamannya,
            // HRD yang menahan asetnya keluar. Jejak keduanya perlu tetap terbaca.
            if ($validated['status'] === 'Ditolak') {
                if ($tahap === 'hrd') {
                    $peminjaman->status_hrd = 'Ditolak';
                } else {
                    $peminjaman->status_leader = $tahap === 'leader' ? 'Ditolak' : $peminjaman->status_leader;
                    $peminjaman->status_manager = in_array($tahap, ['leader', 'manager']) ? 'Ditolak' : $peminjaman->status_manager;
                    $peminjaman->status = 'Ditolak';
                    $peminjaman->status_hrd = 'Ditolak';
                }
            }

            $peminjaman->save();

            if ($peminjaman->boleh_dikeluarkan) {
                $this->sinkronkanAsetDipinjam($peminjaman);
            }

            // Kendala memakai mekanisme ApprovalKendala yang dipakai bersama modul lain.
            // Mengosongkan isinya akan menghapus catatan kendala tahap ini.
            $kendala = ApprovalKendala::saveFor(
                'peminjaman_aset',
                $peminjaman->id,
                $konfigurasi['label'],
                $validated['status'],
                $request->input('kendala'),
                Auth::id()
            );

            DB::commit();

            $peminjaman->refresh();

            if ($validated['status'] === 'Disetujui') {
                $this->notifikasiApproverBerikutnya($peminjaman);
            }

            $this->notifikasiPengaju($peminjaman, $konfigurasi['label'], $validated['status'], $kendala?->kendala);

            LogHelper::success("Approval {$konfigurasi['label']} peminjaman aset berhasil diubah.");

            $pesan = $validated['status'] === 'Belum disetujui'
                ? "Kendala {$konfigurasi['label']} berhasil dicatat dan pengaju sudah diberi tahu."
                : "Approval {$konfigurasi['label']} berhasil disimpan.";

            return redirect()->back()->with('success', $pesan);
        } catch (Throwable $e) {
            DB::rollBack();
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Mencatat pengembalian satu unit aset beserta kondisinya saat kembali.
     *
     * Method yang sama juga dipakai untuk meralat catatan yang sudah tersimpan:
     * tanggal dan kondisi sebenarnya kerap baru diketahui General Affair setelah
     * asetnya diperiksa, jadi catatan pertama tidak boleh terkunci permanen.
     */
    public function pengembalian(Request $request, int $id)
    {
        $peminjaman = PeminjamanAset::with('peminjamanAsetDetails.dataAset')->findOrFail($id);

        if (!$peminjaman->boleh_dikeluarkan) {
            return redirect()->back()->with('error', 'Aset pengajuan ini belum dikeluarkan karena persetujuan belum lengkap, jadi belum ada yang perlu dikembalikan.');
        }

        // Hanya detail milik pengajuan ini yang boleh disentuh — id dari sisi klien
        // tidak dipercaya begitu saja.
        $dipilih = $peminjaman->peminjamanAsetDetails
            ->whereIn('id', array_map('intval', (array) $request->input('detail_ids', [])))
            ->each(fn ($d) => $d->loadMissing('buktiFoto'));

        // Foto wajib kalau ada aset yang baru pertama kali dicatat. Saat meralat,
        // foto lama dipertahankan bila tidak ada unggahan baru.
        $adaPencatatanBaru = $dipilih->contains(fn ($d) => $d->status_pengembalian !== 'Dikembalikan');

        $validated = $request->validate([
            'detail_ids' => 'required|array|min:1',
            'detail_ids.*' => 'integer',
            'kondisi_kembali' => 'required|string|in:Baik,Rusak',
            'tgl_kembali' => 'required|date',
            'bukti_foto' => ($adaPencatatanBaru ? 'required' : 'nullable') . '|array|max:10',
            'bukti_foto.*' => 'image|mimes:jpeg,jpg,png,webp|max:4096',
            'catatan_pengembalian' => 'nullable|string|max:255',
        ], [
            'detail_ids.required' => 'Pilih minimal satu aset yang ingin dicatat.',
            'bukti_foto.required' => 'Bukti foto pengembalian wajib diunggah, minimal satu.',
            'bukti_foto.max' => 'Maksimal 10 foto sekali unggah.',
            'bukti_foto.*.image' => 'Bukti pengembalian harus berupa gambar.',
            'bukti_foto.*.max' => 'Ukuran tiap foto maksimal 4 MB.',
        ]);

        if ($dipilih->isEmpty()) {
            return redirect()->back()
                ->withErrors(['detail_ids' => 'Aset yang dipilih tidak ada pada pengajuan ini.'])
                ->withInput();
        }

        // Dicatat sebelum diubah, untuk membersihkan berkas yatim setelah commit.
        $fotoLama = $dipilih->flatMap(fn ($d) => $d->buktiFoto->pluck('path'))
            ->filter()->unique()->values()->all();
        $pathBaru = [];

        try {
            DB::beginTransaction();

            // Berkas disimpan sekali, lalu dirujuk oleh setiap aset yang dicentang —
            // satu foto yang memuat banyak aset tidak perlu diunggah berulang.
            //
            // Disk 'local' (storage/app), BUKAN 'public': di disk public berkasnya
            // ter-symlink ke public/storage sehingga bisa dibuka tanpa login, dan
            // nama berkas di bawah ini deterministik — id, timestamp, dan urutan
            // semuanya bisa diterka, jadi seluruh arsipnya dapat dienumerasi dari
            // luar. Sekarang hanya keluar lewat route bukti-aset.peminjaman yang
            // memeriksa hak akses per-record.
            foreach ((array) $request->file('bukti_foto', []) as $urutan => $file) {
                $namaFile = 'pengembalian_' . $peminjaman->id . '_' . time() . '_' . $urutan
                    . '.' . $file->getClientOriginalExtension();
                $pathBaru[] = $file->storeAs('bukti-pengembalian-aset', $namaFile, 'local');
            }

            foreach ($dipilih as $detail) {
                $kondisiSebelumnya = $detail->kondisi_kembali;

                $detail->update([
                    'status_pengembalian' => 'Dikembalikan',
                    'tgl_kembali' => $validated['tgl_kembali'],
                    'kondisi_kembali' => $validated['kondisi_kembali'],
                    'catatan_pengembalian' => $validated['catatan_pengembalian'] ?? null,
                ]);

                // Unggahan baru menggantikan bukti lama detail ini, bukan menambah,
                // supaya meralat catatan tidak menyisakan foto yang keliru.
                if ($pathBaru) {
                    $detail->buktiFoto()->delete();

                    foreach ($pathBaru as $path) {
                        $detail->buktiFoto()->create(['path' => $path]);
                    }
                }

                // Kondisi aset di rekap mengikuti kondisi terakhir yang dicatat GA.
                // Ralat Rusak -> Baik ikut membatalkan penandaan rusak yang terlanjur
                // dibuat, supaya salah pilih tidak menyisakan aset sehat tercatat rusak.
                if ($detail->dataAset) {
                    if ($validated['kondisi_kembali'] === 'Rusak') {
                        $detail->dataAset->update(['kondisi' => 'Rusak']);
                    } elseif ($kondisiSebelumnya === 'Rusak') {
                        $detail->dataAset->update(['kondisi' => 'Baik']);
                    }
                }

                // Aset sudah di tangan perusahaan lagi, jadi tidak boleh terus
                // tercatat atas nama peminjam maupun di ruangan tujuan peminjaman.
                $this->kembalikanPenempatanAset($detail);
            }

            $this->sinkronkanStatusPengembalian($peminjaman);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            // Berkas yang terlanjur tersimpan tidak lagi dirujuk siapa pun setelah rollback.
            foreach ($pathBaru as $path) {
                $this->hapusBerkasBukti($path);
            }

            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        // Berkas lama dibuang hanya kalau sudah tidak dirujuk baris mana pun — satu
        // foto bisa dipakai bersama beberapa aset yang dicatat dalam sekali proses.
        if ($pathBaru) {
            foreach ($fotoLama as $path) {
                if (!in_array($path, $pathBaru, true)
                    && !PeminjamanAsetBukti::where('path', $path)->exists()) {
                    $this->hapusBerkasBukti($path);
                }
            }
        }

        $jumlah = $dipilih->count();
        $pesan = "Pengembalian {$jumlah} aset berhasil dicatat.";

        LogHelper::success($pesan);
        return redirect()->route('peminjaman-aset.index')->with('success', $pesan);
    }

    /*
     * Sengaja tidak ada destroy(). Menghapus header peminjaman ikut menghapus
     * seluruh baris detailnya lewat cascade, termasuk tanggal kembali, kondisi,
     * dan bukti foto yang dicatat General Affair — sekaligus melubangi riwayat
     * peminjaman aset di Rekapitulasi Aset. Pengajuan yang salah dibiarkan
     * tercatat apa adanya; jejaknya lebih berharga daripada daftar yang rapi.
     */

    /**
     * Membuang satu berkas bukti dari disk.
     *
     * Kedua disk dicoba: unggahan baru masuk ke 'local', sedangkan berkas dari
     * sebelum perubahan ini masih di 'public' dengan path yang sama. Tanpa
     * mencoba keduanya, foto lama jadi yatim — barisnya hilang dari database
     * tapi berkasnya tetap tertinggal dan tetap terbuka lewat public/storage.
     */
    private function hapusBerkasBukti(string $path): void
    {
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    /**
     * Nomor pengajuan berikutnya.
     *
     * WAJIB dipanggil dari dalam DB::transaction — lockForUpdate hanya menahan
     * baris selama transaksi berjalan. Di luar transaksi, lock-nya lepas seketika
     * dan dua pengajuan bersamaan tetap membaca nomor yang sama; karena
     * kode_peminjaman unik, yang kedua gagal dengan error database.
     */
    private function generateKode(): string
    {
        $terakhir = PeminjamanAset::orderByDesc('id')->lockForUpdate()->first();

        // Angka diambil dari seluruh digit di ujung kode, bukan 4 karakter
        // terakhirnya. Dengan substr(-4), begitu nomornya melewati 9999 kodenya
        // jadi 5 digit dan yang terbaca adalah "0000" — penomorannya diam-diam
        // mulai lagi dari 1 dan berisiko menabrak kode yang sudah ada.
        $nomor = 1;
        if ($terakhir && preg_match('/(\d+)$/', (string) $terakhir->kode_peminjaman, $cocok)) {
            $nomor = (int) $cocok[1] + 1;
        }

        return 'PJA-' . date('Ymd') . '-' . str_pad((string) $nomor, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Aset pada pengajuan ini yang sedang dipinjam pengajuan lain yang masih berjalan.
     */
    private function cariAsetBentrok(PeminjamanAset $peminjaman)
    {
        $asetIds = $peminjaman->peminjamanAsetDetails->pluck('rekap_aset_id');

        return PeminjamanAsetDetails::with('dataAset.barangAset', 'peminjamanAset.dataUser')
            ->whereIn('rekap_aset_id', $asetIds)
            ->where('peminjaman_aset_id', '!=', $peminjaman->id)
            ->where('status_pengembalian', 'Belum dikembalikan')
            ->whereHas('peminjamanAset', function ($query) {
                $query->bolehDikeluarkan();
            })
            ->get()
            ->map(function ($detail) {
                $nama = $detail->dataAset->barangAset->nama_barang ?? 'Aset';
                $nomor = $detail->dataAset->nomor_aset ?? '-';
                $peminjam = $detail->peminjamanAset->dataUser->name ?? 'orang lain';
                $sejak = $detail->peminjamanAset->tgl_pinjam ?? '-';

                return "{$nama} ({$nomor}) sedang dipinjam {$peminjam} sejak {$sejak} dan belum dikembalikan.";
            });
    }

    private function sinkronkanStatusPengembalian(PeminjamanAset $peminjaman): void
    {
        $total = $peminjaman->peminjamanAsetDetails()->count();
        $kembali = $peminjaman->peminjamanAsetDetails()->where('status_pengembalian', 'Dikembalikan')->count();

        $peminjaman->update([
            'status_pengembalian' => match (true) {
                $kembali === 0 => 'Belum dikembalikan',
                $kembali < $total => 'Sebagian dikembalikan',
                default => 'Selesai',
            },
        ]);
    }

    /**
     * Setelah approval lengkap, aset berpindah ke peminjam dan ruangan tujuan.
     */
    private function sinkronkanAsetDipinjam(PeminjamanAset $peminjaman): void
    {
        $peminjaman->loadMissing('peminjamanAsetDetails.dataAset');

        foreach ($peminjaman->peminjamanAsetDetails as $detail) {
            if (!$detail->dataAset) {
                continue;
            }

            $perubahan = [
                'pic_id' => $peminjaman->pengaju,
            ];

            if ($peminjaman->ruangan_id) {
                $perubahan['ruangan_id'] = $peminjaman->ruangan_id;
            }

            $sudahSesuai = $detail->dataAset->pic_id == $peminjaman->pengaju
                && (!$peminjaman->ruangan_id || $detail->dataAset->ruangan_id == $peminjaman->ruangan_id);

            if (!$sudahSesuai) {
                RekapAset::denganAlasan(
                    'Dipinjam lewat ' . $peminjaman->kode_peminjaman,
                    fn () => $detail->dataAset->update($perubahan)
                );
            }
        }
    }

    /**
     * Lepaskan aset dari peminjam: PIC dan ruangan dikosongkan.
     *
     * Kolom kosong di sini berarti aset ada di tangan manajemen — belum
     * ditugaskan ke siapa pun dan belum ditempatkan di ruangan mana pun. Aset
     * tidak dikembalikan ke penempatan pemegang sebelumnya, karena secara fisik
     * memang diserahkan dulu ke General Affair sebelum dipakai lagi.
     */
    private function kembalikanPenempatanAset(PeminjamanAsetDetails $detail): void
    {
        if (!$detail->dataAset) {
            return;
        }

        $alasan = 'Sudah dikembalikan ke manajemen lewat '
            . ($detail->peminjamanAset->kode_peminjaman ?? 'peminjaman aset');

        RekapAset::denganAlasan($alasan, fn () => $detail->dataAset->update([
            'pic_id' => null,
            'ruangan_id' => null,
        ]));
    }

    /**
     * Kirim WhatsApp ke penanggung jawab tahap approval berikutnya.
     */
    private function notifikasiApproverBerikutnya(PeminjamanAset $peminjaman): void
    {
        $pengaju = $peminjaman->dataUser;

        if (!$pengaju) {
            return;
        }

        if ($peminjaman->status_leader !== 'Disetujui') {
            $target = $pengaju->atasanLevel3;
            $label = 'Leader';
        } elseif ($peminjaman->status_manager !== 'Disetujui') {
            $target = $pengaju->atasanLevel2;
            $label = 'Manager';
        } elseif ($peminjaman->status !== 'Disetujui') {
            $target = User::whereHas('roles', function ($query) {
                $query->where('name', 'general_affair');
            })->first();
            $label = 'General Affair';
        } elseif ($peminjaman->status_hrd !== 'Disetujui') {
            $target = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['hrd', 'hrd level 3']);
            })->first();
            $label = 'HRD';
        } else {
            return; // Sudah tuntas semua tahap, tidak ada approver berikutnya.
        }

        if (!$target || !$target->telephone) {
            LogHelper::error("Nomor WhatsApp {$label} tidak ditemukan untuk peminjaman {$peminjaman->kode_peminjaman}.");
            return;
        }

        $daftarAset = $peminjaman->peminjamanAsetDetails
            ->map(fn ($detail) => '- ' . ($detail->dataAset->barangAset->nama_barang ?? 'Aset') . ' (' . ($detail->dataAset->nomor_aset ?? '-') . ')')
            ->implode("\n");

        $message = "Halo {$target->name},\n\n";

        if ($label === 'HRD') {
            $message .= "Pengajuan peminjaman aset dengan kode {$peminjaman->kode_peminjaman} sudah disetujui General Affair dan menunggu Anda sebagai HRD untuk *mengetahui*.\n";
            $message .= "Aset belum dapat dikeluarkan/dipindahkan sebelum Anda menyetujui.\n\n";
        } else {
            $message .= "Pengajuan peminjaman aset dengan kode {$peminjaman->kode_peminjaman} memerlukan persetujuan Anda sebagai {$label}.\n\n";
        }

        $message .= "Pengaju: {$pengaju->name}\nDivisi: {$peminjaman->divisi}\n";
        $message .= "Ruangan Tujuan: " . ($peminjaman->dataRuangan->nama_ruangan ?? '-') . "\n";
        $message .= "Keperluan: {$peminjaman->keperluan}\n";
        $message .= "Tgl Pinjam: {$peminjaman->tgl_pinjam}\n\n";
        $message .= "Aset yang dipinjam:\n{$daftarAset}\n\n";
        $message .= "\nPesan Otomatis:\n";
        $message .= "https://inventory.beacontelemetry.com/";

        SendWhatsAppNotification::dispatch($target->telephone, $message, $target->name);
    }

    private function notifikasiPengaju(PeminjamanAset $peminjaman, string $label, string $status, ?string $kendala = null): void
    {
        $pengaju = $peminjaman->dataUser;

        if (!$pengaju || !$pengaju->telephone) {
            LogHelper::error("Nomor WhatsApp pengaju tidak ditemukan untuk peminjaman {$peminjaman->kode_peminjaman}.");
            return;
        }

        if ($status === 'Belum disetujui') {
            // Approver belum memutuskan, hanya mencatat kendala.
            $statusMessage = "masih *Menunggu Persetujuan* dari {$label}.";
        } elseif ($label === 'HRD') {
            $statusMessage = $status === 'Disetujui'
                ? "telah *diketahui HRD*. Aset sudah boleh dikeluarkan/dipindahkan."
                : "*ditahan HRD*, sehingga aset tidak dapat dikeluarkan/dipindahkan.";
        } else {
            $statusMessage = $status === 'Disetujui'
                ? "telah *Disetujui* oleh {$label}."
                : "telah *Ditolak* oleh {$label}.";
        }

        $message = "Halo {$pengaju->name},\n\n";
        $message .= "Pengajuan peminjaman aset Anda dengan kode {$peminjaman->kode_peminjaman} {$statusMessage} {$peminjaman->catatan}\n";

        if ($kendala) {
            $message .= "\nKendala dari {$label}: {$kendala}\n";
        }

        $message .= "\n";
        $message .= "\nPesan Otomatis:\n";
        $message .= "https://inventory.beacontelemetry.com/";

        SendWhatsAppNotification::dispatch($pengaju->telephone, $message, $pengaju->name);
    }
}
