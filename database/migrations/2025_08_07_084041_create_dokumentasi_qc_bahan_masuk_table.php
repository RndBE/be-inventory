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
        Schema::create('dokumentasi_qc_bahan_masuk', function (Blueprint $table) {
            // Jika kamu ingin kolom ini wajib diisi, hapus `nullable()`
            $table->id();
            $table->unsignedBigInteger('qc_bahan_masuk_detail_id');
            $table->unsignedBigInteger('bahan_id');
            $table->string('gambar')->nullable();

            $table->timestamps();

            // Tambahkan foreign key (sesuaikan nama tabel jika berbeda)
            $table->foreign('qc_bahan_masuk_detail_id')
                ->references('id')
                ->on('qc_bahan_masuk_details')
                ->onDelete('cascade');

            $table->foreign('bahan_id')
                ->references('id')
                ->on('bahan') // Sesuaikan jika nama tabel bukan 'bahans'
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumentasi_qc_bahan_masuk');
    }
};
