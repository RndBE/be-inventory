<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda bahan batangan: panjang satu batang dalam cm.
 *
 * Sengaja tidak memakai kolom boolean terpisah atau menandai lewat
 * `jenis_bahan`. Angka panjangnya sendiri sudah cukup jadi penanda — kalau
 * terisi, bahan boleh diinput per batang atau per cm; kalau null, perilakunya
 * persis seperti sebelumnya. Satu kolom lebih sulit jadi tidak konsisten
 * dibanding boolean yang bisa true padahal panjangnya belum diisi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bahan', function (Blueprint $table) {
            $table->integer('panjang_standar')->nullable()->after('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('bahan', function (Blueprint $table) {
            $table->dropColumn('panjang_standar');
        });
    }
};
