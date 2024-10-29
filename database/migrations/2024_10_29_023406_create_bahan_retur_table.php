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
        Schema::create('bahan_retur', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tgl_pengajuan')->nullable();
            $table->dateTime('tgl_diterima')->nullable();
            $table->string('kode_transaksi')->unique();
            $table->foreignId('produksi_id')->constrained('produksis')->nullable();
            $table->foreignId('projek_id')->constrained('projek')->nullable();
            $table->string('tujuan');
            $table->string('divisi');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_retur');
    }
};
