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
        Schema::create('produk_sample_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_sample_id')->constrained('produk_sample')->onDelete('cascade');
            $table->foreignId('bahan_id')->constrained('bahan');
            $table->foreignId('produk_id')->constrained('bahan_setengahjadi_details');
            $table->decimal('qty', 10, 2);
            $table->decimal('jml_bahan', 10, 2);
            $table->decimal('used_materials', 10, 2);
            $table->text('details');
            $table->decimal('sub_total', 15, 2);
            $table->string('serial_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk_sample_details');
    }
};
