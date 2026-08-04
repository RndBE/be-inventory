<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jumlah aset berdefault 1 dan tidak boleh kosong lagi.
 *
 * Worksheet opname accounting tidak punya kolom jumlah — satu baris di sana
 * berarti satu unit aset, dibedakan lewat nomor asetnya. Akibatnya aset hasil
 * import tersimpan dengan jumlah NULL, lalu tercetak kosong di dokumen dan tidak
 * bisa dijumlahkan di laporan.
 *
 * Nilai 1 aman sebagai default: satu baris rekap aset memang mewakili satu unit
 * yang bernomor sendiri. Data lama membenarkan itu — 43 aset yang sudah ada
 * semuanya bernilai 1, dan tidak ada satu pun yang 0.
 *
 * Dijadikan NOT NULL supaya kekosongan ini tidak bisa terulang dari jalur mana
 * pun. Semua jalur tulis (form tambah/edit dan import) sudah dinormalkan lebih
 * dulu, jadi tidak ada yang mengirim null lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Diisi lebih dulu: kolom tidak bisa dijadikan NOT NULL selama masih ada
        // baris bernilai NULL.
        DB::table('rekap_aset')->whereNull('jumlah_aset')->update(['jumlah_aset' => 1]);

        // 0 juga tidak bermakna untuk unit aset — dirapikan sekalian.
        DB::table('rekap_aset')->where('jumlah_aset', 0)->update(['jumlah_aset' => 1]);

        Schema::table('rekap_aset', function (Blueprint $table) {
            $table->integer('jumlah_aset')->default(1)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('rekap_aset', function (Blueprint $table) {
            $table->integer('jumlah_aset')->nullable()->default(null)->change();
        });
    }
};
