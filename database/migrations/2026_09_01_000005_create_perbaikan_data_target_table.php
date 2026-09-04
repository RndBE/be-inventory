<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baris perubahan yang diminta pada satu pengajuan perbaikan data.
 *
 * Tabel terpisah, bukan kolom tambahan di `perbaikan_data`, karena satu
 * pengajuan lazimnya menyentuh beberapa kolom sekaligus — nominal yang salah
 * plus keterangannya.
 *
 * `nilai_lama` dibekukan saat pengajuan dibuat, dan diisi oleh server dari
 * database, bukan dari kiriman browser. Angka itu yang dibandingkan lagi saat
 * eksekusi: kalau nilainya sudah berubah di antara pengajuan dan approval,
 * koreksinya ditolak alih-alih menimpa perubahan sah orang lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perbaikan_data_target', function (Blueprint $table) {
            $table->id();

            $table->foreignId('perbaikan_data_id')
                ->constrained('perbaikan_data')
                ->onDelete('cascade');

            $table->string('modul');
            $table->unsignedBigInteger('modul_id');
            $table->string('field');

            $table->text('nilai_lama')->nullable();
            $table->text('nilai_baru')->nullable();

            // diajukan  - menunggu approval
            // dicatat - jejaknya sudah masuk audit_perubahan_data (dulu bernama
            //           'dieksekusi', diubah migration 2026_09_03_000003)
            // gagal     - ditolak saat eksekusi, alasannya di kolom catatan
            $table->string('status')->default('diajukan');
            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index(['modul', 'modul_id']);
            $table->index('perbaikan_data_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perbaikan_data_target');
    }
};
