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
        Schema::create('bahan_keluar_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bahan_keluar_id')->constrained('bahan_keluars')->onDelete('cascade');
            $table->foreignId('bahan_id')->constrained('bahan'); // assuming 'bahan' table exists
            $table->integer('qty');
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
        Schema::dropIfExists('bahan_keluar_details');
    }
};
