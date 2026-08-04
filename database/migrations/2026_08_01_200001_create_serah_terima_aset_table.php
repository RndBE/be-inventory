<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Berita Acara Serah Terima Aset (BAST) untuk offboarding karyawan.
 *
 * Rantai persetujuan: Karyawan -> Atasan -> General Affair -> HRD.
 * Dokumen PDF baru bisa diunduh setelah keempatnya menyetujui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serah_terima_aset', function (Blueprint $table) {
            $table->id();
            $table->string('kode_bast')->unique();
            $table->dateTime('tgl_pengajuan')->nullable();

            // Karyawan yang keluar. Dipisah dari pengaju karena BAST bisa dibuat
            // HRD atas nama karyawan yang aksesnya sudah dicabut.
            $table->unsignedBigInteger('karyawan_id');
            $table->unsignedBigInteger('pengaju')->nullable();

            $table->string('alasan_keluar')->nullable();
            $table->date('tgl_efektif')->nullable();
            $table->string('keterangan')->nullable();

            $table->enum('status_karyawan', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->enum('status_atasan', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->enum('status_ga', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->enum('status_hrd', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');

            $table->dateTime('tgl_approve_karyawan')->nullable();
            $table->dateTime('tgl_approve_atasan')->nullable();
            $table->dateTime('tgl_approve_ga')->nullable();
            $table->dateTime('tgl_approve_hrd')->nullable();

            // Atasan yang bertanggung jawab dikunci saat BAST dibuat. Kalau diambil
            // dari relasi user saat approval, mutasi jabatan di tengah proses bisa
            // memindahkan kewajiban ke orang yang tidak pernah melihat pengajuannya.
            $table->unsignedBigInteger('atasan_id')->nullable();

            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->foreign('karyawan_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('pengaju')->references('id')->on('users')->nullOnDelete();
            $table->foreign('atasan_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serah_terima_aset');
    }
};
