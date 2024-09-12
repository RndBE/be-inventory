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
        Schema::create('bahan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_bahan')->unique();
            $table->string('nama_bahan');
            $table->unsignedBigInteger('jenis_bahan_id'); // Foreign key untuk jenis bahan
            $table->integer('stok_awal');
            $table->integer('total_stok')->nullable();
            $table->unsignedBigInteger('unit_id'); // Foreign key untuk unit
            $table->string('kondisi');
            $table->string('penempatan');
            $table->string('gambar')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('jenis_bahan_id')->references('id')->on('jenis_bahan')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('unit')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan');
    }
};
