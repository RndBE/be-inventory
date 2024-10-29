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
        Schema::create('bahan_retur_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bahan_retur_id')->constrained('bahan_retur')->onDelete('cascade');
            $table->foreignId('bahan_id')->constrained('bahan');
            $table->integer('qty');
            $table->integer('jml_bahan')->nullable();
            $table->json('details');
            $table->integer('sub_total');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_retur_details');
    }
};
