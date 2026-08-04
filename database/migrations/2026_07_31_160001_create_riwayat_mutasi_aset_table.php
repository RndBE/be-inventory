<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat perpindahan aset: pergantian PIC dan perpindahan ruangan.
     *
     * Sebelum ini, pic_id dan ruangan_id hanya satu kolom yang ditimpa setiap
     * kali diedit, sehingga pemegang/lokasi sebelumnya hilang tanpa jejak.
     *
     * Nama PIC dan ruangan disimpan sebagai snapshot teks, bukan hanya id-nya,
     * supaya riwayat tetap terbaca kalau user dihapus atau ruangan diganti nama.
     */
    public function up(): void
    {
        Schema::create('riwayat_mutasi_aset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekap_aset_id')->constrained('rekap_aset')->cascadeOnDelete();

            // 'PIC' atau 'Ruangan'
            $table->string('jenis');

            $table->unsignedBigInteger('dari_id')->nullable();
            $table->string('dari_nama')->nullable();
            $table->unsignedBigInteger('ke_id')->nullable();
            $table->string('ke_nama')->nullable();

            $table->unsignedBigInteger('dicatat_oleh')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('dicatat_oleh')->references('id')->on('users')->nullOnDelete();
            $table->index(['rekap_aset_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_mutasi_aset');
    }
};
