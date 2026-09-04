<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menyiapkan `perbaikan_data` jadi tiket yang bisa dipakai sebagai dasar audit.
 *
 * Tiga perubahan, semuanya karena kolom yang ada sekarang tidak cukup:
 *
 * - `user_id`. Pengaju sekarang disimpan sebagai string nama, dan approval-nya
 *   mencari orangnya dengan `User::where('name', $data->pengaju)`. Nama bisa
 *   berubah dan bisa kembar, jadi itu tidak layak jadi dasar audit.
 * - `catatan` dilebarkan ke TEXT. Sekarang varchar(255); alasan koreksi yang
 *   terpotong di tengah tidak ada gunanya buat yang memeriksanya nanti.
 * - `dibatalkan_pada`. Menggantikan penghapusan tiket: tiket yang bisa dihapus
 *   berarti jejak yang bisa dihapus.
 *
 * Kolom `pengaju` yang lama dibiarkan. Baris lama hanya punya nama di sana, dan
 * menebak user_id-nya dari string nama justru menciptakan data yang tampak pasti
 * padahal hasil dugaan.
 *
 * Catatan kompatibilitas, sama seperti migration satuan bahan: introspeksi skema
 * Laravel 11 (`Schema::hasColumn`, `->change()`) membaca kolom
 * `generation_expression` yang belum ada di MySQL/MariaDB versi server produksi,
 * jadi pemeriksaan kolom memakai query information_schema seadanya dan perubahan
 * tipe memakai ALTER TABLE mentah.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->tanpaModeTanggalKetat(function () {
            if (! $this->punyaKolom('perbaikan_data', 'user_id')) {
                DB::statement('alter table `perbaikan_data` add `user_id` bigint unsigned null');
            }

            if (! $this->punyaKolom('perbaikan_data', 'dibatalkan_pada')) {
                DB::statement('alter table `perbaikan_data` add `dibatalkan_pada` datetime null');
            }

            if ($this->punyaKolom('perbaikan_data', 'catatan')) {
                DB::statement('alter table `perbaikan_data` modify `catatan` text null');
            }
        });
    }

    public function down(): void
    {
        $this->tanpaModeTanggalKetat(function () {
            if ($this->punyaKolom('perbaikan_data', 'user_id')) {
                DB::statement('alter table `perbaikan_data` drop `user_id`');
            }

            if ($this->punyaKolom('perbaikan_data', 'dibatalkan_pada')) {
                DB::statement('alter table `perbaikan_data` drop `dibatalkan_pada`');
            }

            if ($this->punyaKolom('perbaikan_data', 'catatan')) {
                DB::statement('alter table `perbaikan_data` modify `catatan` varchar(255) null');
            }
        });
    }

    /**
     * Jalankan perubahan skema tanpa mode tanggal ketat, lalu kembalikan lagi.
     *
     * ALTER TABLE membangun ulang tabelnya dan memvalidasi ulang setiap baris
     * lama. Sebagian tabel di produksi masih menyimpan tanggal nol yang dulu
     * diterima tapi ditolak sql_mode sekarang, sehingga penambahan kolom yang
     * tidak menyentuh tanggal pun ikut gagal. Yang dilonggarkan hanya sesi ini
     * dan dikembalikan di blok finally, termasuk kalau ALTER-nya gagal.
     */
    private function tanpaModeTanggalKetat(callable $aksi): void
    {
        $modeAsli = (string) DB::selectOne('select @@session.sql_mode as mode')->mode;

        $modeLonggar = implode(',', array_filter(
            array_map('trim', explode(',', $modeAsli)),
            static fn ($mode) => $mode !== '' && ! in_array($mode, [
                'NO_ZERO_DATE',
                'NO_ZERO_IN_DATE',
                'STRICT_TRANS_TABLES',
                'STRICT_ALL_TABLES',
            ], true)
        ));

        $pdo = DB::connection()->getPdo();
        DB::unprepared('set session sql_mode = ' . $pdo->quote($modeLonggar));

        try {
            $aksi();
        } finally {
            DB::unprepared('set session sql_mode = ' . $pdo->quote($modeAsli));
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
};
