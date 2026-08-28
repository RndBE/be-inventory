<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Harga satuan di tabel pergerakan bahan diperlebar jadi decimal.
 *
 * Untuk bahan batangan, harga satuan ledger adalah harga per cm — pipa
 * Rp 175.000 per batang 600 cm menjadi 291,6667. Kolom integer akan
 * memotongnya jadi 291, dan pemotongan itu ikut terbawa: retur bahan dari
 * proyek membuat lot stok baru memakai harga dari barisnya sendiri, jadi
 * nilainya menyusut sekitar 0,23% setiap kali bahan berputar keluar-masuk.
 *
 * `bahan_retur_details.unit_price` tidak pernah dibuat lewat migration — kolom
 * itu ditambahkan langsung di database — sehingga tipenya tidak bisa
 * dipastikan dari berkas mana pun di repo ini. Karena itu setiap perubahan
 * dijaga dengan hasColumn, dan tabel yang kolomnya tidak ada dilewati saja.
 *
 * Kolomnya dijadikan nullable karena alasan yang sama: nullability aslinya
 * tidak diketahui, dan `change()` di Laravel 11 menulis ulang seluruh definisi
 * kolom — kalau di sini ditulis NOT NULL sedangkan aslinya nullable, baris lama
 * yang harganya kosong akan menggagalkan migration di tengah jalan. Melonggarkan
 * ke nullable tidak pernah merusak baris maupun insert yang sudah ada.
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
    private const TABEL = [
        'bahan_retur_details',
        'bahan_rusak_details',
    ];

    public function up(): void
    {
        foreach (self::TABEL as $tabel) {
            if (! $this->punyaTabel($tabel) || ! $this->punyaKolom($tabel, 'unit_price')) {
                continue;
            }

            DB::statement("alter table `{$tabel}` modify `unit_price` decimal(15,4) null");
        }
    }

    public function down(): void
    {
        foreach (self::TABEL as $tabel) {
            if (! $this->punyaTabel($tabel) || ! $this->punyaKolom($tabel, 'unit_price')) {
                continue;
            }

            DB::statement("alter table `{$tabel}` modify `unit_price` int null");
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
