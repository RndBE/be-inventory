<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ledger stok siap menyimpan bahan batangan dalam cm.
 *
 * Dua perubahan, dan keduanya saling bergantung:
 *
 * `panjang_standar` — salinan panjang satu batang saat lot dibuat. Dipakai
 * untuk menerjemahkan angka cm kembali jadi batang di tampilan. Nilainya
 * dibekukan di baris lot, bukan dibaca dari tabel `bahan`, supaya satu kali
 * orang mengedit panjang standar (mis. pipa 6 m diganti 4 m) tidak mengubah
 * arti seluruh lot lama sekaligus.
 *
 * `unit_price` diperlebar jadi empat angka desimal. Untuk bahan batangan yang
 * qty-nya cm, harga yang tersimpan adalah harga per cm, jadi pipa Rp 175.000
 * per batang 600 cm tercatat 291,6667. Alternatifnya menyimpan harga per batang
 * dan memprorata di tiap tempat pemakaian, tapi `qty * unit_price` tersebar di
 * puluhan titik (BahanKeluarController saja belasan) dan semuanya akan salah
 * 600 kali kalau harganya per batang sedangkan qty-nya cm. Dengan harga per cm,
 * seluruh perhitungan lama tetap benar tanpa disentuh.
 *
 * Empat angka desimal dipilih supaya galat pembulatannya tinggal sekitar
 * Rp 0,02 per batang. Subtotal pembelian sendiri tidak dihitung dari harga per
 * cm melainkan dari jumlah batang dikali harga per batang, jadi angka yang
 * masuk pembukuan tetap eksak.
 *
 * Catatan tipe asal: migration pembuatan tabel menulis `integer`, tapi di
 * database produksi kolomnya sudah decimal(20,2) — pernah diubah langsung, di
 * luar migration. Jadi perubahan ini menukar dua digit bulat (18 jadi 11)
 * dengan dua digit desimal. Harga satuan tertinggi yang tercatat masih delapan
 * digit, jauh di bawah batas baru, sehingga tidak ada baris yang terpotong.
 * Lihat down() untuk konsekuensinya saat rollback.
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
    public function up(): void
    {
        if (! $this->punyaKolom('purchase_details', 'panjang_standar')) {
            DB::statement('alter table `purchase_details` add `panjang_standar` int null after `bahan_id`');
        }

        DB::statement('alter table `purchase_details` modify `unit_price` decimal(15,4) not null');
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


    /**
     * Dikembalikan ke decimal(20,2), bukan ke integer seperti tertulis di
     * migration pembuatan tabelnya.
     *
     * Tipe di database produksi sudah decimal(20,2) — kolomnya pernah diubah
     * langsung, di luar migration — jadi `integer` di sini bukan mengembalikan
     * keadaan semula melainkan memotong desimal setiap baris, termasuk nilai
     * sen yang sudah tercatat jauh sebelum fitur ini ada.
     *
     * Dua angka desimal yang tersisa tetap hilang untuk bahan batangan, karena
     * harga per cm memang butuh empat. Rollback ini hanya aman kalau belum ada
     * lot batangan yang tercatat; kalau sudah, kembalikan dari backup.
     */
    public function down(): void
    {
        DB::statement('alter table `purchase_details` modify `unit_price` decimal(20,2) not null');

        if ($this->punyaKolom('purchase_details', 'panjang_standar')) {
            DB::statement('alter table `purchase_details` drop column `panjang_standar`');
        }
    }
};
