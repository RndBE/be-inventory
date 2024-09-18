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
        Schema::create('detail_produksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produksi_id')->constrained('produksis');
            $table->foreignId('stok_produksis_id')->constrained('stok_produksis');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_produksis');
    }
};
