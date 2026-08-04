<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanggal kembali tidak lagi diisi pengaju saat mengajukan.
     * Yang dicatat hanya tanggal kembali sebenarnya oleh General Affair,
     * yang sudah tersimpan di peminjaman_aset_details.tgl_kembali.
     */
    public function up(): void
    {
        Schema::table('peminjaman_aset', function (Blueprint $table) {
            $table->dropColumn('tgl_rencana_kembali');
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman_aset', function (Blueprint $table) {
            $table->date('tgl_rencana_kembali')->nullable()->after('tgl_pinjam');
        });
    }
};
