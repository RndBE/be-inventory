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
        Schema::create('bahan_keluars', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tgl_keluar');
            $table->string('kode_transaksi')->unique();
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
        Schema::dropIfExists('bahan_keluars');
    }
};
