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
        Schema::create('produk_sample', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produk_sample')->unique();
            $table->dateTime('mulai_produk_sample');
            $table->dateTime('selesai_produk_sample')->nullable();
            $table->string('pengaju')->nullable();
            $table->string('keterangan')->nullable();
            $table->string('nama_produk_sample');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk_sample');
    }
};
