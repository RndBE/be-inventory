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
        Schema::create('pengambilan_bahan_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengambilan_bahan_id')->constrained('pengambilan_bahan')->onDelete('cascade');
            $table->foreignId('bahan_id')->constrained('bahan');
            $table->integer('qty')->nullable();
            $table->integer('jml_bahan')->nullable();
            $table->integer('used_materials')->nullable();
            $table->text('details');
            $table->integer('sub_total')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengambilan_bahan_details');
    }
};
