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
        Schema::create('produk_jadi_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_jadis_id')->constrained('produk_jadis')->onDelete('cascade');
            $table->foreignId('produk_id')->constrained('produk_jadi');

            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('sisa', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);

            $table->string('serial_number')->nullable();
            $table->string('nama_produk')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk_jadi_details');
    }
};
