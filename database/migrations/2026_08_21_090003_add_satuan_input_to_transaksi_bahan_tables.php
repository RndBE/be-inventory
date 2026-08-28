<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Jejak satuan yang dipilih user saat menginput transaksi bahan.
 *
 * Kolom `qty` di tabel-tabel ini tetap satu-satunya sumber kebenaran dan
 * selalu dalam satuan dasar (cm untuk bahan batangan). Dua kolom di sini hanya
 * merekam apa yang diketik orangnya — "5 batang" atau "40 cm" — supaya
 * tampilan riwayat dan cetakan bisa menampilkan angka yang sama dengan yang
 * dimasukkan, bukan hasil konversinya. Tidak ada perhitungan stok yang boleh
 * mengambil angka dari sini.
 *
 * Keduanya nullable karena baris lama tidak punya nilainya, dan bahan
 * non-batangan tidak perlu mengisinya.
 */
/**
 * Catatan kompatibilitas: introspeksi skema Laravel 11 tidak dipakai di sini.
 *
 * `Schema::hasColumn()`, `Schema::hasTable()`, dan `->change()` di Laravel 11
 * membaca `information_schema.columns` beserta kolom `generation_expression`,
 * yang baru ada sejak MySQL 5.7 dan MariaDB 10.2. Server produksi memakai versi
 * yang lebih tua, jadi migration ini langsung gagal di sana dengan
 * "Unknown column 'generation_expression' in 'field list'" - sebelum satu pun
 * ALTER dijalankan.
 *
 * Karena itu pemeriksaan kolom memakai query `information_schema` seadanya
 * (COUNT saja) dan perubahan tipe memakai ALTER TABLE mentah. Keduanya jalan di
 * versi lama maupun baru, dan tidak ada yang hilang: `change()` di Laravel pun
 * pada akhirnya menulis ALTER TABLE yang sama.
 */
return new class extends Migration
{
    /**
     * Tabel yang diubah, dan kolom acuan posisinya.
     *
     * Posisi kolom tidak memengaruhi apa pun secara fungsional, tapi
     * menempatkannya di sebelah kolom qty terkait membuat struktur tabelnya
     * lebih mudah dibaca saat ditelusuri langsung di database.
     */
    private const TABEL = [
        'pembelian_bahan_details' => 'qty',
        'bahan_keluar_details' => 'qty',
        'pengambilan_bahan_details' => 'qty',
        'bahan_retur_details' => 'qty',
        'bahan_rusak_details' => 'qty',
        'stock_opname_details' => 'tersedia_fisik',
        'qc_bahan_masuk_details' => 'fisik_baik',
    ];

    public function up(): void
    {
        foreach (self::TABEL as $tabel => $setelah) {
            if (! $this->punyaTabel($tabel)) {
                continue;
            }

            if (! $this->punyaKolom($tabel, 'qty_input')) {
                DB::statement("alter table `{$tabel}` add `qty_input` decimal(15,2) null after `{$setelah}`");
            }

            if (! $this->punyaKolom($tabel, 'satuan_input')) {
                DB::statement("alter table `{$tabel}` add `satuan_input` varchar(20) null after `{$setelah}`");
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABEL) as $tabel) {
            if (! $this->punyaTabel($tabel)) {
                continue;
            }

            foreach (['qty_input', 'satuan_input'] as $kolom) {
                if ($this->punyaKolom($tabel, $kolom)) {
                    DB::statement("alter table `{$tabel}` drop column `{$kolom}`");
                }
            }
        }
    }

    /**
     * Apakah tabel ini punya kolom tersebut.
     *
     * Menggantikan Schema::hasColumn() - lihat catatan kompatibilitas di atas.
     */
    private function punyaKolom(string $tabel, string $kolom): bool
    {
        return (int) DB::selectOne(
            'select count(*) as jumlah from information_schema.columns
             where table_schema = database() and table_name = ? and column_name = ?',
            [$tabel, $kolom]
        )->jumlah > 0;
    }

    /**
     * Apakah tabelnya ada. Menggantikan Schema::hasTable().
     */
    private function punyaTabel(string $tabel): bool
    {
        return (int) DB::selectOne(
            'select count(*) as jumlah from information_schema.tables
             where table_schema = database() and table_name = ?',
            [$tabel]
        )->jumlah > 0;
    }

};
