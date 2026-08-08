<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tautan foto unit yang masuk gudang, untuk produk jadi dan produk setengah jadi.
 *
 * Mengikuti pola yang sudah dipakai `rekap_aset.link_gambar`: yang disimpan
 * tautannya (umumnya Google Drive), bukan berkasnya. Alasannya sama seperti di
 * aset — foto unit diambil pakai ponsel dan sudah terlanjur ada di Drive, jadi
 * memindahkannya ke server hanya menambah kerja tanpa menambah manfaat.
 *
 * Ditaruh di tabel transaksi masuk gudang (bukan di master produk) karena satu
 * baris di sana mewakili satu unit dengan serial number sendiri, dan fotonya
 * memang foto unit itu — bukan foto katalog produknya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produk_jadis', function (Blueprint $table) {
            $table->string('link_gambar')->nullable()->after('kode_transaksi');
        });

        Schema::table('bahan_setengahjadis', function (Blueprint $table) {
            $table->string('link_gambar')->nullable()->after('kode_transaksi');
        });
    }

    public function down(): void
    {
        Schema::table('produk_jadis', function (Blueprint $table) {
            $table->dropColumn('link_gambar');
        });

        Schema::table('bahan_setengahjadis', function (Blueprint $table) {
            $table->dropColumn('link_gambar');
        });
    }
};
