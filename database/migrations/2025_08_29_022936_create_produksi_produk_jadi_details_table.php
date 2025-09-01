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
        Schema::create('produksi_produk_jadi_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produksi_produk_jadi_id')->constrained('produksi_produk_jadi')->onDelete('cascade');
            $table->foreignId('bahan_id')->constrained('bahan');
            $table->foreignId('produk_id')->constrained('bahan_setengahjadi_details');
            $table->string('serial_number')->nullable();
            $table->decimal('qty', 15, 2)->default(0)->nullable();
            $table->decimal('used_materials', 15, 2)->default(0)->nullable();
            $table->text('details')->nullable();
            $table->decimal('sub_total', 15, 2)->default(0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produksi_produk_jadi_details');
    }
};
