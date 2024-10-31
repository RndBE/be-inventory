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
        Schema::create('projek_rnd_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projek_rnd_id')->constrained('projek_rnd')->onDelete('cascade');
            $table->foreignId('bahan_id')->constrained('bahan');
            $table->integer('qty');
            $table->text('details');
            $table->integer('sub_total');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projek_rnd_details');
    }
};
