<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Header pengajuan peminjaman aset.
     * Alur approval: Leader (atasan level 3) -> Manager (atasan level 2) -> General Affair.
     */
    public function up(): void
    {
        Schema::create('peminjaman_aset', function (Blueprint $table) {
            $table->id();
            $table->string('kode_peminjaman')->unique();
            $table->dateTime('tgl_pengajuan')->nullable();
            $table->unsignedBigInteger('pengaju')->nullable();
            $table->string('divisi')->nullable();
            $table->string('keperluan')->nullable();
            $table->date('tgl_pinjam')->nullable();
            $table->date('tgl_rencana_kembali')->nullable();

            $table->enum('status_leader', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->enum('status_manager', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->enum('status', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');

            $table->dateTime('tgl_approve_leader')->nullable();
            $table->dateTime('tgl_approve_manager')->nullable();
            $table->dateTime('tgl_approve_ga')->nullable();

            // Belum dikembalikan / Sebagian dikembalikan / Selesai
            $table->string('status_pengembalian')->default('Belum dikembalikan');
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->foreign('pengaju')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman_aset');
    }
};
