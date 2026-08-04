<?php

namespace App\Imports;

use App\Models\BarangAset;
use App\Models\RekapAset;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Pembaca sekaligus pemroses import rekap aset.
 *
 * Format template lama (header di baris pertama) dan worksheet opname yang
 * mempunyai judul/kategori di atas tabel sama-sama diterima. Data tetap di-upsert
 * lewat nomor aset: sel kosong tidak menghapus nilai lama.
 *
 * Penanggung jawab & pemegang HANYA diisi dari nilai yang benar-benar cocok
 * dengan nama user. Kolom "PIC" pada worksheet opname berisi jabatan, bukan nama,
 * dan jabatan sengaja TIDAK diterjemahkan ke orang: menebak siapa pemegang sebuah
 * jabatan berisiko menaruh aset pada orang yang salah, dan salah tebak seperti itu
 * baru ketahuan saat aset ditagih.
 *
 * Nilai yang tidak dikenali dilewati tanpa menggagalkan import — kolomnya
 * dibiarkan kosong dan jumlahnya dilaporkan lewat ringkasan(), supaya jelas
 * berapa aset yang masih perlu diisi manual.
 */
class RekapAsetImport
{
    /** Kata yang menandai baris rekapitulasi, bukan baris aset. */
    private const KATA_REKAP = ['total', 'subtotal', 'sub total', 'jumlah', 'grand total'];

    /**
     * Kata tingkat jabatan pada kolom PIC -> job_level di data user.
     *
     * Diturunkan dari isi data: level 1 diisi Komisaris & Direktur, level 2 semua
     * jabatan *Manager, level 3 leader/senior tiap unit, level 4 staf.
     */
    private const TINGKAT_JABATAN = [
        'direktur' => 1,
        'director' => 1,
        'manajer' => 2,
        'manager' => 2,
        'leader' => 3,
        'lead' => 3,
    ];

    /**
     * Nama unit di worksheet yang tidak sama dengan nama job position / divisi.
     *
     * Hanya untuk yang memang tidak bisa dijembatani dari katanya:
     *   supply chain      - unitnya terdaftar sebagai job position "Purchasing"
     *   corporate service - pemegangnya terdaftar di job position "HRD"
     *
     * Unit lain (hardware, software, marketing, rnd, admin) sudah sama namanya
     * dengan job position atau divisi, jadi tidak perlu dicantumkan di sini.
     */
    private const ALIAS_UNIT = [
        'supply chain' => 'purchasing',
        'corporate service' => 'hrd',
    ];

    public int $jumlahBaru = 0;

    public int $jumlahDiperbarui = 0;

    public int $jumlahTidakBerubah = 0;

    /** Baris yang penanggung jawab atau pemegangnya tidak bisa ditentukan. */
    public int $jumlahTanpaPenanggungJawab = 0;

    public int $jumlahTanpaPemegang = 0;

    /** Nilai yang gagal dikenali, untuk disebut di ringkasan. */
    private array $nilaiTidakDikenal = [];

    /** Katalog barang, dimuat sekali per import. Lihat muatKatalog(). */
    private ?array $katalogNama = null;

    private ?array $katalogPersis = null;

    private ?array $katalogHimpunanKata = null;

    /** Ruangan, dimuat sekali per import. Lihat muatRuangan(). */
    private ?array $ruanganNama = null;

    private ?array $ruanganPersis = null;

    private ?array $ruanganKata = null;

    /** Kode barang yang sudah terpakai, untuk menjamin kode baru tidak bentrok. */
    private ?array $kodeTerpakai = null;

    /** Nama barang yang baru didaftarkan ke katalog oleh import ini. */
    private array $barangDibuat = [];

    /** Hasil terjemahan jabatan, agar jabatan yang sama tidak dihitung berulang. */
    private array $cacheJabatan = [];

    /** Jabatan yang berhasil diterjemahkan: jabatan => nama orang. */
    private array $jabatanTerpetakan = [];

    /** Alasan kegagalan terjemahan jabatan, untuk dilaporkan. */
    private array $jabatanGagal = [];

    private array $nomorAsetCache = [];

    /**
     * Baca sheet pertama tanpa menulis apa pun ke database.
     */
    public function bacaFile($file): array
    {
        $sheets = Excel::toArray([], $file);

        return $this->bacaSheet($sheets[0] ?? []);
    }

    /**
     * Ubah worksheet mentah menjadi baris dengan nama kolom internal.
     */
    public function bacaSheet(array $rows): array
    {
        [$indexHeader, $petaHeader] = $this->temukanHeader($rows);
        $hasil = [];

        foreach (array_slice($rows, $indexHeader + 1, null, true) as $index => $row) {
            $row = is_array($row) ? $row : (array) $row;

            if ($this->adalahBarisHeader($row)) {
                continue; // header berulang pada blok kategori berikutnya
            }

            $data = [];
            foreach ($petaHeader as $kolom => $nama) {
                $data[$nama] = $row[$kolom] ?? null;
            }

            if (empty(array_filter($data, fn ($nilai) => $nilai !== null && trim((string) $nilai) !== ''))) {
                continue;
            }

            $nomorAset = trim((string) ($data['nomor_aset'] ?? ''));
            $namaAset = self::normalisasiTeks($data['nama_aset'] ?? '');

            // Baris rekapitulasi ("Total", "Subtotal", "Jumlah") bukan data aset.
            //
            // Kata kuncinya bisa mendarat di kolom mana saja: di worksheet aslinya
            // selnya digabung melintasi beberapa kolom, dan nilainya jatuh di kolom
            // paling kiri gabungan itu — kadang kolom No, kadang Nomor Aset, kadang
            // Nama Aset, tergantung lebar gabungannya. Karena itu seluruh baris
            // diperiksa, bukan satu kolom tertentu.
            //
            // Syarat keduanya penting: aset yang namanya sah tidak boleh terbuang
            // hanya karena ada sel lain di barisnya yang berbunyi "jumlah".
            $namaAsetBukanAset = $namaAset === '' || in_array($namaAset, self::KATA_REKAP, true);

            if ($namaAsetBukanAset && $this->adalahBarisTotal($row)) {
                continue;
            }

            // Tanpa nomor aset dan tanpa nama aset, baris itu hiasan atau catatan
            // kaki — bukan data yang gagal, jadi dilewati tanpa error.
            if ($nomorAset === '' && $namaAset === '') {
                continue;
            }

            $data['_baris'] = $index + 1; // indeks array nol, nomor Excel satu
            $hasil[] = $data;
        }

        return $hasil;
    }

    /**
     * Baca lalu tulis seluruh baris. Dipanggil dari dalam satu transaksi, jadi
     * satu baris yang gagal membatalkan seluruh import.
     */
    public function prosesFile($file): void
    {
        $semua = $this->bacaFile($file);

        // Seluruh berkas diperiksa lebih dulu, sebelum satu baris pun ditulis.
        // Kalau kegagalan dilaporkan per baris, pengguna harus membetulkan satu
        // nama lalu unggah ulang, berulang kali sampai habis — untuk worksheet
        // opname berisi puluhan baris itu menyiksa.
        $this->pastikanAcuanValid($semua);

        foreach ($semua as $data) {
            $this->prosesBaris($data);
        }
    }

    /**
     * Periksa seluruh acuan (nama barang & ruangan) lebih dulu, lalu laporkan
     * semua masalahnya sekaligus.
     *
     * Barang yang belum ada di katalog tidak dipermasalahkan — nanti didaftarkan
     * otomatis. Yang menghentikan import hanya yang tidak bisa ditentukan:
     *
     *   nama barang ambigu  - cocok ke >1 entri katalog. Memilih salah satu berarti
     *                         menebak; mendaftarkannya sebagai entri baru justru
     *                         menambah kembaran ketiga untuk barang yang sama.
     *   ruangan tidak jelas - tidak ketemu, atau cocok ke >1 ruangan.
     */
    private function pastikanAcuanValid(array $semua): void
    {
        $masalah = [];

        foreach ($semua as $data) {
            $baris = (int) ($data['_baris'] ?? 0);

            $nama = trim((string) ($data['nama_aset'] ?? ''));
            $kunciNama = 'barang:'.self::normalisasiTeks($nama);

            if ($nama !== '' && ! isset($masalah[$kunciNama])) {
                $cocok = $this->cariBarangAset($nama);

                if ($cocok['id'] === null && $cocok['ambigu'] !== []) {
                    $masalah[$kunciNama] = "baris {$baris}: nama barang '{$nama}' cocok dengan "
                        .count($cocok['ambigu']).' barang di katalog ('.implode(' / ', $cocok['ambigu']).')';
                }
            }

            $ruangan = trim((string) ($data['ruangan'] ?? ''));
            $kunciRuangan = 'ruangan:'.self::normalisasiTeks($ruangan);

            if ($ruangan !== '' && ! isset($masalah[$kunciRuangan])) {
                $cocok = $this->cariRuangan($ruangan);

                if ($cocok['id'] === null) {
                    $masalah[$kunciRuangan] = "baris {$baris}: ruangan '{$ruangan}' "
                        .($cocok['kandidat'] === []
                            ? 'tidak ada di master Ruangan Aset'
                            : 'cocok dengan '.count($cocok['kandidat']).' ruangan ('.implode(' / ', $cocok['kandidat']).')');
                }
            }
        }

        if ($masalah === []) {
            return;
        }

        throw new \RuntimeException(
            'Ada '.count($masalah).' acuan yang tidak bisa ditentukan: '.implode('; ', $masalah)
            .'. Samakan penulisannya dengan data yang sudah ada, atau tambahkan dulu di menu Ruangan Aset / Barang Aset.'
        );
    }

    private function temukanHeader(array $rows): array
    {
        foreach ($rows as $index => $row) {
            $peta = [];
            foreach ((array) $row as $kolom => $nilai) {
                $nama = self::namaKolom($nilai);
                if ($nama !== null && $nama !== 'no') {
                    $peta[$kolom] = $nama;
                }
            }

            if (in_array('nomor_aset', $peta, true) && in_array('nama_aset', $peta, true)) {
                return [$index, $peta];
            }
        }

        throw new \RuntimeException("Header Excel tidak ditemukan. Pastikan terdapat kolom 'Nomor Aset' dan 'Nama Aset'.");
    }

    /**
     * Ada sel yang berbunyi kata rekapitulasi, di kolom mana pun.
     *
     * Pencocokannya persis, bukan "mengandung": aset bernama "Total Station"
     * (alat ukur) tidak boleh dianggap baris total.
     */
    private function adalahBarisTotal(array $row): bool
    {
        foreach ($row as $nilai) {
            if (in_array(self::normalisasiTeks($nilai), self::KATA_REKAP, true)) {
                return true;
            }
        }

        return false;
    }

    private function adalahBarisHeader(array $row): bool
    {
        $nama = array_values(array_filter(array_map([self::class, 'namaKolom'], $row)));

        return in_array('nomor_aset', $nama, true) && in_array('nama_aset', $nama, true);
    }

    /**
     * Alias header template aplikasi dan worksheet opname perusahaan.
     */
    public static function namaKolom($header): ?string
    {
        $slug = Str::of((string) $header)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        if ($slug === '') {
            return null;
        }

        $langsung = [
            'no' => 'no',
            'nomor_aset' => 'nomor_aset',
            'nama_aset' => 'nama_aset',
            'serial_number' => 'serial_number',
            'merek' => 'merek',
            'link_gambar' => 'link_gambar',
            'tanggal_perolehan' => 'tanggal_perolehan',
            'tanggal_akuisisi' => 'tanggal_perolehan',
            'jumlah_aset' => 'jumlah_aset',
            'harga_perolehan' => 'harga_perolehan',
            'nilai_perolehan' => 'harga_perolehan',
            'kondisi_aset' => 'kondisi_aset',
            // "Kondisi" telanjang wajib ada di sini, bukan cuma mengandalkan pola
            // 'kondisi_' di bawah: di worksheet opname judulnya dua baris —
            // "Kondisi" di baris atas, "(Baik/Rusak Ringan/Rusak)" di baris bawah —
            // sehingga sel yang terbaca sebagai header hanya berisi "Kondisi".
            'kondisi' => 'kondisi_aset',
            'kondisi_barang' => 'kondisi_aset',
            'keterangan' => 'keterangan',
            'nama_penanggungjawab' => 'nama_penanggungjawab',
            'nama_penanggung_jawab' => 'nama_penanggungjawab',
            'nama_pic' => 'nama_pic',
            'ruangan' => 'ruangan',
            'lokasi' => 'ruangan',
            'pic' => 'pic_jabatan',
        ];

        if (isset($langsung[$slug])) {
            return $langsung[$slug];
        }

        return match (true) {
            str_starts_with($slug, 'person_yang_membawa') => 'nama_pic',
            str_starts_with($slug, 'kondisi_') => 'kondisi_aset',
            str_starts_with($slug, 'status_fisik') => 'status_fisik',
            default => null,
        };
    }

    private function prosesBaris(array $data): void
    {
        $baris = (int) ($data['_baris'] ?? 0);
        $nomorAset = trim((string) ($data['nomor_aset'] ?? ''));

        if ($nomorAset === '') {
            throw new \RuntimeException("Error pada kolom 'nomor_aset' di baris {$baris} Excel: Nomor aset wajib diisi.");
        }

        if (in_array($nomorAset, $this->nomorAsetCache, true)) {
            throw new \RuntimeException("Error pada kolom 'nomor_aset' di baris {$baris} Excel: Nomor aset duplikat pada file Excel.");
        }
        $this->nomorAsetCache[] = $nomorAset;

        $aset = RekapAset::where('nomor_aset', $nomorAset)->first();
        $asetBaru = $aset === null;
        $nilai = $this->petakanNilai($data, $baris, $asetBaru);

        if ($asetBaru) {
            RekapAset::denganAlasan(
                'Penetapan awal lewat import Excel (baris '.$baris.')',
                fn () => RekapAset::create(array_merge(['nomor_aset' => $nomorAset], array_filter(
                    $nilai,
                    fn ($value) => $value !== null
                )))
            );
            $this->jumlahBaru++;

            return;
        }

        $perubahan = [];
        foreach ($nilai as $kolom => $nilaiBaru) {
            if ($nilaiBaru === null) {
                continue;
            }

            if (! $this->samaSaja($aset->{$kolom}, $nilaiBaru)) {
                $perubahan[$kolom] = $nilaiBaru;
            }
        }

        if ($perubahan === []) {
            $this->jumlahTidakBerubah++;

            return;
        }

        RekapAset::denganAlasan(
            'Diperbarui lewat import Excel (baris '.$baris.')',
            fn () => $aset->update($perubahan)
        );
        $this->jumlahDiperbarui++;
    }

    private function petakanNilai(array $data, int $baris, bool $asetBaru): array
    {
        $nilai = [];

        if (! empty($data['nama_aset'])) {
            $cocok = $this->cariBarangAset((string) $data['nama_aset']);

            // Belum ada di katalog: didaftarkan sekarang. Yang ambigu tetap
            // ditolak — sudah disaring pastikanNamaBarangTidakAmbigu(), dan
            // pemeriksaan ini menjaga pemanggil yang memakai prosesBaris()
            // langsung tanpa lewat prosesFile().
            if ($cocok['id'] === null && $cocok['ambigu'] !== []) {
                throw new \RuntimeException("Error pada kolom 'nama_aset' di baris {$baris} Excel: '{$data['nama_aset']}' cocok dengan "
                    .count($cocok['ambigu']).' barang di katalog ('.implode(' / ', $cocok['ambigu']).').');
            }

            $nilai['barang_aset_id'] = $cocok['id'] ?? $this->buatBarangAset((string) $data['nama_aset']);
        } elseif ($asetBaru) {
            throw new \RuntimeException("Error pada kolom 'nama_aset' di baris {$baris} Excel: Wajib diisi untuk aset baru.");
        }

        // Penanggung jawab: nama yang ditulis eksplisit menang atas jabatan.
        //
        // Nama orang tidak perlu ditafsirkan, sedangkan jabatan harus diterjemahkan
        // lewat job level + job position/divisi. Kalau keduanya ada, yang eksplisit
        // dipakai — penulisnya jelas sudah tahu siapa yang dimaksud.
        //
        // Yang tidak berhasil ditentukan dibiarkan kosong dan dilaporkan, bukan
        // menggagalkan import.
        $namaPenanggungJawab = $data['nama_penanggungjawab'] ?? null;

        if (! empty($namaPenanggungJawab)) {
            $user = $this->cariUserDenganNama($namaPenanggungJawab);

            if ($user) {
                $nilai['user_id'] = $user->id;
            } else {
                $this->catatTidakDikenal($namaPenanggungJawab);
                $this->jumlahTanpaPenanggungJawab++;
            }
        } elseif (! empty($data['pic_jabatan'])) {
            $jabatan = trim((string) $data['pic_jabatan']);
            $hasil = $this->penanggungJawabDariJabatan($jabatan);

            if ($hasil['user']) {
                $nilai['user_id'] = $hasil['user']->id;
                $this->jabatanTerpetakan[$jabatan] = $hasil['user']->name;
            } else {
                $this->jabatanGagal[$jabatan] = $hasil['alasan'];
                $this->jumlahTanpaPenanggungJawab++;
            }
        } elseif ($asetBaru) {
            $this->jumlahTanpaPenanggungJawab++;
        }

        if (! empty($data['merek'])) {
            $nilai['merek'] = trim((string) $data['merek']);
        }

        // Kolom "Person yang Membawa" kadang berisi nama, kadang jabatan. Yang
        // dipakai hanya kalau cocok persis dengan nama user aktif.
        if (! empty($data['nama_pic'])) {
            $pemegang = User::query()
                ->where('status', 'Aktif')
                ->whereRaw('LOWER(TRIM(name)) = ?', [self::normalisasiTeks($data['nama_pic'])])
                ->first();

            if ($pemegang) {
                $nilai['pic_id'] = $pemegang->id;
            } else {
                $this->catatTidakDikenal($data['nama_pic']);
                $this->jumlahTanpaPemegang++;
            }
        }

        if (! empty($data['ruangan'])) {
            // Sudah disaring pastikanAcuanValid(). Pemeriksaan di sini menjaga
            // pemanggil yang memakai prosesBaris() langsung tanpa lewat prosesFile().
            $cocok = $this->cariRuangan((string) $data['ruangan']);

            if ($cocok['id'] === null) {
                throw new \RuntimeException("Error pada kolom 'lokasi/ruangan' di baris {$baris} Excel: Nilai '{$data['ruangan']}' "
                    .($cocok['kandidat'] === []
                        ? 'tidak ditemukan di master Ruangan Aset.'
                        : 'cocok dengan '.count($cocok['kandidat']).' ruangan ('.implode(' / ', $cocok['kandidat']).').'));
            }

            $nilai['ruangan_id'] = $cocok['id'];
        }

        if (! empty($data['tanggal_perolehan'])) {
            $nilai['tgl_perolehan'] = $this->bacaTanggal($data['tanggal_perolehan'], $baris);
        }

        if (! empty($data['harga_perolehan'])) {
            $nilai['harga_perolehan'] = $this->bacaAngka($data['harga_perolehan'], 'nilai_perolehan', $baris);
        }

        if (isset($data['jumlah_aset']) && trim((string) $data['jumlah_aset']) !== '') {
            $nilai['jumlah_aset'] = max(1, (int) $this->bacaAngka($data['jumlah_aset'], 'jumlah_aset', $baris));
        } elseif ($asetBaru) {
            // Worksheet opname tidak punya kolom jumlah: satu barisnya berarti satu
            // unit, dibedakan lewat nomor asetnya. Hanya untuk aset baru — pada aset
            // yang sudah ada, kolom kosong berarti "jangan disentuh", sesuai sifat
            // upsert import ini.
            $nilai['jumlah_aset'] = 1;
        }

        if (isset($data['kondisi_aset']) && trim((string) $data['kondisi_aset']) !== '') {
            $kondisiSumber = self::normalisasiTeks($data['kondisi_aset']);
            $nilai['kondisi'] = match ($kondisiSumber) {
                'b', 'baik' => 'Baik',
                'r', 'rr', 'rusak', 'rusak ringan' => 'Rusak',
                default => throw new \RuntimeException("Error pada kolom 'kondisi' di baris {$baris} Excel: Nilai '{$data['kondisi_aset']}' tidak dikenal. Gunakan B/Baik atau RR/R/Rusak."),
            };
        }

        foreach ([
            'serial_number' => 'serial_number',
            'link_gambar' => 'link_gambar',
            'keterangan' => 'keterangan',
        ] as $kolomExcel => $kolomDb) {
            if (isset($data[$kolomExcel]) && trim((string) $data[$kolomExcel]) !== '') {
                $nilai[$kolomDb] = is_string($data[$kolomExcel]) ? trim($data[$kolomExcel]) : $data[$kolomExcel];
            }
        }

        // Status A/TA/Dipinjam pada worksheet adalah hasil opname, bukan status
        // transaksi peminjaman. Simpan jejaknya di keterangan agar tidak menimpa
        // status live yang dihitung aplikasi.
        if (isset($data['status_fisik']) && trim((string) $data['status_fisik']) !== '') {
            $status = $this->namaStatusFisik($data['status_fisik'], $baris);
            $catatanStatus = "Status fisik saat import: {$status}";
            $nilai['keterangan'] = isset($nilai['keterangan'])
                ? $nilai['keterangan'].' | '.$catatanStatus
                : $catatanStatus;
        }

        return $nilai;
    }

    /**
     * User dengan nama yang cocok persis, atau null. Pencocokannya mengabaikan
     * besar-kecil huruf dan spasi berlebih, karena data dari Excel hampir selalu
     * punya spasi tak sengaja — tapi tidak menebak ejaan yang berbeda.
     */
    private function cariUserDenganNama($nama): ?User
    {
        return User::whereRaw('LOWER(TRIM(name)) = ?', [self::normalisasiTeks($nama)])->first();
    }

    /**
     * Kumpulkan nilai yang tidak bisa dicocokkan ke user, untuk disebut namanya
     * di ringkasan. Menyebut nilainya jauh lebih berguna daripada sekadar
     * memberi tahu ada N baris yang kosong.
     */
    private function catatTidakDikenal($nilai): void
    {
        $nilai = trim((string) $nilai);

        if ($nilai !== '' && ! in_array($nilai, $this->nilaiTidakDikenal, true)) {
            $this->nilaiTidakDikenal[] = $nilai;
        }
    }

    private function namaStatusFisik($nilai, int $baris): string
    {
        return match (self::normalisasiTeks($nilai)) {
            'a', 'ada' => 'Ada',
            'ta', 'tidak ada' => 'Tidak Ada',
            'd', 'dipinjam' => 'Dipinjam',
            default => throw new \RuntimeException("Error pada kolom 'status_fisik' di baris {$baris} Excel: Nilai '{$nilai}' tidak dikenal. Gunakan A, TA, atau Dipinjam."),
        };
    }

    private function bacaTanggal($nilai, int $baris): string
    {
        if ($nilai instanceof \DateTimeInterface) {
            return $nilai->format('Y-m-d');
        }

        if (is_numeric($nilai)) {
            try {
                return Date::excelToDateTimeObject((float) $nilai)->format('Y-m-d');
            } catch (\Throwable) {
                throw new \RuntimeException("Error pada kolom 'tanggal_perolehan' di baris {$baris} Excel: Nilai tidak valid.");
            }
        }

        $teks = trim((string) $nilai);
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $format) {
            $tanggal = \DateTime::createFromFormat($format, $teks);
            if ($tanggal && $tanggal->format($format) === $teks) {
                return $tanggal->format('Y-m-d');
            }
        }

        throw new \RuntimeException("Error pada kolom 'tanggal_perolehan' di baris {$baris} Excel: Nilai '{$teks}' bukan tanggal yang dikenali.");
    }

    private function bacaAngka($nilai, string $kolom, int $baris): float
    {
        if (is_int($nilai) || is_float($nilai)) {
            return (float) $nilai;
        }

        $teks = trim((string) $nilai);
        $bersih = preg_replace('/[^0-9,.\-]/', '', $teks);

        if ($bersih === '' || $bersih === '-') {
            throw new \RuntimeException("Error pada kolom '{$kolom}' di baris {$baris} Excel: Nilai '{$teks}' bukan angka.");
        }

        $posTitik = strrpos($bersih, '.');
        $posKoma = strrpos($bersih, ',');

        if ($posTitik !== false && $posKoma !== false) {
            $pemisahDesimal = $posTitik > $posKoma ? '.' : ',';
        } elseif ($posTitik !== false || $posKoma !== false) {
            $tanda = $posTitik !== false ? '.' : ',';
            $posisi = $posTitik !== false ? $posTitik : $posKoma;
            $digitSetelah = strlen($bersih) - $posisi - 1;
            $pemisahDesimal = (substr_count($bersih, $tanda) > 1 || $digitSetelah === 3) ? null : $tanda;
        } else {
            $pemisahDesimal = null;
        }

        if ($pemisahDesimal === null) {
            $angka = str_replace(['.', ','], '', $bersih);
        } else {
            $pemisahRibuan = $pemisahDesimal === '.' ? ',' : '.';
            $angka = str_replace($pemisahRibuan, '', $bersih);
            $angka = str_replace($pemisahDesimal, '.', $angka);
        }

        if (! is_numeric($angka)) {
            throw new \RuntimeException("Error pada kolom '{$kolom}' di baris {$baris} Excel: Nilai '{$teks}' bukan angka.");
        }

        return (float) $angka;
    }

    private function samaSaja($lama, $baru): bool
    {
        if ($lama === null) {
            return false;
        }

        if (is_numeric($lama) && is_numeric($baru)) {
            return (float) $lama === (float) $baru;
        }

        return (string) $lama === (string) $baru;
    }

    private static function normalisasiTeks($nilai): string
    {
        return Str::lower(Str::squish((string) $nilai));
    }

    /**
     * Katalog barang dimuat sekali per import, bukan satu query per baris.
     *
     * Worksheet opname bisa berisi puluhan baris dengan nama yang berulang, dan
     * pencocokan longgar di bawah butuh membandingkan ke seluruh katalog.
     */
    private function muatKatalog(): void
    {
        if ($this->katalogNama !== null) {
            return;
        }

        $this->katalogNama = [];
        $this->katalogPersis = [];
        $this->katalogHimpunanKata = [];

        foreach (BarangAset::get(['id', 'nama_barang']) as $barang) {
            $this->katalogNama[$barang->id] = $barang->nama_barang;
            $this->katalogPersis[self::normalisasiTeks($barang->nama_barang)] = $barang->id;
            $this->katalogHimpunanKata[self::kunciHimpunanKata($barang->nama_barang)][] = $barang->id;
        }
    }

    /**
     * Cari barang di katalog: cocok persis dulu, baru cocok tanpa memandang
     * urutan kata.
     *
     * Pencocokan longgar ini yang membuat "Epson Printer L3250" dari worksheet
     * accounting kena ke "Printer Epson L3250" di katalog — barang yang sama,
     * cuma urutan katanya tertukar. Yang dibandingkan himpunan katanya, jadi
     * hanya nama dengan kata yang PERSIS sama (bukan mirip) yang dianggap cocok.
     *
     * Kalau satu himpunan kata dimiliki lebih dari satu entri katalog, tidak ada
     * yang dipilih — menebak di situ berarti aset masuk ke jenis barang yang salah.
     *
     * @return array{id: ?int, ambigu: array<int, string>}
     */
    private function cariBarangAset(string $nama): array
    {
        $this->muatKatalog();

        $persis = $this->katalogPersis[self::normalisasiTeks($nama)] ?? null;

        if ($persis !== null) {
            return ['id' => $persis, 'ambigu' => []];
        }

        $kandidat = $this->katalogHimpunanKata[self::kunciHimpunanKata($nama)] ?? [];

        if (count($kandidat) === 1) {
            return ['id' => $kandidat[0], 'ambigu' => []];
        }

        return [
            'id' => null,
            'ambigu' => array_map(fn (int $id) => $this->katalogNama[$id], $kandidat),
        ];
    }

    /**
     * Terjemahkan jabatan di kolom PIC jadi satu orang penanggung jawab.
     *
     * Jabatan dipecah dua bagian: kata tingkat ("Leader", "Manajer") menentukan
     * job_level, sisanya adalah unitnya. Unit dicocokkan ke job position ATAU
     * divisi — keduanya perlu karena datanya tidak konsisten: "Manajer Hardware"
     * ketemu lewat divisi (Engineer Manager di divisi Hardware), sedangkan
     * "Leader Supply Chain" ketemu lewat job position (Purchasing).
     *
     * Pencocokannya persis, bukan kemiripan, ditambah daftar alias untuk unit yang
     * namanya memang berbeda. Sengaja tidak menebak: yang tidak ketemu dilaporkan,
     * bukan diisi dengan orang yang paling mendekati.
     *
     * Kalau hasilnya bukan tepat satu orang, penanggung jawabnya dibiarkan kosong
     * dan alasannya dicatat — import TIDAK digagalkan. Menghentikan seluruh berkas
     * gara-gara satu jabatan tak terpetakan itu yang bikin alur lama menyusahkan.
     *
     * @return array{user: ?User, alasan: ?string}
     */
    private function penanggungJawabDariJabatan(string $jabatan): array
    {
        $kunci = self::normalisasiTeks($jabatan);

        if (isset($this->cacheJabatan[$kunci])) {
            return $this->cacheJabatan[$kunci];
        }

        $hasil = $this->hitungPenanggungJawab($jabatan);
        $this->cacheJabatan[$kunci] = $hasil;

        return $hasil;
    }

    /**
     * @return array{user: ?User, alasan: ?string}
     */
    private function hitungPenanggungJawab(string $jabatan): array
    {
        $level = null;
        $sisa = [];

        foreach (self::pecahKata($jabatan) as $kata) {
            if ($level === null && isset(self::TINGKAT_JABATAN[$kata])) {
                $level = self::TINGKAT_JABATAN[$kata];

                continue;
            }

            $sisa[] = $kata;
        }

        if ($level === null) {
            return ['user' => null, 'alasan' => "'{$jabatan}' tidak memuat tingkat jabatan (Leader/Manajer/Direktur)"];
        }

        if ($sisa === []) {
            return ['user' => null, 'alasan' => "'{$jabatan}' hanya memuat tingkat jabatan, tanpa unit"];
        }

        $unit = implode(' ', $sisa);
        $unit = self::ALIAS_UNIT[$unit] ?? $unit;

        $kandidat = User::with('dataJobPosition', 'dataOrganization')
            ->where('status', 'Aktif')
            ->where('job_level', $level)
            ->where(function ($query) use ($unit) {
                $query->whereHas('dataJobPosition', fn ($sub) => $sub->whereRaw('LOWER(TRIM(nama)) = ?', [$unit]))
                    ->orWhereHas('dataOrganization', fn ($sub) => $sub->whereRaw('LOWER(TRIM(nama)) = ?', [$unit]));
            })
            ->get();

        if ($kandidat->count() === 1) {
            return ['user' => $kandidat->first(), 'alasan' => null];
        }

        if ($kandidat->isEmpty()) {
            return ['user' => null, 'alasan' => "'{$jabatan}' tidak menemukan user aktif dengan job level {$level} di '{$unit}'"];
        }

        return [
            'user' => null,
            'alasan' => "'{$jabatan}' menemukan ".$kandidat->count().' user ('
                .$kandidat->pluck('name')->implode(', ').') — harus tepat satu',
        ];
    }

    /**
     * Cari ruangan: kode/nama persis dulu, baru pencocokan kata.
     *
     * Worksheet accounting sering menulis nama ruangan yang lebih pendek daripada
     * nama resminya — "Ruang HRD" atau "Ruang Corporate Service" untuk ruangan
     * yang terdaftar sebagai "Ruang HRD & Corporate Service". Karena itu subset
     * kata diterima DUA ARAH: nilai Excel boleh lebih pendek maupun lebih panjang
     * daripada nama terdaftar.
     *
     * Syaratnya tetap tepat satu kandidat. "Ruang" saja cocok ke sebelas ruangan
     * dan "Ruang Meeting Marketing" cocok ke dua — keduanya ditolak, bukan ditebak,
     * karena aset yang salah ruangan tidak akan ketahuan sampai ada opname lagi.
     *
     * Ruangan sengaja TIDAK dibuat otomatis seperti barang: daftarnya kecil,
     * terkendali, dan salah tulis yang jadi ruangan baru akan memecah laporan
     * penempatan aset tanpa ada yang menyadarinya.
     *
     * @return array{id: ?int, kandidat: array<int, string>}
     */
    private function cariRuangan(string $nilai): array
    {
        $this->muatRuangan();

        $norm = self::normalisasiTeks($nilai);

        if (isset($this->ruanganPersis[$norm])) {
            return ['id' => $this->ruanganPersis[$norm], 'kandidat' => []];
        }

        $kata = self::pecahKata($nilai);

        if ($kata === []) {
            return ['id' => null, 'kandidat' => []];
        }

        $cocok = [];

        foreach ($this->ruanganKata as $id => $kataRuangan) {
            // Salah satu arah cukup: nilai Excel bagian dari nama terdaftar,
            // atau nama terdaftar bagian dari nilai Excel.
            if (array_diff($kata, $kataRuangan) === [] || array_diff($kataRuangan, $kata) === []) {
                $cocok[] = $id;
            }
        }

        if (count($cocok) === 1) {
            return ['id' => $cocok[0], 'kandidat' => []];
        }

        return [
            'id' => null,
            'kandidat' => array_map(fn (int $id) => $this->ruanganNama[$id], $cocok),
        ];
    }

    /**
     * Ruangan dimuat sekali per import: kode & nama untuk cocok persis, plus
     * pecahan katanya untuk pencocokan subset.
     */
    private function muatRuangan(): void
    {
        if ($this->ruanganNama !== null) {
            return;
        }

        $this->ruanganNama = [];
        $this->ruanganPersis = [];
        $this->ruanganKata = [];

        foreach (Ruangan::get(['id', 'kode_ruangan', 'nama_ruangan']) as $ruangan) {
            $this->ruanganNama[$ruangan->id] = $ruangan->nama_ruangan;
            $this->ruanganPersis[self::normalisasiTeks($ruangan->kode_ruangan)] = $ruangan->id;
            $this->ruanganPersis[self::normalisasiTeks($ruangan->nama_ruangan)] = $ruangan->id;
            $this->ruanganKata[$ruangan->id] = self::pecahKata($ruangan->nama_ruangan);
        }
    }

    /**
     * Daftarkan barang baru ke katalog, untuk nama yang belum ada.
     *
     * `barang_aset` mewajibkan kode_barang (unik), jenis_bahan_id, dan unit_id,
     * padahal worksheet opname tidak memuat ketiganya. Nilai bawaannya diambil
     * dari data yang sudah ada, bukan dikarang:
     *
     *   jenis bahan - kategori "ASET" yang sudah dipakai entri aset lain
     *   unit        - "Pcs", dipakai 383 dari 384 barang di katalog
     *   kode        - dari kata pertama nama + urutan, mengikuti gaya yang sudah
     *                 ada (TAB-001, LAPTOP-010, UPS-006)
     *
     * Kategorinya sengaja kasar. Menebak kategori dari nama ("mengandung Laptop
     * berarti LAPTOP") akan salah tanpa ada yang menyadarinya, sedangkan kategori
     * "ASET" jelas menandakan entri ini perlu dirapikan — dan nama-namanya
     * dilaporkan di ringkasan supaya tidak terlewat.
     */
    private function buatBarangAset(string $nama): int
    {
        $nama = Str::squish($nama);

        $jenisBahanId = $this->idAcuan('jenis_bahan', 'ASET');
        $unitId = $this->idAcuan('unit', 'Pcs');

        $barang = BarangAset::create([
            'kode_barang' => $this->kodeBarangBaru($nama),
            'nama_barang' => $nama,
            'jenis_bahan_id' => $jenisBahanId,
            'unit_id' => $unitId,
        ]);

        // Katalog dalam memori ikut diperbarui, supaya nama yang sama muncul
        // berkali-kali dalam satu berkas tidak membuat entri ganda.
        $this->katalogNama[$barang->id] = $nama;
        $this->katalogPersis[self::normalisasiTeks($nama)] = $barang->id;
        $this->katalogHimpunanKata[self::kunciHimpunanKata($nama)][] = $barang->id;

        $this->barangDibuat[] = $nama;

        return $barang->id;
    }

    /**
     * Kode barang yang belum terpakai. kode_barang bersifat unik, jadi urutannya
     * dinaikkan sampai ketemu yang kosong — bukan sekadar menghitung jumlah baris,
     * karena kode lama bisa saja sudah memakai angka di tengah.
     */
    private function kodeBarangBaru(string $nama): string
    {
        $kata = self::pecahKata($nama);
        $awalan = strtoupper(substr($kata[0] ?? 'ASET', 0, 12));

        if ($awalan === '') {
            $awalan = 'ASET';
        }

        $this->kodeTerpakai ??= BarangAset::pluck('kode_barang')
            ->map(fn ($kode) => strtoupper(trim((string) $kode)))
            ->all();

        for ($urut = 1; $urut <= 9999; $urut++) {
            $kode = $awalan.'-'.str_pad((string) $urut, 3, '0', STR_PAD_LEFT);

            if (! in_array($kode, $this->kodeTerpakai, true)) {
                $this->kodeTerpakai[] = $kode;

                return $kode;
            }
        }

        throw new \RuntimeException("Tidak bisa membuat kode barang untuk '{$nama}': awalan '{$awalan}' sudah terpakai sampai 9999.");
    }

    /**
     * Id acuan berdasarkan namanya, bukan angka yang ditulis langsung di kode —
     * id di setiap lingkungan bisa berbeda.
     */
    private function idAcuan(string $tabel, string $nama): int
    {
        $id = DB::table($tabel)->whereRaw('LOWER(TRIM(nama)) = ?', [self::normalisasiTeks($nama)])->value('id');

        if (! $id) {
            throw new \RuntimeException("Data acuan '{$nama}' tidak ada di tabel {$tabel}. "
                ."Tambahkan dulu sebelum import bisa mendaftarkan barang baru secara otomatis.");
        }

        return (int) $id;
    }

    /**
     * Kata-kata sebuah nama, diurutkan dan digabung — jadi penanda yang sama
     * untuk "Epson Printer L3250" maupun "Printer Epson L3250".
     */
    private static function kunciHimpunanKata(?string $nama): string
    {
        $kata = self::pecahKata($nama);
        sort($kata);

        return implode(' ', $kata);
    }

    /**
     * @return array<int, string>
     */
    private static function pecahKata(?string $nama): array
    {
        return preg_split('/[^a-z0-9]+/', self::normalisasiTeks($nama), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Nama katalog yang paling mirip, untuk disebut di pesan error.
     *
     * Skornya rasio kata yang sama terhadap nama terpanjang, supaya nama katalog
     * yang panjang tidak otomatis menang hanya karena banyak katanya.
     *
     * @return array<int, string>
     */
    private function saranNamaBarang(string $nama, int $maks = 3): array
    {
        $this->muatKatalog();

        $kata = self::pecahKata($nama);

        if ($kata === []) {
            return [];
        }

        $skor = [];

        foreach ($this->katalogNama as $namaKatalog) {
            $kataKatalog = self::pecahKata($namaKatalog);
            $sama = count(array_intersect($kata, $kataKatalog));

            if ($sama === 0) {
                continue;
            }

            $skor[$namaKatalog] = $sama / max(count($kata), count($kataKatalog));
        }

        arsort($skor);

        return array_slice(array_keys($skor), 0, $maks);
    }

    /**
     * Ringkasan hasil import, termasuk apa yang TIDAK terisi.
     *
     * Nilai yang gagal dicocokkan disebut namanya — tanpa itu pengguna cuma tahu
     * ada kolom yang kosong tanpa tahu harus membetulkan apa di worksheet-nya.
     */
    /**
     * Hitungan singkat untuk ditampilkan di layar.
     *
     * Hanya angkanya. Rinciannya dipisah ke catatan() supaya pesan di layar tidak
     * jadi paragraf panjang yang justru tidak terbaca.
     */
    public function ringkasan(): string
    {
        return "{$this->jumlahBaru} aset baru, {$this->jumlahDiperbarui} diperbarui, {$this->jumlahTidakBerubah} tidak berubah.";
    }

    /**
     * Rincian hasil import, untuk dicatat di log aktivitas.
     *
     * Isinya hal-hal yang tidak boleh hilang tapi tidak perlu memenuhi layar:
     * entri katalog yang dibuat otomatis, terjemahan jabatan jadi orang, dan
     * kolom yang gagal terisi. Semuanya kesimpulan atau perubahan yang dibuat
     * sistem sendiri, jadi harus tetap bisa ditelusuri walau tidak ditampilkan.
     *
     * Mengembalikan string kosong kalau tidak ada yang perlu dicatat.
     */
    public function catatan(): string
    {
        $catatan = [];

        // Kategori entri baru sengaja kasar ("ASET"). Kalau tidak tercatat, katalog
        // perlahan terisi barang tak berkategori tanpa ada yang menyadarinya.
        if ($this->barangDibuat !== []) {
            $daftar = array_slice($this->barangDibuat, 0, 5);
            $sisa = count($this->barangDibuat) - count($daftar);

            $catatan[] = count($this->barangDibuat).' barang baru didaftarkan ke katalog: '
                .implode(', ', $daftar)
                .($sisa > 0 ? " (dan {$sisa} lainnya)" : '')
                .'. Kategorinya masih "ASET" — rapikan di menu Barang Aset.';
        }

        // Terjemahan jabatan -> orang adalah kesimpulan sistem, bukan isi berkas.
        // Wajib tercatat supaya bisa diperiksa, bukan dipercaya diam-diam.
        if ($this->jabatanTerpetakan !== []) {
            $daftar = [];

            foreach ($this->jabatanTerpetakan as $jabatan => $nama) {
                $daftar[] = "{$jabatan} → {$nama}";
            }

            $catatan[] = 'Penanggung jawab dari jabatan: '.implode('; ', $daftar).'.';
        }

        if ($this->jabatanGagal !== []) {
            $catatan[] = 'Jabatan yang tidak bisa ditentukan: '.implode('; ', $this->jabatanGagal).'.';
        }

        if ($this->jumlahTanpaPenanggungJawab > 0) {
            $catatan[] = "{$this->jumlahTanpaPenanggungJawab} baris tanpa penanggung jawab.";
        }

        if ($this->jumlahTanpaPemegang > 0) {
            $catatan[] = "{$this->jumlahTanpaPemegang} baris tanpa PIC pemegang.";
        }

        if ($this->nilaiTidakDikenal !== []) {
            $daftar = array_slice($this->nilaiTidakDikenal, 0, 5);
            $sisa = count($this->nilaiTidakDikenal) - count($daftar);

            $catatan[] = 'Tidak cocok dengan nama user: '.implode(', ', $daftar)
                .($sisa > 0 ? " (dan {$sisa} lainnya)" : '')
                .'. Lengkapi lewat form edit aset.';
        }

        return implode(' ', $catatan);
    }
}
