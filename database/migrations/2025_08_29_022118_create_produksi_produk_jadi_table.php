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
        Schema::create('produksi_produk_jadi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produksi')->unique();
            $table->foreignId('produk_jadi_id')->constrained('produk_jadi');
            $table->string('serial_number')->nullable();
            $table->dateTime('mulai_produksi');
            $table->dateTime('selesai_produksi')->nullable();
            $table->string('pengaju')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('jenis_produksi')->nullable();
            $table->integer('jml_produksi')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produksi_produk_jadi');
    }
};
