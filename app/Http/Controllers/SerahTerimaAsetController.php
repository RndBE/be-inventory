<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\User;
use App\Models\RekapAset;
use App\Helpers\LogHelper;
use App\Helpers\TanggalHelper;
use App\Services\HrisPegawai;
use App\Models\SerahTerimaAset;
use App\Models\PeminjamanAsetDetails;
use App\Models\SerahTerimaAsetDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;

/**
 * Berita Acara Serah Terima Aset (BAST) — dokumen offboarding karyawan.
 *
 * Tanpa rantai persetujuan: dokumennya dicetak dengan kotak tanda tangan kosong
 * untuk ditandatangani basah saat serah terima. Satu-satunya tindakan yang
 * mengubah data adalah selesaikan(), yang melepas aset dari karyawan.
 */
class SerahTerimaAsetController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-serah-terima-aset', ['only' => ['index', 'downloadPdf']]);
        $this->middleware('permission:tambah-serah-terima-aset', ['only' => ['create', 'store']]);
        $this->middleware('permission:selesaikan-serah-terima-aset', ['only' => ['selesaikan']]);
    }

    public function index()
    {
        return view('pages.serah-terima-aset.index');
    }

    public function create(Request $request)
    {
        $karyawan = User::orderBy('name')->get();
        $terpilih = null;
        $aset = collect();

        if ($request->filled('karyawan_id')) {
            $terpilih = User::find($request->query('karyawan_id'));
            $aset = $terpilih ? $this->asetKaryawan($terpilih) : collect();
        }

        return view('pages.serah-terima-aset.create', compact('karyawan', 'terpilih', 'aset'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'karyawan_id' => 'required|exists:users,id',
                'alasan_keluar' => 'required|string|max:255',
                'tgl_efektif' => 'required|date',
                'keterangan' => 'nullable|string|max:255',
            ]);

            $karyawan = User::findOrFail($validated['karyawan_id']);

            // Satu karyawan cukup satu BAST. Dua dokumen bernomor berbeda untuk
            // orang yang sama akan membingungkan saat dijadikan rujukan HR.
            $bastAda = SerahTerimaAset::where('karyawan_id', $karyawan->id)->first();

            if ($bastAda) {
                return redirect()->back()
                    ->withErrors(['karyawan_id' => 'Karyawan ini sudah punya BAST ' . $bastAda->kode_bast
                        . ' berstatus ' . $bastAda->status . '.'])
                    ->withInput();
            }

            DB::beginTransaction();

            $pengaju = Auth::user();
            // Namanya dicetak di blok tanda tangan "Mengetahui". Dikunci sekarang
            // supaya mutasi jabatan di tengah proses tidak mengubah isi dokumen.
            $atasan = $karyawan->atasanLevel3 ?? $karyawan->atasanLevel2;

            $hrd = $this->pemegangRoleAktif(['hrd', 'hrd level 3']);
            $identitasHrd = $this->identitasPihakKedua($hrd);
            $terdahulu = $this->jabatanTerdahulu($karyawan);

            $bast = SerahTerimaAset::create([
                'kode_bast' => $this->generateKode(),
                'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'karyawan_id' => $karyawan->id,
                'pengaju' => $pengaju->id,
                'alasan_keluar' => $validated['alasan_keluar'],
                'tgl_efektif' => $validated['tgl_efektif'],
                'keterangan' => $validated['keterangan'] ?? null,
                'atasan_id' => $atasan?->id,
                // Format resmi menyebutnya "Jabatan/Divisi Terdahulu" — keadaan saat
                // karyawan masih bekerja. Dibekukan karena akun yang dirapikan setelah
                // dia keluar akan mengubah isi dokumen yang sudah ditandatangani.
                'jabatan_terdahulu' => $terdahulu['jabatan'],
                'divisi_terdahulu' => $terdahulu['divisi'],
                // Sama alasannya dengan atasan: nama GA dan HRD dicetak di blok
                // tanda tangan, jadi dikunci sekarang supaya cetak ulang dokumen
                // ini tidak pernah memunculkan nama pejabat pengganti.
                'ga_id' => $this->pemegangRoleAktif(['general_affair'])?->id,
                'hrd_id' => $hrd?->id,
                // Identitas lengkap PIHAK KEDUA ikut dibekukan, bukan cuma id-nya:
                // jabatan & divisi di master boleh dikoreksi tanpa mengubah dokumen
                // yang sudah terbit. Lihat identitasPihakKedua() untuk titik
                // penukaran sumbernya ke HRIS nanti.
                'hrd_nomor_id' => $identitasHrd['nomor_id'],
                'hrd_jabatan' => $identitasHrd['jabatan'],
                'hrd_divisi' => $identitasHrd['divisi'],
                'status' => 'Draft',
            ]);

            // Daftar aset dibekukan saat BAST dibuat. Kalau ditarik ulang setiap kali
            // dibuka, isi dokumen bisa berubah setelah ditandatangani.
            foreach ($this->asetKaryawan($karyawan) as $baris) {
                SerahTerimaAsetDetails::create([
                    'serah_terima_aset_id' => $bast->id,
                    'rekap_aset_id' => $baris['rekap_aset_id'],
                    'sumber' => $baris['sumber'],
                    'status_pegang' => $baris['status_pegang'],
                    'peminjaman_aset_detail_id' => $baris['peminjaman_aset_detail_id'],
                    'kondisi_serah' => $baris['kondisi'] === 'Rusak' ? 'Rusak' : 'Baik',
                    // Kolom "Tempat" di BAST. Dibekukan karena menandai BAST selesai
                    // mengosongkan ruangan aset — kalau dibaca dari relasinya, cetak
                    // ulang setelah selesai kehilangan seluruh isi kolom ini.
                    'tempat_serah' => $baris['ruangan'] !== '-' ? $baris['ruangan'] : null,
                ]);
            }

            DB::commit();

            LogHelper::success('Berhasil membuat BAST ' . $bast->kode_bast);
            return redirect()->route('serah-terima-aset.index')
                ->with('success', 'BAST ' . $bast->kode_bast . ' berhasil dibuat.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            DB::rollBack();
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menutup proses offboarding: aset dilepas dari karyawan dan akunnya
     * dinonaktifkan.
     *
     * Sengaja dipisah dari pembuatan dokumen. Saat BAST dibuat, aset fisiknya
     * belum tentu sudah diserahkan — melepasnya saat itu juga berarti sistem
     * mencatat aset sudah kembali padahal barangnya masih di tangan orang.
     */
    public function selesaikan(Request $request, int $id)
    {
        $bast = SerahTerimaAset::with('dataKaryawan')->findOrFail($id);

        if ($bast->selesai) {
            return redirect()->back()->with('error', 'BAST ' . $bast->kode_bast . ' sudah ditandai selesai sebelumnya.');
        }

        try {
            DB::transaction(function () use ($bast) {
                $bast->update([
                    'status' => 'Selesai',
                    'tgl_selesai' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'diselesaikan_oleh' => Auth::id(),
                ]);

                $this->lepaskanAset($bast);
            });
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        LogHelper::success('BAST ' . $bast->kode_bast . ' selesai: aset dilepas dan karyawan dinonaktifkan.');
        return redirect()->back()->with('success', 'BAST ' . $bast->kode_bast . ' ditandai selesai. Aset sudah dilepas dan karyawan dinonaktifkan.');
    }

    /**
     * PDF hanya terbit setelah keempat tahap menyetujui — dokumen setengah jadi
     * dengan tanda tangan sebagian justru berbahaya kalau beredar.
     */
    public function downloadPdf(int $id)
    {
        $bast = SerahTerimaAset::with([
            'serahTerimaAsetDetails.dataAset.barangAset',
            'serahTerimaAsetDetails.dataAset.dataRuangan',
            'serahTerimaAsetDetails.detailPeminjaman',
            'dataKaryawan.dataJobPosition',
            'dataAtasan',
            'dataPengaju',
            'dataGa',
            'dataHrd',
        ])->findOrFail($id);

        // Nama pejabat dicetak di bawah garis tanda tangan sebagai penunjuk siapa
        // yang harus menandatangani. Gambar tanda tangannya sengaja tidak diambil:
        // dokumen ini ditandatangani basah, dan mencetak tanda tangan digital tanpa
        // ada persetujuan berarti menerbitkan surat atas nama orang yang tidak tahu.
        //
        // Namanya dibaca dari kolom yang dibekukan saat BAST dibuat, bukan dicari
        // ulang di sini. Kalau kosong, PDF mencetak garis tanpa nama — itu lebih
        // jujur daripada menebak siapa pejabatnya sekarang.
        $pdf = Pdf::loadView('pages.serah-terima-aset.pdf', [
            'bast' => $bast,
            // PIHAK KEDUA pada format resmi adalah HRD; General Affair mengisi blok
            // "Mengetahui". Keduanya bisa menunjuk orang yang sama selama role-nya
            // masih dirangkap — dokumennya memang ditandatangani dua kali.
            //
            // Seluruhnya dibaca dari kolom snapshot pada dokumen, bukan dari relasi
            // user: itu yang membuat cetak ulang selalu sama dengan kertas yang
            // sudah ditandatangani, dan membuat pencetakan tidak bergantung pada
            // sumber data mana pun yang bisa berubah atau tak terjangkau.
            'pihakKedua' => [
                'nama' => $bast->dataHrd->name ?? null,
                'nomor_id' => $bast->hrd_nomor_id,
                'jabatan' => $bast->hrd_jabatan,
                'divisi' => $bast->hrd_divisi,
            ],
            'namaGa' => $bast->dataGa->name ?? '',
            'tglSerah' => TanggalHelper::bagianTanggal($bast->tgl_efektif),
            'tglDibuat' => TanggalHelper::bagianTanggal($bast->tgl_pengajuan),
        ])->setPaper('a4', 'portrait');

        $this->gambarNomorHalaman($pdf);

        // stream(), bukan download(): dokumennya tampil dulu di peramban supaya bisa
        // diperiksa sebelum dicetak. Tombol unduh peramban tetap tersedia di sana.
        return $pdf->stream($this->namaBerkasPdf($bast->kode_bast));
    }

    /**
     * Gambar "Hal N dari M" di dasar setiap halaman.
     *
     * Tidak lewat CSS. Dua hal yang membuat cara itu gagal di dompdf 3.0:
     * counter(pages) tidak terselesaikan sehingga tercetak "dari 0", dan elemen
     * position:fixed yang dideklarasikan setelah page-break tidak tergambar di
     * halaman-halaman sebelumnya — halaman 1 kehilangan footernya.
     *
     * page_text() menggambar langsung ke kanvas untuk semua halaman sekaligus, dan
     * placeholder {PAGE_NUM}/{PAGE_COUNT} diisi dompdf saat berkasnya ditulis.
     *
     * Wajib dipanggil SETELAH render(): jumlah halaman baru diketahui di situ, dan
     * output() tidak akan me-render ulang karena penanda rendered sudah menyala —
     * kalau dibalik urutannya, teks yang digambar di sini akan hilang.
     */
    private function gambarNomorHalaman($pdf): void
    {
        $pdf->render();

        $dompdf = $pdf->getDomPDF();
        $kanvas = $dompdf->getCanvas();
        $metrik = $dompdf->getFontMetrics();

        $font = $metrik->getFont('DejaVu Sans');
        $ukuran = 8.5;
        $teks = 'Hal {PAGE_NUM} dari {PAGE_COUNT}';

        // Lebar diukur dari teks yang angkanya sudah terisi, bukan dari
        // placeholder-nya: "{PAGE_NUM}" jauh lebih lebar daripada "1", sehingga
        // mengukurnya akan menggeser footer keluar dari tengah.
        $jumlah = $kanvas->get_page_count();
        $lebar = $metrik->getTextWidth('Hal '.$jumlah.' dari '.$jumlah, $font, $ukuran);

        $kanvas->page_text(
            ($kanvas->get_width() - $lebar) / 2,
            // Diletakkan di dalam margin bawah (20mm ≈ 57pt), jadi tidak menimpa isi.
            $kanvas->get_height() - 34,
            $teks,
            $font,
            $ukuran,
            [0.2, 0.2, 0.2]
        );
    }

    /**
     * Nomor BAST dijadikan nama berkas yang aman.
     *
     * Format resmi memakai garis miring (001/BAST/GA/ATC/VIII/2026), dan itu
     * dilarang di header Content-Disposition — Symfony menolaknya dengan
     * "The filename and the fallback cannot contain the / and \ characters".
     * Garis miringnya diganti tanda hubung, jadi berkasnya turun sebagai
     * 001-BAST-GA-ATC-VIII-2026.pdf.
     *
     * Nomor lama berformat BAST-20260803-0001 tidak punya garis miring, jadi
     * namanya tidak berubah sama sekali.
     */
    private function namaBerkasPdf(string $kode): string
    {
        $aman = str_replace(['/', '\\'], '-', $kode);

        // Berjaga-jaga kalau suatu saat nomornya memuat karakter lain yang tidak
        // sah di nama berkas. Titik dipertahankan, spasi jadi tanda hubung.
        $aman = preg_replace('/[^A-Za-z0-9._-]+/', '-', $aman);

        return trim($aman, '-') . '.pdf';
    }

    /**
     * Identitas PIHAK KEDUA untuk dibekukan di dokumen: nomor ID, jabatan, divisi.
     *
     * Dipanggil sekali saat BAST dibuat, TIDAK saat mencetak. Dua alasannya:
     * dokumen tidak boleh berubah setelah ditandatangani, dan pencetakan tidak
     * boleh gagal gara-gara sumber data sedang tak bisa dihubungi.
     *
     * Sumber utamanya HRIS, yang memang pemilik data kepegawaian — data di
     * inventory sudah terbukti melenceng (divisi tercatat "Admin", sedangkan HRIS
     * menyebut "HRD & CORPORATE SERVICE").
     *
     * Kalau HRIS tidak menjawab atau emailnya tidak terdaftar di sana, nilainya
     * jatuh ke data inventory. Cadangannya diperiksa per kolom, bukan seluruhnya
     * sekaligus: HRIS yang menjawab tapi salah satu kolomnya kosong tetap lebih
     * baik dilengkapi dari inventory daripada dibiarkan bolong di dokumen resmi.
     *
     * Kegagalan HRIS sengaja tidak menggagalkan pembuatan BAST — menahan proses
     * keluar karyawan karena sistem lain mati itu berlebihan.
     */
    private function identitasPihakKedua(?User $hrd): array
    {
        if (!$hrd) {
            return ['nomor_id' => null, 'jabatan' => null, 'divisi' => null];
        }

        $hrd->loadMissing('dataJobPosition', 'dataOrganization');
        $hris = HrisPegawai::byEmail($hrd->email);

        return [
            'nomor_id' => $hris['nomor_id'] ?? $hrd->nomor_id,
            'jabatan' => $hris['jabatan'] ?? $hrd->dataJobPosition->nama ?? null,
            'divisi' => $hris['divisi'] ?? $hrd->dataOrganization->nama ?? null,
        ];
    }

    /**
     * Jabatan & divisi PIHAK PERTAMA saat masih bekerja, untuk dibekukan di
     * dokumen sebagai "Jabatan Terdahulu" dan "Divisi Terdahulu".
     *
     * Sumber dan aturan cadangannya sama dengan identitasPihakKedua(): keduanya
     * data kepegawaian, jadi tidak masuk akal kalau satu pihak diambil dari HRIS
     * dan pihak lain dari inventory pada dokumen yang sama.
     */
    private function jabatanTerdahulu(User $karyawan): array
    {
        $karyawan->loadMissing('dataJobPosition', 'dataOrganization');
        $hris = HrisPegawai::byEmail($karyawan->email);

        return [
            'jabatan' => $hris['jabatan'] ?? $karyawan->dataJobPosition->nama ?? null,
            'divisi' => $hris['divisi'] ?? $karyawan->dataOrganization->nama ?? null,
        ];
    }

    /**
     * Pemegang role yang statusnya masih Aktif.
     *
     * Filter status inilah intinya: role approval tidak selalu dicabut saat
     * karyawan keluar, jadi mencari berdasarkan role saja bisa mengembalikan
     * mantan pejabat — dan karena id-nya lebih kecil, justru dia yang menang.
     *
     * Kalau suatu saat ada dua pemegang aktif sekaligus (masa transisi jabatan),
     * yang terambil adalah yang lebih dulu terdaftar. Nilainya dibekukan di
     * dokumen, jadi paling banyak satu BAST yang perlu dikoreksi manual.
     */
    private function pemegangRoleAktif(array $namaRole): ?User
    {
        return User::whereHas('roles', fn ($q) => $q->whereIn('name', $namaRole))
            ->where('status', 'Aktif')
            ->orderBy('id')
            ->first();
    }

    /**
     * Seluruh aset yang pernah tercatat pada seorang karyawan, dalam dua kelompok:
     *
     *   Dipegang     - tanggung jawab tetap (pic_id) dan peminjaman yang belum kembali
     *   Sudah kembali - peminjaman yang sudah dikembalikan sebelum BAST dibuat
     *
     * Keduanya masuk BAST supaya dokumennya jadi rekening lengkap, bukan cuma sisa.
     * Yang sudah kembali tercatat sebagai keterangan saja dan tidak diproses ulang
     * saat BAST tuntas.
     */
    private function asetKaryawan(User $karyawan)
    {
        $dariPic = RekapAset::with('barangAset', 'dataRuangan')
            ->where('pic_id', $karyawan->id)
            ->get()
            ->map(fn ($aset) => [
                'rekap_aset_id' => $aset->id,
                'nomor_aset' => $aset->nomor_aset,
                'nama_barang' => $aset->barangAset->nama_barang ?? '-',
                'ruangan' => $aset->dataRuangan->nama_ruangan ?? '-',
                'kondisi' => $aset->kondisi,
                'sumber' => 'PIC',
                'status_pegang' => 'Dipegang',
                'tgl_kembali' => null,
                'peminjaman_aset_detail_id' => null,
            ]);

        $dariPeminjaman = PeminjamanAsetDetails::with('dataAset.barangAset', 'dataAset.dataRuangan')
            ->whereHas('peminjamanAset', function ($query) use ($karyawan) {
                $query->where('pengaju', $karyawan->id)->bolehDikeluarkan();
            })
            ->get()
            ->map(fn ($detail) => [
                'rekap_aset_id' => $detail->rekap_aset_id,
                'nomor_aset' => $detail->dataAset->nomor_aset ?? '-',
                'nama_barang' => $detail->dataAset->barangAset->nama_barang ?? '-',
                'ruangan' => $detail->dataAset->dataRuangan->nama_ruangan ?? '-',
                'kondisi' => $detail->kondisi_kembali ?? $detail->dataAset->kondisi ?? 'Baik',
                'sumber' => 'Peminjaman',
                'status_pegang' => $detail->status_pengembalian === 'Dikembalikan' ? 'Sudah kembali' : 'Dipegang',
                'tgl_kembali' => $detail->tgl_kembali,
                'peminjaman_aset_detail_id' => $detail->id,
            ]);

        // Aset bisa muncul dari kedua sumber sekaligus — peminjaman yang disetujui
        // memang menetapkan pic_id ke peminjam. Baris peminjaman dimenangkan supaya
        // saat BAST tuntas, peminjamannya ikut ditutup, bukan cuma PIC-nya dilepas.
        $gabungan = $dariPeminjaman
            ->concat($dariPic->whereNotIn('rekap_aset_id', $dariPeminjaman->pluck('rekap_aset_id')));

        // Yang masih dipegang di atas, yang sudah kembali di bawah.
        return $gabungan
            ->sortBy(fn ($baris) => $baris['status_pegang'] === 'Dipegang' ? 0 : 1)
            ->values();
    }

    /**
     * Melepas aset dari karyawan dan menonaktifkan akunnya.
     *
     * Dipanggil dari dalam transaksi selesaikan(), jadi kegagalan di sini ikut
     * membatalkan penandaan selesai — status "Selesai" tidak boleh tersimpan
     * kalau asetnya ternyata gagal dilepas.
     */
    private function lepaskanAset(SerahTerimaAset $bast): void
    {
        $bast->loadMissing('serahTerimaAsetDetails.dataAset', 'serahTerimaAsetDetails.detailPeminjaman');

        // Hanya yang masih dipegang. Baris "Sudah kembali" ikut tercantum
        // sebagai keterangan saja — penempatannya sudah dipulihkan waktu
        // aset itu dikembalikan, jadi menyentuhnya lagi justru merusak.
        foreach ($bast->serahTerimaAsetDetails->where('status_pegang', 'Dipegang') as $detail) {
            if ($detail->dataAset) {
                // Sama seperti pengembalian biasa: aset pulang ke manajemen, jadi
                // PIC dan ruangan dikosongkan sampai ditugaskan ulang.
                $perubahan = ['pic_id' => null, 'ruangan_id' => null];

                // Kondisi aset di rekap mengikuti kondisi saat diserahkan.
                if (in_array($detail->kondisi_serah, ['Rusak', 'Hilang'], true)) {
                    $perubahan['kondisi'] = 'Rusak';
                }

                RekapAset::denganAlasan(
                    'Sudah dikembalikan ke manajemen lewat ' . $bast->kode_bast
                        . ' (offboarding ' . ($bast->dataKaryawan->name ?? '-') . ')',
                    fn () => $detail->dataAset->update($perubahan)
                );
            }

            // Peminjaman yang masih menggantung ditutup, supaya asetnya tidak
            // terus terhitung "sedang dipinjam" oleh mantan karyawan.
            if ($detail->detailPeminjaman && $detail->detailPeminjaman->status_pengembalian !== 'Dikembalikan') {
                $detail->detailPeminjaman->update([
                    'status_pengembalian' => 'Dikembalikan',
                    'tgl_kembali' => $bast->tgl_efektif,
                    'kondisi_kembali' => $detail->kondisi_serah === 'Baik' ? 'Baik' : 'Rusak',
                    'catatan_pengembalian' => 'Diserahkan lewat BAST ' . $bast->kode_bast,
                ]);

                $this->sinkronkanStatusPengembalian($detail->detailPeminjaman->peminjamanAset);
            }
        }

        $bast->dataKaryawan?->update(['status' => 'Non-Aktif']);
    }

    private function sinkronkanStatusPengembalian($peminjaman): void
    {
        if (!$peminjaman) {
            return;
        }

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
     * Nomor BAST format resmi perusahaan: 001/BAST/GA/ATC/VIII/2026.
     *
     * Urutannya dihitung per tahun dan diambil dari jumlah BAST tahun berjalan,
     * bukan dari nomor terakhir — nomor lama berformat BAST-20260801-0001 tidak
     * bisa diurai dengan pola ini, dan menebaknya justru berisiko menghasilkan
     * nomor kembar. Dokumen lama sengaja dibiarkan bernomor lama: menulis ulang
     * nomor dokumen yang sudah terbit dan dirujuk HR lebih berbahaya daripada
     * membiarkan dua format berdampingan.
     */
    /**
     * Nomor BAST berikutnya, dalam format NNN/BAST/GA/ATC/<bulan romawi>/<tahun>.
     *
     * WAJIB dipanggil dari dalam DB::transaction — lockForUpdate hanya menahan
     * baris selama transaksi berjalan.
     */
    private function generateKode(): string
    {
        $sekarang = now()->setTimezone('Asia/Jakarta');

        // Dihitung dari nomor TERTINGGI yang sudah terpakai, bukan dari count().
        // Dengan count(), sekali ada satu BAST tahun ini terhapus, count()+1
        // menghasilkan nomor yang sudah dipakai — dan karena kode_bast unik,
        // penerbitan BAST langsung gagal dan tetap gagal sampai ada yang
        // menyadari sebabnya.
        $tertinggi = SerahTerimaAset::whereYear('tgl_pengajuan', $sekarang->year)
            ->lockForUpdate()
            ->pluck('kode_bast')
            ->map(fn ($kode) => (int) explode('/', (string) $kode)[0])
            ->max();

        return str_pad((string) ((int) $tertinggi + 1), 3, '0', STR_PAD_LEFT)
            . '/BAST/GA/ATC/'
            . $this->bulanRomawi((int) $sekarang->month)
            . '/' . $sekarang->year;
    }

    private function bulanRomawi(int $bulan): string
    {
        return [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ][$bulan] ?? (string) $bulan;
    }
}
