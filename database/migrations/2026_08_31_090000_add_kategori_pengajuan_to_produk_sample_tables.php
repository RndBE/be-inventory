<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori pengajuan untuk Produk Sample: 'Non-RnD' diputus Leader (atasan
 * level 3), 'RnD' melewati Leader dan langsung diputus Manager (atasan level
 * 2). Mekanismenya meniru `kategori_pengajuan` di Pengajuan Pembelian, tapi
 * istilahnya sengaja beda — pengaju Produk Sample datang dari banyak divisi
 * (HSE, Marketing, Admin Project), jadi 'Produksi' lawan 'Riset' di sana tidak
 * cocok dipakai di sini.
 *
 * Kolomnya dipasang di dua tempat dengan tugas berbeda. Di `produk_sample`
 * kolomnya menyimpan pilihan pengaju sehingga Bahan Keluar susulan yang dibuat
 * lewat halaman edit memakai rute yang sama. Di `bahan_keluars` kolomnya
 * membekukan rute yang sudah berjalan, supaya approver satu transaksi tidak
 * ikut berpindah kalau kategori produk sample-nya diubah belakangan.
 *
 * Keduanya nullable: baris lama bernilai null dan tetap diperlakukan sebagai
 * 'Non-RnD', jadi pengaju Produk Sample yang sudah ada tidak berubah rutenya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produk_sample', function (Blueprint $table) {
            $table->string('kategori_pengajuan')->nullable()->after('nama_produk_sample');
        });

        Schema::table('bahan_keluars', function (Blueprint $table) {
            $table->string('kategori_pengajuan')->nullable()->after('status_leader');
        });
    }

    public function down(): void
    {
        Schema::table('produk_sample', function (Blueprint $table) {
            $table->dropColumn('kategori_pengajuan');
        });

        Schema::table('bahan_keluars', function (Blueprint $table) {
            $table->dropColumn('kategori_pengajuan');
        });
    }
};
