<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bukti foto kondisi aset saat dikembalikan, diunggah oleh General Affair.
     */
    public function up(): void
    {
        Schema::table('peminjaman_aset_details', function (Blueprint $table) {
            $table->string('bukti_foto')->nullable()->after('kondisi_kembali');
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman_aset_details', function (Blueprint $table) {
            $table->dropColumn('bukti_foto');
        });
    }
};
