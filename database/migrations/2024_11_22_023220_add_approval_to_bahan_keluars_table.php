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
        Schema::table('bahan_keluars', function (Blueprint $table) {
            $table->enum('status_leader', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan_keluars', function (Blueprint $table) {
            $table->dropColumn('status_leader');
        });
    }
};
