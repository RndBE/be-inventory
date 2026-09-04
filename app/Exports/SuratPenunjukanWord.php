<?php

namespace App\Exports;

use App\Models\PenunjukanPerbaikanData;
use DOMDocument;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;
use ZipArchive;

/**
 * Surat Penunjukan Perubahan Data dalam bentuk Word (.docx).
 *
 * Mengisi public/templates/surat-penunjukan-penanda.docx, salinan berpenanda
 * dari SURAT PENUNJUKAN PERUBAHAN DATA.docx. Seluruh tata letaknya berasal dari
 * dokumen asli itu — kop bergambar, Times New Roman 12, tab stop, spasi 1,5,
 * gaya tabel, penomoran instruksi, dan blok tanda tangan lima kolomnya. Tidak
 * ada satu pun bagian yang disusun ulang dari kode, jadi tidak ada yang bisa
 * menyimpang darinya.
 *
 * Versi sebelumnya menyusun dokumennya dari kode dengan PhpWord. Isinya benar
 * tapi bentuknya bukan surat yang sama: jarak antar bagian, lebar kolom tanda
 * tangan, dan susunan kop harus ditebak satu per satu, dan tiap tebakan meleset
 * sedikit. Surat dinas dinilai dari bentuknya juga, bukan hanya kalimatnya.
 *
 * Templatenya dibuat sekali dengan mengganti teks LINTAS `<w:r>`. Word memecah
 * satu kalimat jadi puluhan run mengikuti riwayat suntingan — paragraf pembuka
 * dokumen ini terdiri dari 64 run — jadi pencarian teks biasa gagal untuk 7 dari
 * 11 nilai yang harus diganti, dan gagalnya diam, bukan melempar error.
 * Penggantian per-karakter itu yang membuat penanda bisa dipasang tanpa
 * menyentuh format apa pun.
 *
 * Bunyi kalimat tetapnya sekarang milik template, bukan config. Yang masih
 * dibaca dari config/surat_penunjukan.php hanya nama penanda tangan bawaan dan
 * daftar status konfirmasi. Kalau bunyi suratnya berubah, yang disunting dokumen
 * Word-nya — dan itu memang cara paling wajar bagi yang menulis surat.
 */
class SuratPenunjukanWord
{
    private const TEMPLATE = 'templates/surat-penunjukan-penanda.docx';

    private const BAGIAN_DOKUMEN = 'word/document.xml';

    /** Surat utuh: instruksi, dan halaman konfirmasi kalau sudah dilaksanakan. */
    public const CETAK_SURAT = 'surat';

    /** Lembar konfirmasi pelaksanaan saja, tanpa halaman instruksinya. */
    public const CETAK_KONFIRMASI = 'konfirmasi';

    /**
     * Kotak centang.
     *
     * Yang tercentang memakai ☑, bukan ☒. Silang di kotak pilihan lazim
     * dibaca sebagai "dicoret" atau "tidak dipilih" — kebalikan dari yang
     * dimaksud. Kotak kosongnya sama persis dengan simbol di dokumen aslinya.
     */
    private const KOTAK_KOSONG = '☐';

    private const KOTAK_ISI = '☑';

    private const GARIS_KOSONG = '..........................';

    private array $surat;

    private string $cetak = self::CETAK_SURAT;

    public function __construct(
        private PenunjukanPerbaikanData $penunjukan,
        private Collection $rincian,
        private Collection $kodeTransaksi,
    ) {
        $this->surat = (array) config('surat_penunjukan');
    }

    /**
     * Cetak lembar konfirmasi pelaksanaannya saja.
     *
     * Dipakai setelah pelaksana menjawab. Yang dibutuhkan saat itu bukan surat
     * lengkapnya lagi — instruksinya sudah dijalankan, dan halaman satunya sudah
     * ditandatangani serta diarsipkan. Yang perlu dicetak, ditandatangani
     * pelaksana, lalu diarsipkan lagi tinggal lembar konfirmasinya.
     *
     * Kop suratnya tetap ikut: kop tinggal di word/header1.xml, jadi terbawa ke
     * halaman mana pun yang tersisa.
     */
    public function hanyaKonfirmasi(): self
    {
        $this->cetak = self::CETAK_KONFIRMASI;

        return $this;
    }

    public function simpanKe(string $path): void
    {
        $template = public_path(self::TEMPLATE);

        if (! is_file($template)) {
            throw new RuntimeException(
                'Template surat penunjukan tidak ada di ' . $template . '. Berkas itu salinan '
                . 'berpenanda dari SURAT PENUNJUKAN PERUBAHAN DATA.docx dan harus ikut ter-deploy.'
            );
        }

        $isi = new TemplateProcessor($template);

        $this->isiKepala($isi);
        $this->isiRincian($isi);
        $this->isiTandaTangan($isi);

        // Halaman konfirmasi baru ikut setelah pelaksananya menjawab. Sebelum
        // itu suratnya berhenti di blok tanda tangan "Disetujui Oleh".
        //
        // Halaman kosong berisi kotak centang yang belum boleh diisi siapa pun
        // cuma mengundang orang mengisinya dengan tangan — dan jawaban yang
        // ditulis di kertas tidak pernah sampai ke sistem, jadi status
        // pelaksanaan di layar tetap kosong sementara kertasnya menyatakan
        // selesai.
        $hanyaKonfirmasi = $this->cetak === self::CETAK_KONFIRMASI;
        $adaKonfirmasi = $hanyaKonfirmasi || $this->penunjukan->sudahDilaksanakan();

        if ($adaKonfirmasi) {
            $this->isiKonfirmasi($isi);
        }

        $isi->saveAs($path);

        $this->potongBagian($path, $adaKonfirmasi, $hanyaKonfirmasi);
        $this->pastikanUtuh($path);
    }

    /**
     * Kirim sebagai unduhan.
     *
     * Lewat berkas sementara: TemplateProcessor menulis zip, dan penulis zip
     * butuh berkas yang bisa dicari posisinya — php://output tidak bisa.
     */
    public function unduh(string $namaBerkas): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $sementara = tempnam(sys_get_temp_dir(), 'surat-penunjukan-') . '.docx';

        $this->simpanKe($sementara);

        // Content-Type disebut eksplisit. Berkas sementaranya bernama
        // "surXXXX.tmp.docx" dan penebak tipe Symfony bisa berhenti di ".tmp",
        // lalu unduhannya terkirim tanpa tipe — sebagian browser menyimpannya
        // dengan akhiran salah, dan Word menolak berkas yang akhirannya bukan
        // .docx sebelum sempat membacanya.
        return response()
            ->download($sementara, $namaBerkas, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * Pastikan surat yang baru ditulis benar-benar bisa dibuka dan sudah terisi.
     *
     * Dua kegagalan yang pernah benar-benar terjadi di berkas ini, dan dua-duanya
     * DIAM:
     *
     * 1. Penanda tertinggal. TemplateProcessor::deleteBlock() memakai satu
     *    preg_match dengan beberapa `.*` serakah atas seluruh document.xml. Pada
     *    dokumen ini — 220 KB — PCRE berhenti dengan "Backtrack limit
     *    exhausted", preg_match mengembalikan false, dan methodnya selesai tanpa
     *    mengubah apa pun. Suratnya tercetak lengkap dengan `${konfirmasi}`
     *    mentah di badannya.
     * 2. XML tidak berpasangan. Pola `<w:p` tanpa pembatas juga cocok dengan
     *    `<w:pPr`, jadi pemotongan mulai dari tengah paragraf dan tag
     *    pembukanya tertinggal. Zip-nya tetap sah, ukurannya wajar, teksnya
     *    terbaca — tapi Word menolak membukanya dengan "Word experienced an
     *    error trying to open the file".
     *
     * Keduanya lolos dari pemeriksaan "zip valid" dan "berkasnya ada". Yang
     * menangkapnya hanya parser XML sungguhan dan pencarian penanda sisa, dan
     * itu yang dikerjakan di sini — sekali, tepat sebelum berkasnya diserahkan.
     */
    private function pastikanUtuh(string $path): void
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Surat yang baru ditulis bukan berkas .docx yang sah: ' . $path);
        }

        $xml = $zip->getFromName(self::BAGIAN_DOKUMEN);
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('Surat yang baru ditulis tidak punya ' . self::BAGIAN_DOKUMEN . '.');
        }

        $sisa = [];

        if (preg_match_all('/\$\{[^}]{1,60}\}/', $xml, $cocok)) {
            $sisa = array_values(array_unique($cocok[0]));
        }

        if ($sisa !== []) {
            throw new RuntimeException(
                'Surat tercetak dengan penanda yang belum terisi: ' . implode(', ', $sisa)
                . '. Biasanya berarti pengisian blok atau baris tabelnya gagal tanpa melempar error.'
            );
        }

        $sebelumnya = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $sah = $dom->loadXML($xml);
        $galat = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($sebelumnya);

        if (! $sah) {
            $pesan = $galat === [] ? 'tidak diketahui' : trim($galat[0]->message);

            throw new RuntimeException(
                'Surat tercetak dengan XML yang tidak berpasangan (' . $pesan . '). '
                . 'Word akan menolak membukanya.'
            );
        }
    }

    /**
     * Buang halaman konfirmasi, atau cukup buang penandanya.
     *
     * Dikerjakan sendiri di sini, tidak lewat TemplateProcessor::deleteBlock().
     * Method itu memakai satu preg_match dengan beberapa `.*` serakah atas
     * seluruh document.xml, dan pada dokumen ini — 220 KB — PCRE menyerah dengan
     * "Backtrack limit exhausted". preg_match mengembalikan false, methodnya
     * memeriksa `isset($matches[3])` yang jadi salah, lalu SELESAI TANPA
     * MENGUBAH APA PUN dan tanpa melempar apa-apa. Surat yang belum dilaksanakan
     * akan tercetak lengkap dengan penanda `${konfirmasi}` mentah di badannya.
     *
     * Penggantinya pencarian posisi biasa: tidak bisa kehabisan backtrack, dan
     * tidak bisa gagal diam-diam — kalau penandanya tidak ketemu, method ini
     * melempar.
     */
    private function potongBagian(string $path, bool $pertahankan, bool $hanyaKonfirmasi = false): void
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Surat yang baru ditulis tidak bisa dibuka lagi: ' . $path);
        }

        $xml = $zip->getFromName(self::BAGIAN_DOKUMEN);

        if ($xml === false) {
            $zip->close();

            throw new RuntimeException('Surat yang baru ditulis tidak punya ' . self::BAGIAN_DOKUMEN . '.');
        }

        $buka = $this->paragrafPenanda($xml, '${konfirmasi}');
        $tutup = $this->paragrafPenanda($xml, '${/konfirmasi}');

        // Penanda penutup selalu dibuang, dan selalu lebih dulu: memotong dari
        // belakang membuat posisi penanda pembuka tetap berlaku.
        $xml = substr($xml, 0, $tutup[0]) . substr($xml, $tutup[1]);

        if ($hanyaKonfirmasi) {
            // Semua yang mendahului blok konfirmasi dibuang, sampai batas awal
            // badan dokumen. `<w:sectPr>` di ujung tidak tersentuh — di situlah
            // ukuran kertas, margin, dan rujukan kop halaman disimpan.
            $badan = strpos($xml, '<w:body>');

            if ($badan === false) {
                $zip->close();

                throw new RuntimeException('Dokumen tidak punya <w:body>, tidak bisa dipotong.');
            }

            $badan += strlen('<w:body>');
            $xml = substr($xml, 0, $badan) . substr($xml, $buka[1]);
        } elseif ($pertahankan) {
            // Penandanya saja yang dibuang, isinya tetap.
            $xml = substr($xml, 0, $buka[0]) . substr($xml, $buka[1]);
        } else {
            $xml = substr($xml, 0, $buka[0]) . substr($xml, $tutup[0]);
        }

        $zip->addFromString(self::BAGIAN_DOKUMEN, $xml);
        $zip->close();
    }

    /**
     * Posisi awal dan akhir paragraf yang memuat sebuah penanda.
     *
     * @return array{0: int, 1: int}
     */
    private function paragrafPenanda(string $xml, string $penanda): array
    {
        $posisi = strpos($xml, $penanda);

        if ($posisi === false) {
            throw new RuntimeException(
                'Penanda ' . $penanda . ' tidak ada di template surat penunjukan. '
                . 'Template-nya perlu dibuat ulang dengan public/templates/buat-surat-penunjukan-penanda.py.'
            );
        }

        // `<w:p>` atau `<w:p `, bukan `<w:p`. Tanpa pembatas itu pencariannya
        // juga cocok dengan `<w:pPr>` milik paragraf yang sama — pemotongan
        // mulai dari tengah paragraf, dan `<w:p>` pembukanya tertinggal tanpa
        // penutup. Zip-nya tetap sah dan Word menolak membukanya.
        $depan = substr($xml, 0, $posisi);
        $awal = max(
            (int) strrpos($depan, '<w:p>'),
            (int) strrpos($depan, '<w:p ')
        );
        $akhir = strpos($xml, '</w:p>', $posisi);

        if ($awal === 0) {
            $awal = false;
        }

        if ($awal === false || $akhir === false) {
            throw new RuntimeException('Penanda ' . $penanda . ' tidak berada di dalam paragraf utuh.');
        }

        return [$awal, $akhir + strlen('</w:p>')];
    }

    private function isiKepala(TemplateProcessor $isi): void
    {
        $pengajuan = $this->penunjukan->perbaikanData;

        $isi->setValue('nomor_surat', $this->aman($this->penunjukan->nomorSuratCetak()));
        $isi->setValue('tanggal_surat', $this->aman($this->tanggalPanjang($this->penunjukan->tgl_penunjukan) ?? '-'));
        $isi->setValue('nomor_pengajuan', $this->aman(optional($pengajuan)->kode_pengajuan ?? '-'));
        $isi->setValue('tim_pemohon', $this->aman($this->penunjukan->timPemohon()));

        // Tanggal pengajuan bisa kosong. Kalimatnya berbunyi "Nomor X tanggal Y",
        // jadi yang kosong diisi tanda pisah alih-alih dibiarkan menggantung.
        $isi->setValue(
            'tanggal_pengajuan',
            $this->aman($this->tanggalPanjang(optional($pengajuan)->tgl_pengajuan) ?? '-')
        );

        // Pokok perubahan. Kalau penerbit surat tidak menuliskannya, dirangkum
        // dari nama kolom yang dikoreksi — kalimatnya jadi lebih kaku, tapi
        // suratnya tetap menyebut apa yang diubah.
        $pokok = $this->penunjukan->perihal_perubahan
            ?: ($this->rincian->isNotEmpty()
                ? 'perubahan ' . $this->rincian->pluck('uraian')->unique()->implode(', ')
                : 'perubahan data pada Sistem Inventory');

        $isi->setValue('pokok_perubahan', $this->aman($pokok));
        $isi->setValue(
            'daftar_kode',
            $this->aman($this->kodeTransaksi->isNotEmpty() ? $this->kodeTransaksi->implode(', ') : '-')
        );
    }

    /**
     * Tabel rincian: satu baris template digandakan sebanyak barisnya.
     *
     * Pengajuan tanpa rincian per kolom tetap mengisi satu baris, berisi
     * keterangan bahwa rinciannya ada di dokumen lampiran. Baris template yang
     * dibiarkan kosong akan tercetak dengan penanda `${uraian}` mentah, dan
     * surat dinas yang memuat itu tidak bisa ditandatangani.
     */
    private function isiRincian(TemplateProcessor $isi): void
    {
        if ($this->rincian->isEmpty()) {
            $isi->setValue('no', '1');
            $isi->setValue('uraian', $this->aman(
                'Rincian perubahan mengikuti dokumen permohonan yang dilampirkan.'
            ));
            $isi->setValue('data_lama', '-');
            $isi->setValue('data_baru', '-');

            return;
        }

        $isi->cloneRow('no', $this->rincian->count());

        foreach ($this->rincian->values() as $urutan => $baris) {
            $nomor = $urutan + 1;

            // Kode dan alasan ikut di sel uraian, dipisah baris baru. Dokumen
            // aslinya hanya punya empat kolom, dan menambah kolom berarti tata
            // letaknya bukan lagi tata letak yang disetujui.
            $uraian = $baris['uraian'];

            if (! empty($baris['kode'])) {
                $uraian .= "\n" . $baris['kode'];
            }

            if (! empty($baris['alasan'])) {
                $uraian .= "\nAlasan: " . $baris['alasan'];
            }

            $isi->setValue('no#' . $nomor, (string) $nomor);
            $isi->setValue('uraian#' . $nomor, $this->barisBaru($uraian));

            // Nilai dicetak mentah: memformat ulang angka bisa membuat dua nilai
            // yang berbeda tampil sama, dan justru salah ketik titik atau nol
            // yang paling sering dikoreksi lewat modul ini.
            $isi->setValue('data_lama#' . $nomor, $this->aman($baris['nilai_lama'] ?? '(kosong)'));
            $isi->setValue('data_baru#' . $nomor, $this->aman($baris['nilai_baru'] ?? '(kosong)'));
        }
    }

    /**
     * Nama dan nomor ID penanda tangan, keduanya dari sumber yang sama.
     *
     * Penerbit surat dan pelaksananya diambil dari barisnya sendiri di
     * `users` — merekalah yang tercatat di penunjukan ini. Empat penanda tangan
     * tetap (Leader FAT, Leader Software, Manajer FAT, Manajer Software)
     * dari config.
     *
     * Yang penting: nomor ID selalu ikut namanya. Nomor tetap yang tertulis di
     * dokumen Word akan tertinggal begitu orangnya berganti, dan surat yang
     * memuat nama seseorang dengan nomor ID orang lain lebih buruk daripada
     * surat tanpa nomor sama sekali.
     */
    private function isiTandaTangan(TemplateProcessor $isi): void
    {
        $ttd = $this->surat['tanda_tangan'];
        $penunjuk = $this->penunjukan->penunjuk;

        // Nama penerbit surat, bukan nama dari config: yang menerbitkan surat
        // ini tercatat di barisnya sendiri.
        //
        // Nama dan nomornya dibaca sebagai satu pasang. Dibaca sendiri-sendiri,
        // penerbit yang barisnya belum punya `nomor_id` akan tercetak dengan
        // nomor milik orang di config: namanya satu orang, nomornya orang lain,
        // di blok tanda tangan yang sama.
        [$namaDibuat, $idDibuat] = $this->pasangan(
            optional($penunjuk)->name,
            optional($penunjuk)->nomor_id,
            $ttd['dibuat']
        );

        $isi->setValue('nama_dibuat', $namaDibuat);
        $isi->setValue('id_dibuat', $idDibuat);

        foreach ([
            'diperiksa_fat' => $ttd['diperiksa_fat'],
            'koordinasi' => $ttd['dikoordinasikan_software'],
            'setuju_fat' => $ttd['disetujui_fat'],
            'setuju_software' => $ttd['disetujui_software'],
        ] as $penanda => $orang) {
            $isi->setValue('nama_' . $penanda, $this->aman($orang['nama'] ?: self::GARIS_KOSONG));
            $isi->setValue('id_' . $penanda, $this->nomorId($orang['id'] ?? null));
        }
    }

    private function isiKonfirmasi(TemplateProcessor $isi): void
    {
        // Satu sumber saja: pelaksana yang ditunjuk di surat ini. Dulu ada
        // kolom ketikan `nama_petugas` di samping dropdown penunjukan, dan dua
        // tempat mengisi orang yang sama berarti dua jawaban yang bisa berbeda
        // untuk satu tanda tangan. Yang diketik juga tidak membawa nomor induk,
        // jadi blok tanda tangannya kehilangan ID-nya. Kalau ternyata bukan dia
        // yang mengerjakan, yang dibetulkan penunjukannya — lewat Ubah Surat.
        $pelaksana = $this->penunjukan->pelaksana;
        $namaPelaksana = trim((string) optional($pelaksana)->name);

        // Garis panjang untuk ditulis tangan sudah dibuang dari template: nilai
        // yang tercetak lalu disusul garis kosong sepanjang sisa baris terbaca
        // seperti isian yang belum selesai. Yang kosong tetap dapat tempat
        // menulis, berupa titik-titik dari sini.
        $isi->setValue(
            'tanggal_pelaksanaan',
            $this->aman($this->tanggalPanjang($this->penunjukan->tgl_pelaksanaan) ?: self::GARIS_KOSONG)
        );
        $isi->setValue('nama_pelaksana', $this->aman($namaPelaksana ?: self::GARIS_KOSONG));

        // Keterangan pelaksana selalu dicetak, apa pun statusnya. Sebelumnya
        // tidak pernah ikut sama sekali — bagian ini kosong di setiap surat,
        // padahal justru di situ pelaksana menjelaskan apa yang dikerjakan dan
        // apa yang tidak.
        $isi->setValue('keterangan', $this->aman(
            trim((string) $this->penunjukan->keterangan) ?: self::GARIS_KOSONG
        ));

        // Kotak centang, bukan satu status tercetak: suratnya dicetak juga
        // sebelum dijawab, dan halaman ini diisi tangan kalau pelaksananya belum
        // mengisi di sistem.
        $pilihan = array_values((array) $this->surat['konfirmasi']['status']);

        foreach ([1, 2, 3] as $urutan) {
            $status = $pilihan[$urutan - 1] ?? null;

            $isi->setValue(
                'kotak_' . $urutan,
                $status !== null && $this->penunjukan->status === $status
                    ? self::KOTAK_ISI
                    : self::KOTAK_KOSONG
            );
        }

        // Nama petugas yang benar-benar mengerjakan, bukan nama yang ditunjuk:
        // keduanya bisa berbeda, dan halaman ini mencatat siapa pelaksananya.
        $dilaksanakan = $this->surat['konfirmasi']['tanda_tangan']['dilaksanakan'];

        [$namaTtd, $idTtd] = $this->pasangan(
            $namaPelaksana,
            optional($pelaksana)->nomor_id,
            $dilaksanakan
        );

        $isi->setValue('nama_pelaksana_ttd', $namaTtd);
        $isi->setValue('id_pelaksana', $idTtd);
    }

    /**
     * Nama dan nomor ID yang dijamin milik orang yang sama.
     *
     * Slot tanda tangan punya dua sumber: baris `users` orang yang tercatat di
     * penunjukan ini, dan isi tetap di config. Bahayanya kalau keduanya dicampur
     * dalam satu slot — nama dari `users`, nomor dari config — karena hasilnya
     * surat resmi yang menempelkan nomor induk seseorang pada nama orang lain.
     * Jatuh ke config karena itu dilakukan untuk sepasang sekaligus, bukan per
     * nilai.
     *
     * Nomor yang kosong dicetak sebagai garis titik, dan itu jawaban yang benar:
     * nomor orang ini memang belum diketahui, bukan bahwa nomornya nomor lain.
     *
     * @param  array{nama: ?string, id: ?string}  $cadangan  isi config untuk slot ini
     * @return array{0: string, 1: string}  nama siap cetak, nomor ID siap cetak
     */
    private function pasangan(?string $nama, ?string $nomor, array $cadangan): array
    {
        $nama = trim((string) $nama);

        if ($nama !== '') {
            return [$this->aman($nama), $this->nomorId($nomor)];
        }

        return [
            $this->aman(trim((string) ($cadangan['nama'] ?? '')) ?: self::GARIS_KOSONG),
            $this->nomorId($cadangan['id'] ?? null),
        ];
    }

    /**
     * Nomor ID pegawai siap cetak, berawalan "ID." secara seragam.
     *
     * Sumbernya dua dan bentuknya berbeda: `users.nomor_id` diisi apa adanya
     * ("001/SUPER/I/2026", mengikuti bentuk dari HRIS), sedangkan config
     * menuliskannya lengkap dengan awalan. Diseragamkan di sini supaya satu
     * surat tidak memuat dua gaya penulisan pada blok tanda tangan yang sama.
     *
     * Yang kosong dicetak sebagai garis titik, bukan dibiarkan hilang: baris ID
     * yang kosong membuat blok tanda tangannya terbaca seperti salah cetak.
     */
    private function nomorId(?string $nomor): string
    {
        $nomor = trim((string) $nomor);

        if ($nomor === '') {
            return 'ID. ' . self::GARIS_KOSONG;
        }

        return $this->aman(str_starts_with($nomor, 'ID.') ? $nomor : 'ID. ' . $nomor);
    }

    /**
     * Nama bulan Indonesia, ditulis sendiri dan tidak lewat lokal Carbon.
     *
     * Carbon di aplikasi ini tidak dipastikan berlokal id. Tanggal surat dinas
     * yang tercetak "02 September" lalu berubah jadi "02 Sep" karena konfigurasi
     * server bukan sesuatu yang boleh bergantung pada lingkungan.
     */
    private function tanggalPanjang(?Carbon $tanggal): ?string
    {
        if (! $tanggal) {
            return null;
        }

        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $tanggal->format('d') . ' ' . $bulan[(int) $tanggal->format('n')] . ' ' . $tanggal->format('Y');
    }

    /**
     * Teks banyak baris untuk satu sel tabel.
     *
     * "\n" tidak berarti apa-apa di dalam XML dokumen Word; yang memisah baris
     * `<w:br/>`. Tanpa ini, kode transaksi dan alasan menempel jadi satu baris
     * panjang bersama uraiannya.
     */
    private function barisBaru(string $teks): string
    {
        return implode('</w:t><w:br/><w:t>', array_map(
            fn ($baris) => $this->aman($baris),
            explode("\n", $teks)
        ));
    }

    /**
     * Teks siap masuk XML dokumen.
     *
     * TemplateProcessor menyisipkan nilainya apa adanya. Satu ampersand pada
     * nama bahan sudah cukup membuat berkasnya ditolak Word dengan pesan "file
     * rusak" — dan nama bahan di aplikasi ini memang memuat "&".
     */
    private function aman(?string $teks): string
    {
        return htmlspecialchars((string) $teks, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
