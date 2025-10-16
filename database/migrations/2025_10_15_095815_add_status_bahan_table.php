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
        Schema::table('bahan', function (Blueprint $table) {
            $table->enum('status', ['Digunakan', 'Tidak digunakan'])->nullable()->default('Digunakan')->after('nama_bahan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan', function (Blueprint $table) {
            //
        });
    }
};
