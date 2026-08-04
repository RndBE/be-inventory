<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membedakan aset yang masih dipegang karyawan dari yang sudah pernah ia kembalikan.
 *
 * BAST offboarding perlu memuat keduanya supaya jadi rekening lengkap: apa yang
 * diserahkan sekarang, dan apa yang sudah beres sebelumnya. Tapi hanya baris
 * "Dipegang" yang boleh diproses saat BAST tuntas — aset yang sudah dikembalikan
 * penempatannya sudah dipulihkan waktu itu, dan tidak boleh disentuh lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('serah_terima_aset_details', function (Blueprint $table) {
            $table->enum('status_pegang', ['Dipegang', 'Sudah kembali'])
                ->default('Dipegang')
                ->after('sumber');
        });
    }

    public function down(): void
    {
        Schema::table('serah_terima_aset_details', function (Blueprint $table) {
            $table->dropColumn('status_pegang');
        });
    }
};
