<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Alasan koreksi, disimpan per baris perubahan.
 *
 * Sebelum ini kolom `alasan` di `audit_perubahan_data` hanya bisa diisi dari
 * `perbaikan_data.catatan`, dan kolom itu tidak layak jadi sumbernya: approval
 * mengosongkannya untuk setiap status selain 'Ditolak', sehingga hampir semua
 * baris audit akan berisi alasan generik "Perbaikan data PD-xxxx". Audit dengan
 * alasan yang sama di setiap baris tidak menjawab apa pun.
 *
 * Diletakkan per baris, bukan per tiket, karena satu pengajuan bisa mengoreksi
 * beberapa kolom dengan sebab yang berbeda — nominal salah ketik dan keterangan
 * yang tertukar bisa datang dalam satu tiket.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->punyaKolom('perbaikan_data_target', 'alasan')) {
            DB::statement('alter table `perbaikan_data_target` add `alasan` text null');
        }
    }

    public function down(): void
    {
        if ($this->punyaKolom('perbaikan_data_target', 'alasan')) {
            DB::statement('alter table `perbaikan_data_target` drop `alasan`');
        }
    }

    /**
     * Apakah tabel ini punya kolom tersebut.
     *
     * Memakai query information_schema seadanya, bukan Schema::hasColumn():
     * introspeksi skema Laravel 11 ikut membaca kolom `generation_expression`
     * yang belum ada di MySQL/MariaDB versi server produksi.
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
