<?php

namespace App\Http\Controllers;

use App\Exports\TemplateRekapAsetExport;
use App\Helpers\LogHelper;
use App\Imports\RekapAsetImport;
use App\Models\BarangAset;
use App\Models\JenisBahan;
use App\Models\PengembalianManajemen;
use App\Models\RekapAset;
use App\Models\Ruangan;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class RekapAsetController extends Controller
{
    public function __construct()
    {
        // Memakai permission *-rekap-aset, bukan *-barang. Sebelumnya controller ini
        // menegakkan tambah-barang/edit-barang sementara Blade-nya menyembunyikan
        // tombol berdasarkan tambah-rekap-aset/edit-rekap-aset. Selama semua role
        // memegang kedua-duanya hal itu tidak terasa, tapi begitu ada satu role yang
        // hanya punya salah satu, hasilnya tombol tampil lalu 403 — atau lebih buruk,
        // tombol tersembunyi tapi POST-nya tetap tembus.
        //
        // *-barang dipilih untuk ditinggalkan karena itu milik BarangAsetController
        // (data master jenis barang), resource yang berbeda dari rekap aset.
        $this->middleware('permission:lihat-rekap-aset', ['only' => ['index']]);
        $this->middleware('permission:tambah-rekap-aset', ['only' => ['create', 'store', 'import']]);
        $this->middleware('permission:edit-rekap-aset', ['only' => ['update', 'edit']]);
        $this->middleware('permission:hapus-rekap-aset', ['only' => ['destroy']]);
        $this->middleware('permission:pengembalian-aset-manajemen', ['only' => ['pengembalianManajemen']]);
        // Baris export-barang dibuang: permission itu tidak ada di database DAN
        // method export()-nya juga tidak ada. Sama untuk updateMultiple/editMultiple
        // yang tadinya disebut di baris edit.
    }

    /**
     * Catat aset ber-PIC yang diserahkan kembali ke manajemen.
     *
     * Padanan "Catat Pengembalian" milik peminjaman, untuk aset yang PIC &
     * ruangannya ditetapkan lewat rekap aset sehingga tidak punya pengajuan
     * peminjaman. Sebelum ini satu-satunya cara adalah mengosongkan dua dropdown
     * di form edit — tanpa tanggal serah terima, kondisi, maupun bukti.
     *
     * Sengaja borongan per orang: karyawan biasanya menyerahkan beberapa barang
     * sekaligus, dan mengulang alur yang sama untuk tiap aset membuat tanggal
     * serta bukti fotonya rawan tidak konsisten antar aset.
     */
    public function pengembalianManajemen(Request $request)
    {
        $validated = $request->validate([
            'pic_id' => 'required|exists:users,id',
            'rekap_aset_ids' => 'required|array|min:1',
            'rekap_aset_ids.*' => 'integer',
            'tgl_kembali' => 'required|date',
            'kondisi' => 'required|string|in:Baik,Rusak',
            'bukti_foto' => 'required|array|max:10',
            'bukti_foto.*' => 'image|mimes:jpeg,jpg,png,webp|max:4096',
            'catatan' => 'nullable|string|max:255',
        ], [
            'rekap_aset_ids.required' => 'Pilih minimal satu aset yang diserahkan.',
            'bukti_foto.required' => 'Bukti foto serah terima wajib diunggah, minimal satu.',
            'bukti_foto.max' => 'Maksimal 10 foto sekali unggah.',
            'bukti_foto.*.image' => 'Bukti serah terima harus berupa gambar.',
            'bukti_foto.*.max' => 'Ukuran tiap foto maksimal 4 MB.',
        ]);

        // Hanya aset yang benar-benar dipegang PIC tersebut. Id dari sisi klien
        // tidak dipercaya — tanpa penyaringan ini, aset milik orang lain bisa
        // ikut dilepas dengan menyunting nilai checkbox.
        $aset = RekapAset::where('pic_id', $validated['pic_id'])
            ->whereIn('id', array_map('intval', $validated['rekap_aset_ids']))
            ->get();

        if ($aset->isEmpty()) {
            return redirect()->back()
                ->withErrors(['rekap_aset_ids' => 'Aset yang dipilih tidak sedang dipegang PIC tersebut.'])
                ->withInput();
        }

        $pathBaru = [];

        try {
            DB::beginTransaction();

            // Berkas disimpan sekali lalu dirujuk oleh satu pencatatan — satu foto
            // yang memuat beberapa aset tidak perlu diunggah berulang.
            // Disk 'local' (storage/app), BUKAN 'public': di disk public berkasnya
            // ter-symlink ke public/storage sehingga bisa dibuka tanpa login, dan
            // nama berkas di bawah ini deterministik — pic_id, timestamp, dan
            // urutan semuanya bisa diterka. Sekarang hanya keluar lewat route
            // bukti-aset.manajemen yang memeriksa hak akses.
            foreach ((array) $request->file('bukti_foto', []) as $urutan => $file) {
                $namaFile = 'pengembalian_manajemen_'.$validated['pic_id'].'_'.time().'_'.$urutan
                    .'.'.$file->getClientOriginalExtension();
                $pathBaru[] = $file->storeAs('bukti-pengembalian-manajemen', $namaFile, 'local');
            }

            $pengembalian = PengembalianManajemen::create([
                'tgl_kembali' => $validated['tgl_kembali'],
                'kondisi' => $validated['kondisi'],
                'catatan' => $validated['catatan'] ?? null,
                'pic_sebelum_id' => $validated['pic_id'],
                'dicatat_oleh' => Auth::id(),
            ]);

            foreach ($pathBaru as $path) {
                $pengembalian->buktiFoto()->create(['path' => $path]);
            }

            $alasan = 'Dikembalikan ke manajemen pada '
                .Carbon::parse($validated['tgl_kembali'])->format('d/m/Y')
                .' — kondisi '.$validated['kondisi']
                .($validated['catatan'] ?? null ? '. '.$validated['catatan'] : '');

            foreach ($aset as $satuAset) {
                // Konteks lengkap: alasan, tanggal serah terima sebenarnya, dan
                // rujukan ke pencatatannya supaya bukti foto bisa ditelusuri dari
                // baris riwayat mana pun.
                RekapAset::denganKonteks([
                    'keterangan' => $alasan,
                    'tgl_kejadian' => $validated['tgl_kembali'],
                    'pengembalian_manajemen_id' => $pengembalian->id,
                ], fn () => $satuAset->update([
                    'pic_id' => null,
                    'ruangan_id' => null,
                    // Kondisi mengikuti hasil pemeriksaan GA saat barangnya
                    // diterima — itu pengamatan paling baru atas aset ini.
                    'kondisi' => $validated['kondisi'],
                ]));
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            // Berkas sudah tersimpan di disk sebelum transaksi gagal, dan disk
            // tidak ikut ter-rollback. Tanpa ini setiap kegagalan menyisakan foto
            // yatim yang tidak dirujuk baris mana pun.
            // Kedua disk dicoba: unggahan baru di 'local', berkas dari sebelum
            // perubahan ini masih di 'public' dengan path yang sama.
            foreach ($pathBaru as $path) {
                foreach (['local', 'public'] as $disk) {
                    if (Storage::disk($disk)->exists($path)) {
                        Storage::disk($disk)->delete($path);
                    }
                }
            }

            LogHelper::error($e->getMessage());

            return redirect()->back()->with('error', 'Terjadi kesalahan: '.$e->getMessage())->withInput();
        }

        LogHelper::success('Mencatat pengembalian '.$aset->count().' aset ke manajemen');

        return redirect()->route('rekap-aset.index')
            ->with('success', $aset->count().' aset dicatat kembali ke manajemen.');
    }

    public function index()
    {
        $rekapgAsets = RekapAset::with('jenisBahan', 'dataUnit')->get();

        return view('pages.rekap_aset.index', compact('rekapgAsets'));
    }

    public function create()
    {
        $units = Unit::all();
        $suppliers = Supplier::all();
        $jenisBahan = JenisBahan::all();
        $barangAset = BarangAset::all();
        $dataUser = User::all();
        $dataRuangan = Ruangan::orderBy('nama_ruangan')->get();

        return view('pages.rekap_aset.create', compact('units', 'suppliers', 'jenisBahan', 'barangAset', 'dataUser', 'dataRuangan'));
    }

    /**
     * Unduh template Excel untuk import, supaya nama kolomnya tidak ditebak-tebak.
     */
    public function templateImport()
    {
        return Excel::download(new TemplateRekapAsetExport, 'template-import-rekap-aset.xlsx');
    }

    /**
     * Import rekap aset dari Excel, satu langkah.
     *
     * Tidak ada layar pemetaan jabatan: kolom "PIC" pada worksheet opname berisi
     * jabatan, dan jabatan sengaja tidak diterjemahkan ke orang. Penanggung jawab
     * & pemegang hanya terisi kalau nilainya cocok persis dengan nama user; yang
     * tidak cocok dibiarkan kosong dan dilaporkan lewat ringkasan.
     *
     * Seluruh berkas diproses dalam satu transaksi, jadi satu baris yang bermasalah
     * tidak meninggalkan setengah data terimpor.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ], [
            'file.required' => 'File wajib dipilih.',
            'file.mimes' => 'File harus berformat xlsx, xls, atau csv.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $import = new RekapAsetImport;

        try {
            $berkas = $request->file('file')->getRealPath();

            if ($import->bacaFile($berkas) === []) {
                throw new \RuntimeException('File tidak mempunyai baris data aset yang dapat diimpor.');
            }

            DB::transaction(fn () => $import->prosesFile($berkas));
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());

            return redirect()->route('rekap-aset.index')->with('error', $e->getMessage());
        }

        // Layar cukup angkanya. Rinciannya — entri katalog yang dibuat otomatis,
        // terjemahan jabatan jadi orang, kolom yang gagal terisi — masuk log
        // aktivitas: tidak memenuhi layar, tapi tetap bisa ditelusuri.
        LogHelper::success(trim('Berhasil Import Rekap Aset! '.$import->ringkasan().' '.$import->catatan()));

        return redirect()->route('rekap-aset.index')
            ->with('success', 'Data Rekap Aset berhasil diimport. '.$import->ringkasan());
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nomor_aset' => 'required|string|max:255|unique:rekap_aset,nomor_aset',
                'serial_number' => 'nullable|string|max:255',
                'merek' => 'nullable|string|max:255',
                'barang_aset_id' => 'required|exists:barang_aset,id',
                'link_gambar' => 'nullable|string',
                'tgl_perolehan' => 'nullable',
                // Boleh dikosongkan (jadi 1), tapi kalau diisi harus angka >= 1 —
                // aset dengan 0 unit tidak bermakna, dan itu lebih baik ditolak
                // terang-terangan daripada diam-diam diubah.
                // in:1, bukan min:1. Satu baris rekap aset = satu unit bernomor
                // sendiri, dan nilai ini ikut dicetak sebagai kolom jumlah di BAST.
                // Form-nya sudah dikunci, tapi tanpa batas ini POST buatan tangan
                // masih bisa membuat dokumen menyatakan jumlah yang tidak sesuai
                // dengan yang dihitung sistem.
                'jumlah_aset' => 'nullable|integer|in:1',
                'harga_perolehan' => 'nullable',
                'kondisi' => 'nullable',
                'keterangan' => 'nullable',
                'user_id' => 'required|exists:users,id',
                'pic_id' => 'nullable|exists:users,id',
                'ruangan_id' => 'nullable|exists:ruangan,id',
            ]);

            // Satu baris rekap aset mewakili satu unit bernomor sendiri, jadi
            // kosong berarti 1. Dinormalkan di sini, bukan sekadar mengandalkan
            // default kolom: null yang dikirim eksplisit tetap menimpa default itu.
            $validated['jumlah_aset'] = $validated['jumlah_aset'] ?? 1;

            // Penetapan PIC & ruangan awal ikut tercatat di riwayat mutasi oleh
            // observer model. Alasannya diberi label di sini supaya di riwayat bisa
            // dibedakan dari perpindahan karena peminjaman atau offboarding.
            RekapAset::denganAlasan(
                'Penetapan awal saat aset didaftarkan lewat form rekap aset',
                fn () => RekapAset::create($validated)
            );
            LogHelper::success('Berhasil Menambah Rekap Aset!');

            return redirect()->route('rekap-aset.index')->with('success', 'Berhasil Menambah Rekap Aset!');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());

            return view('pages.utility.404');
        }
    }

    public function edit($id)
    {
        $units = Unit::all();
        $suppliers = Supplier::all();
        $jenisBahan = JenisBahan::all();
        $barangAset = BarangAset::all();
        $dataUser = User::all();
        $dataRuangan = Ruangan::orderBy('nama_ruangan')->get();
        $rekap_aset = RekapAset::with('jenisBahan', 'barangAset', 'dataUser', 'dataPic', 'dataRuangan')->findOrFail($id);

        return view('pages.rekap_aset.edit',
            compact('rekap_aset', 'units', 'suppliers', 'jenisBahan', 'barangAset', 'dataUser', 'dataRuangan')
        );
    }

    public function update(Request $request, $id)
    {
        try {
            $rekap_aset = RekapAset::findOrFail($id);

            $validated = $request->validate([
                'nomor_aset' => 'required|unique:rekap_aset,nomor_aset,'.$id,
                'serial_number' => 'nullable|string|max:255',
                'merek' => 'nullable|string|max:255',
                'barang_aset_id' => 'required|exists:barang_aset,id',
                'link_gambar' => 'nullable|string',
                'tgl_perolehan' => 'nullable',
                // Boleh dikosongkan (jadi 1), tapi kalau diisi harus angka >= 1 —
                // aset dengan 0 unit tidak bermakna, dan itu lebih baik ditolak
                // terang-terangan daripada diam-diam diubah.
                // in:1, bukan min:1. Satu baris rekap aset = satu unit bernomor
                // sendiri, dan nilai ini ikut dicetak sebagai kolom jumlah di BAST.
                // Form-nya sudah dikunci, tapi tanpa batas ini POST buatan tangan
                // masih bisa membuat dokumen menyatakan jumlah yang tidak sesuai
                // dengan yang dihitung sistem.
                'jumlah_aset' => 'nullable|integer|in:1',
                'harga_perolehan' => 'nullable',
                'kondisi' => 'nullable',
                'keterangan' => 'nullable',
                'user_id' => 'required|exists:users,id',
                'pic_id' => 'nullable|exists:users,id',
                'ruangan_id' => 'nullable|exists:ruangan,id',
            ]);

            // Sama seperti saat menambah: kosong berarti 1, bukan null.
            $validated['jumlah_aset'] = $validated['jumlah_aset'] ?? 1;

            RekapAset::denganAlasan(
                'Diubah manual lewat form edit rekap aset',
                fn () => $rekap_aset->update($validated)
            );
            LogHelper::success('Berhasil Mengubah Rekap Aset!');

            return redirect()->route('rekap-aset.index')->with('success', 'Berhasil Mengubah Rekap Aset!');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());

            return view('pages.utility.404');
        }
    }

    public function scan($id)
    {
        $rekap_aset = RekapAset::with(
            'barangAset',
            'dataUser.dataJobPosition',
            'dataPic.dataJobPosition',
            'dataRuangan',
            'peminjamanAktif.peminjamanAset.dataUser'
        )->findOrFail($id);

        return view('pages.rekap_aset.scan', compact('rekap_aset'));
    }

    public function label($id)
    {
        $rekap_aset = RekapAset::with('barangAset', 'dataUser.dataJobPosition')->findOrFail($id);

        return view('pages.rekap_aset.label', compact('rekap_aset'));
    }

    public function destroy($id)
    {
        try {
            $rekap_aset = RekapAset::findOrFail($id);
            $rekap_aset->delete();
            LogHelper::success('Berhasil Menghapus Rekap Aset');

            return redirect()->route('rekap-aset.index')->with('success', 'Berhasil Menghapus Rekap Aset');
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());

            return view('pages.utility.404');
        }
    }
}
