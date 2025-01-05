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
        Schema::create('pembelian_bahan_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembelian_bahan_id')->constrained('pembelian_bahan')->onDelete('cascade');
            $table->foreignId('bahan_id')->constrained('bahan');
            $table->integer('qty');
            $table->integer('jml_bahan')->nullable();
            $table->integer('used_materials')->nullable();
            $table->json('details');
            $table->json('new_details');
            $table->integer('sub_total');
            $table->integer('new_sub_total');
            $table->string('spesifikasi')->nullable();
            $table->string('keterangan_pembayaran')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembelian_bahan_details');
    }
};
