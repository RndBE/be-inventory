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
        Schema::create('qc_produk_jadi_list', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produksi_produk_jadi_id')->constrained('produksi_produk_jadi')->cascadeOnDelete();
            $table->unsignedBigInteger('produk_jadi_id')->nullable();
            $table->string('kode_list')->nullable();
            $table->dateTime('mulai_produksi')->nullable();
            $table->dateTime('selesai_produksi')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('petugas_produksi')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->dateTime('tanggal_masuk_gudang')->nullable();

            // Tambahkan relasi foreign key
            $table->foreign('produk_jadi_id')
                ->references('id')
                ->on('produk_jadi')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qc_produk_jadi_list');
    }
};
