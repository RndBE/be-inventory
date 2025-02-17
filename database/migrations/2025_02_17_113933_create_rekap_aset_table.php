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
        Schema::create('rekap_aset', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_aset')->unique();
            $table->unsignedBigInteger('barang_aset_id');
            $table->string('link_gambar')->nullable();
            $table->date('tgl_perolehan')->nullable();
            $table->integer('jumlah_aset')->nullable();
            $table->decimal('harga_perolehan', 10, 2)->nullable();
            $table->string('kondisi')->nullable();
            $table->string('keterangan')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('barang_aset_id')->references('id')->on('barang_aset')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_aset');
    }
};
