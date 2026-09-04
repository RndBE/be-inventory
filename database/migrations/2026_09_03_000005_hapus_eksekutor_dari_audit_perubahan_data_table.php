<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cabut `eksekutor_id`, dan ubah arti benderanya jadi persetujuan sendiri.
 *
 * Tiga kolom pelaku dulu ada di tabel ini: pengaju, approver, eksekutor —
 * "siapa minta, siapa mengizinkan, siapa mengerjakan". Susunan itu masuk akal
 * ketika aplikasi yang mengerjakan perubahannya.
 *
 * Sekarang tidak lagi. Perubahan datanya dikerjakan tim software langsung di
 * database, dan yang tercatat sebagai "eksekutor" hanyalah orang yang menekan
 * tombol pencatatan — belum tentu yang mengetik SQL-nya. Kolom yang namanya
 * menjanjikan satu hal tapi isinya hal lain lebih buruk daripada kolom yang
 * tidak ada, apalagi di tabel yang tujuannya kejujuran catatan.
 *
 * `diajukan_sendiri` ikut berubah arti. Dulu membandingkan pengaju dengan
 * eksekutor; setelah eksekutor tidak ada, perbandingan yang tersisa — dan
 * sebenarnya yang lebih penting — pengaju dengan APPROVER. Orang yang
 * menyetujui permintaannya sendiri adalah kelemahan kontrol yang klasik,
 * sedangkan orang yang mencatat permintaannya sendiri bukan apa-apa.
 *
 * Karena artinya berubah, namanya ikut diubah jadi `disetujui_sendiri`.
 * Mempertahankan nama lama berarti kolom yang berbohong pada pembacanya.
 *
 * Nilai lama tidak dipindahkan: `diajukan_sendiri` yang ada sekarang menjawab
 * pertanyaan yang berbeda, jadi menyalinnya berarti mengarang jawaban untuk
 * pertanyaan yang belum pernah ditanyakan pada baris-baris itu.
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
        if ($this->punyaKolom(self::TABEL, 'eksekutor_id')) {
            DB::statement('alter table `' . self::TABEL . '` drop `eksekutor_id`');
        }

        if ($this->punyaKolom(self::TABEL, 'diajukan_sendiri')
            && ! $this->punyaKolom(self::TABEL, 'disetujui_sendiri')) {
            DB::statement(
                'alter table `' . self::TABEL . '`
                 change `diajukan_sendiri` `disetujui_sendiri` tinyint(1) not null default 0'
            );

            // Artinya berubah, jadi nilai lamanya dinolkan. Baris lama memang
            // tidak punya jawaban untuk pertanyaan yang baru.
            DB::table(self::TABEL)->update(['disetujui_sendiri' => 0]);
        }
    }

    public function down(): void
    {
        if ($this->punyaKolom(self::TABEL, 'disetujui_sendiri')
            && ! $this->punyaKolom(self::TABEL, 'diajukan_sendiri')) {
            DB::statement(
                'alter table `' . self::TABEL . '`
                 change `disetujui_sendiri` `diajukan_sendiri` tinyint(1) not null default 0'
            );
        }

        if (! $this->punyaKolom(self::TABEL, 'eksekutor_id')) {
            DB::statement('alter table `' . self::TABEL . '` add `eksekutor_id` bigint unsigned null');
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
