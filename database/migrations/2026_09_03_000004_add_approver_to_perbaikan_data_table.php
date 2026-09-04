<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Simpan siapa yang menyetujui pengajuan perbaikan data, dan kapan.
 *
 * Sampai sekarang `perbaikan_data` hanya menyimpan `status` — Disetujui,
 * Ditolak, dan seterusnya — tanpa menyebut siapa yang menyetelnya. Identitas
 * approver tidak ada di mana pun.
 *
 * Akibatnya, `audit_perubahan_data.approver_id` selama ini diisi dari
 * `approval_kendalas`, satu-satunya tabel yang kebetulan menyimpan `user_id`
 * pada alur persetujuan. Itu bukan tabel persetujuan melainkan tabel CATATAN
 * KENDALA, dan barisnya DIHAPUS begitu kendalanya dikosongkan
 * (ApprovalKendala::saveFor). Jadi approver hanya tercatat kalau dia kebetulan
 * menulis catatan kendala, dan catatannya belum dihapus.
 *
 * Buktinya sudah ada di data: tiga baris audit menunjuk approver sementara
 * `approval_kendalas` untuk modul ini kosong. Jejak yang sumbernya sudah lenyap
 * persis jenis jejak yang menyesatkan orang yang membacanya setahun kemudian.
 *
 * Dua kolom di sini yang menggantikannya, diisi dari pengguna yang menekan
 * tombol persetujuan.
 *
 * Tanpa foreign key, mengikuti tabel audit: baris persetujuan harus tetap
 * terbaca walau usernya dihapus, dan jejak yang ikut terhapus bersama subjeknya
 * bukan jejak.
 *
 * Catatan kompatibilitas, sama seperti migration perbaikan data lainnya:
 * introspeksi skema Laravel 11 membaca kolom `generation_expression` yang belum
 * ada di MySQL/MariaDB versi server produksi, jadi pemeriksaan kolom memakai
 * query information_schema seadanya dan perubahannya memakai ALTER TABLE mentah.
 */
return new class extends Migration
{
    private const TABEL = 'perbaikan_data';

    public function up(): void
    {
        $kolom = [
            'approver_id' => 'bigint unsigned null',
            'tgl_approve' => 'datetime null',
        ];

        foreach ($kolom as $nama => $tipe) {
            if (! $this->punyaKolom(self::TABEL, $nama)) {
                DB::statement('alter table `' . self::TABEL . '` add `' . $nama . '` ' . $tipe);
            }
        }
    }

    public function down(): void
    {
        foreach (['approver_id', 'tgl_approve'] as $nama) {
            if ($this->punyaKolom(self::TABEL, $nama)) {
                DB::statement('alter table `' . self::TABEL . '` drop `' . $nama . '`');
            }
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
