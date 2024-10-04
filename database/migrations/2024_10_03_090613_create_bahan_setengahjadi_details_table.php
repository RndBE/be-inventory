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
        Schema::create('bahan_setengahjadi_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bahan_setengahjadi_id')->constrained('bahan_setengahjadis')->onDelete('cascade');
            $table->string('nama_produk');
            $table->integer('qty');
            $table->integer('sisa');
            $table->foreignId('unit_id')->constrained('unit');
            $table->integer('unit_price');
            $table->integer('sub_total');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_setengahjadi_details');
    }
};
