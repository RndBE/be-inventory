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
        Schema::create('produk_produksi_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_produksis_id')->constrained('produk_produksis')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('bahan_id')->constrained('bahan');
            $table->integer('jml_bahan')->nullable();
            $table->integer('used_materials')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk_produksi_details');
    }
};
