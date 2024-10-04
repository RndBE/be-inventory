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
        Schema::create('produksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bahan_keluar_id')->constrained('bahan_keluars')->onDelete('cascade');
            $table->string('kode_produksi')->unique();
            $table->dateTime('mulai_produksi');
            $table->dateTime('selesai_produksi')->nullable();
            $table->string('nama_produk');
            $table->string('jenis_produksi');
            $table->integer('jml_produksi');
            $table->foreignId('unit_id')->constrained('unit');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produksis');
    }
};
