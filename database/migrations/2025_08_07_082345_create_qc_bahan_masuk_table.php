<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qc_bahan_masuk', function (Blueprint $table) {
            $table->id('id_qc_bahan_masuk');
            $table->unsignedBigInteger('id_pembelian_bahan');
            $table->string('kode_qc')->unique();
            $table->dateTime('tanggal_qc');
            $table->text('keterangan_qc')->nullable();

            // GANTI dari string ke unsignedBigInteger
            $table->unsignedBigInteger('id_petugas_qc');
            $table->unsignedBigInteger('id_petugas_input_qc');
            
            $table->timestamps();

            // Foreign key ke tabel pembelian_bahan
            $table->foreign('id_pembelian_bahan')->references('id')->on('pembelian_bahan');

            // Relasi ke tabel users
            $table->foreign('id_petugas_qc')->references('id')->on('users');
            $table->foreign('id_petugas_input_qc')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qc_bahan_masuk');
    }
};
