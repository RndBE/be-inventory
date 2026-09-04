<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Penanda apakah sebuah koreksi ikut menyesuaikan stok.
 *
 * Sampai sekarang setiap baris audit memperlakukan koreksi sebagai satu jenis:
 * angka diubah, dan sistem dianggap konsisten sesudahnya. Itu benar untuk
 * kolom lot, yang `sisa` dan `sub_total`-nya ikut dihitung ulang dan hanya
 * boleh dikoreksi selama lotnya belum tersentuh.
 *
 * Sekarang qty dan harga satuan juga bisa dikoreksi pada baris di luar alur
 * lot: baris konsumsi, baris retur, baris QC. Untuk baris-baris itu angkanya
 * berubah tapi lot yang dulu terpotong TIDAK bisa ikut dikembalikan, karena
 * catatan alokasinya hanya menyimpan qty dan harga tanpa id lot. Koreksinya
 * tetap dijalankan — itu keputusan yang diambil sadar — tapi konsekuensinya
 * harus terlihat.
 *
 * Kolom ini yang membuatnya terlihat. Bernilai salah berarti: dokumennya sudah
 * benar, stoknya belum, dan selisihnya harus diselesaikan lewat Stock Opname.
 * Tanpa kolom ini pertanyaan "kenapa stok tidak cocok" tidak punya jawaban di
 * tabel audit, dan itu justru pertanyaan yang paling mungkin ditanyakan.
 *
 * Nullable, bukan default true: baris lama ditulis sebelum pembedaan ini ada,
 * dan menandainya "stok disesuaikan" berarti mengarang kepastian yang tidak
 * pernah diperiksa. NULL berarti tidak diketahui, dan itu memang keadaannya.
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
        if (! $this->punyaKolom(self::TABEL, 'stok_disesuaikan')) {
            DB::statement(
                'alter table `' . self::TABEL . '` add `stok_disesuaikan` tinyint(1) null'
            );
        }
    }

    public function down(): void
    {
        if ($this->punyaKolom(self::TABEL, 'stok_disesuaikan')) {
            DB::statement('alter table `' . self::TABEL . '` drop `stok_disesuaikan`');
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
