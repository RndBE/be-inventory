<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ubah status baris perubahan dari `dieksekusi` jadi `dicatat`.
 *
 * Namanya lahir waktu modul Perbaikan Data masih ikut menulis koreksinya
 * sendiri. Sekarang tidak: perubahan datanya dikerjakan tim software langsung
 * di database, dan yang dilakukan aplikasi menulis jejaknya ke
 * `audit_perubahan_data` lalu menutup tiketnya.
 *
 * Status yang mengaku "dieksekusi" karena itu menyesatkan justru di modul yang
 * ada demi kejujuran catatan: yang membacanya akan menyimpulkan datanya sudah
 * berubah, padahal yang berubah baru catatannya. Baris lama ikut diubah supaya
 * halaman lama dan halaman baru tidak menampilkan dua istilah untuk keadaan
 * yang sama.
 *
 * Baris berstatus `gagal` dan `menunggu` tidak disentuh: artinya tidak berubah.
 *
 * Catatan kompatibilitas, sama seperti migration perbaikan data lainnya:
 * introspeksi skema Laravel 11 membaca kolom `generation_expression` yang belum
 * ada di MySQL/MariaDB versi server produksi, jadi perubahannya memakai query
 * mentah, bukan Schema builder.
 */
return new class extends Migration
{
    private const TABEL = 'perbaikan_data_target';

    public function up(): void
    {
        DB::table(self::TABEL)->where('status', 'dieksekusi')->update(['status' => 'dicatat']);
    }

    public function down(): void
    {
        DB::table(self::TABEL)->where('status', 'dicatat')->update(['status' => 'dieksekusi']);
    }
};
