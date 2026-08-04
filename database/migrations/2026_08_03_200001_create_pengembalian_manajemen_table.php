<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pencatatan aset ber-PIC yang diserahkan kembali ke manajemen.
 *
 * Aset yang PIC & ruangannya diisi lewat rekap aset tidak punya pengajuan
 * peminjaman, sehingga tidak bisa memakai alur "Catat Pengembalian" milik
 * peminjaman. Satu-satunya cara sebelumnya adalah mengosongkan dua dropdown di
 * form edit — tanpa tanggal, tanpa kondisi, tanpa bukti.
 *
 * Tabel ini menyimpan bagian yang dibagi bersama satu kali serah terima:
 * tanggalnya, kondisinya, catatannya, dan siapa yang mencatat. Aset mana saja
 * yang tercakup TIDAK diduplikasi di sini — baris riwayat_mutasi_aset yang
 * merujuk ke sini sudah berperan sebagai rinciannya, lengkap dengan PIC dan
 * ruangan asalnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengembalian_manajemen', function (Blueprint $table) {
            $table->id();

            // Tanggal serah terima sebenarnya, bukan kapan diketik. GA sering
            // mencatat belakangan, dan created_at tidak boleh dipakai sebagai
            // penggantinya — itu membuat laporan per periode jadi salah.
            $table->date('tgl_kembali');

            $table->enum('kondisi', ['Baik', 'Rusak'])->default('Baik');
            $table->string('catatan')->nullable();

            // Karyawan yang menyerahkan. Disimpan walau PIC di rekap aset sudah
            // dikosongkan, supaya riwayat tetap bisa menjawab "siapa yang
            // menyerahkan" tanpa menelusuri baris mutasi satu per satu.
            $table->unsignedBigInteger('pic_sebelum_id')->nullable();
            $table->unsignedBigInteger('dicatat_oleh')->nullable();
            $table->timestamps();

            $table->foreign('pic_sebelum_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('dicatat_oleh')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('pengembalian_manajemen_bukti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengembalian_manajemen_id')
                ->constrained('pengembalian_manajemen')
                ->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengembalian_manajemen_bukti');
        Schema::dropIfExists('pengembalian_manajemen');
    }
};
