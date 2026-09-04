<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kolom yang dibutuhkan surat penunjukan versi cetak.
 *
 * Sampai sekarang barisnya hanya menyimpan siapa ditunjuk dan kapan. Yang
 * dicetak menurut public/templates/SURAT PENUNJUKAN PERUBAHAN DATA.docx butuh
 * lebih dari itu:
 *
 * - `nomor_surat` beserta `nomor_urut` dan `tahun_surat`. Suratnya bernomor
 *   resmi ("008/ACC-PD/IX/2026") dan nomor itu yang dirujuk arsip Accounting.
 *   `kode_penunjukan` tidak bisa dipakai untuk itu: bentuknya PN-<timestamp>
 *   dan tidak berurutan. Urut dan tahunnya disimpan terpisah supaya nomor
 *   berikutnya bisa dihitung tanpa membedah string, dan supaya urutannya tetap
 *   utuh kalau pola nomornya di config nanti berubah.
 * - `tim_pemohon`. Suratnya menyebut asal permohonan ("dari Tim Supply Chain").
 *   Tidak bisa disimpulkan dari `perbaikan_data.pengaju` yang hanya berisi nama
 *   orang, dan tabel `users` di server ini tidak punya kolom tim yang bisa
 *   diandalkan.
 * - `perihal_perubahan`. Kalimat pembuka menyebut pokok perubahannya
 *   ("perubahan harga barang dan biaya pengiriman"). Itu rangkuman yang ditulis
 *   penerbit surat; menyusunnya otomatis dari nama kolom akan menghasilkan
 *   kalimat yang benar secara teknis tapi tidak terbaca sebagai surat.
 *
 * `nomor_surat` unique: dua surat bernomor sama membuat arsip Accounting tidak
 * bisa dirujuk. Kolomnya nullable supaya baris yang sudah ada — kalau ada —
 * tidak ikut gagal, dan karena MySQL mengizinkan banyak NULL pada kolom unique.
 *
 * Catatan kompatibilitas, sama seperti migration perbaikan data lainnya:
 * introspeksi skema Laravel 11 membaca kolom `generation_expression` yang belum
 * ada di MySQL/MariaDB versi server produksi, jadi pemeriksaan kolom memakai
 * query information_schema seadanya dan perubahannya memakai ALTER TABLE mentah.
 */
return new class extends Migration
{
    private const TABEL = 'perbaikan_data_penunjukan';

    public function up(): void
    {
        $kolom = [
            'nomor_surat' => 'varchar(100) null',
            'nomor_urut' => 'int unsigned null',
            'tahun_surat' => 'smallint unsigned null',
            'tim_pemohon' => 'varchar(255) null',
            'perihal_perubahan' => 'text null',
        ];

        foreach ($kolom as $nama => $tipe) {
            if (! $this->punyaKolom(self::TABEL, $nama)) {
                DB::statement('alter table `' . self::TABEL . '` add `' . $nama . '` ' . $tipe);
            }
        }

        if (! $this->punyaIndeks(self::TABEL, 'perbaikan_data_penunjukan_nomor_surat_unique')) {
            DB::statement(
                'alter table `' . self::TABEL . '`
                 add unique `perbaikan_data_penunjukan_nomor_surat_unique` (`nomor_surat`)'
            );
        }

        // Dipakai saat menghitung nomor berikutnya: max(nomor_urut) untuk tahun
        // berjalan. Tanpa indeks, setiap penerbitan surat memindai seluruh tabel.
        if (! $this->punyaIndeks(self::TABEL, 'perbaikan_data_penunjukan_tahun_surat_nomor_urut_index')) {
            DB::statement(
                'alter table `' . self::TABEL . '`
                 add index `perbaikan_data_penunjukan_tahun_surat_nomor_urut_index` (`tahun_surat`, `nomor_urut`)'
            );
        }
    }

    public function down(): void
    {
        if ($this->punyaIndeks(self::TABEL, 'perbaikan_data_penunjukan_nomor_surat_unique')) {
            DB::statement('alter table `' . self::TABEL . '` drop index `perbaikan_data_penunjukan_nomor_surat_unique`');
        }

        if ($this->punyaIndeks(self::TABEL, 'perbaikan_data_penunjukan_tahun_surat_nomor_urut_index')) {
            DB::statement('alter table `' . self::TABEL . '` drop index `perbaikan_data_penunjukan_tahun_surat_nomor_urut_index`');
        }

        foreach (['nomor_surat', 'nomor_urut', 'tahun_surat', 'tim_pemohon', 'perihal_perubahan'] as $nama) {
            if ($this->punyaKolom(self::TABEL, $nama)) {
                DB::statement('alter table `' . self::TABEL . '` drop `' . $nama . '`');
            }
        }
    }

    /**
     * Apakah tabel ini punya kolom tersebut. Menggantikan Schema::hasColumn().
     */
    private function punyaKolom(string $tabel, string $kolom): bool
    {
        return (int) DB::selectOne(
            'select count(*) as jumlah from information_schema.columns
             where table_schema = database() and table_name = ? and column_name = ?',
            [$tabel, $kolom]
        )->jumlah > 0;
    }

    private function punyaIndeks(string $tabel, string $indeks): bool
    {
        return (int) DB::selectOne(
            'select count(*) as jumlah from information_schema.statistics
             where table_schema = database() and table_name = ? and index_name = ?',
            [$tabel, $indeks]
        )->jumlah > 0;
    }
};
