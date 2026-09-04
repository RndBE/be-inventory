<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cabut `stok_disesuaikan`. Kolomnya tidak pernah punya dua nilai.
 *
 * Ditambahkan beberapa saat sebelumnya, saat modul Perbaikan Data masih ikut
 * menulis koreksinya sendiri. Waktu itu kolom ini ada gunanya: sebagian koreksi
 * menyesuaikan lot terkait, sebagian tidak, dan pembaca halaman audit perlu
 * tahu yang mana.
 *
 * Sekarang aplikasi tidak menyentuh data yang dikoreksi sama sekali — semuanya
 * dikerjakan tim software langsung di database — jadi jawabannya selalu sama.
 * Kolom yang isinya selalu satu nilai tidak menjelaskan apa pun, tapi tetap
 * membuat pembacanya berhenti dan menduga-duga apa bedanya. Itu biaya tanpa
 * imbalan.
 *
 * Aman dijatuhkan: saat migration ini ditulis, nol dari empat baris audit yang
 * ada pernah terisi.
 *
 * Catatan kompatibilitas, sama seperti migration perbaikan data lainnya:
 * introspeksi skema Laravel 11 membaca kolom `generation_expression` yang belum
 * ada di MySQL/MariaDB versi server produksi, jadi pemeriksaan kolom memakai
 * query information_schema seadanya dan perubahannya memakai ALTER TABLE mentah.
 */
return new class extends Migration
{
    private const TABEL = 'audit_perubahan_data';

    public function up(): void
    {
        if ($this->punyaKolom(self::TABEL, 'stok_disesuaikan')) {
            DB::statement('alter table `' . self::TABEL . '` drop `stok_disesuaikan`');
        }
    }

    public function down(): void
    {
        if (! $this->punyaKolom(self::TABEL, 'stok_disesuaikan')) {
            DB::statement(
                'alter table `' . self::TABEL . '` add `stok_disesuaikan` tinyint(1) null'
            );
        }
    }

    private function punyaKolom(string $tabel, string $kolom): bool
    {
        return (int) DB::selectOne(
            'select count(*) as jumlah from information_schema.columns
             where table_schema = database() and table_name = ? and column_name = ?',
            [$tabel, $kolom]
        )->jumlah > 0;
    }
};
