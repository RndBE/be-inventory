<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tahap "Mengetahui" oleh HRD, dipasang setelah General Affair.
     * Ini gerbang terakhir sebelum aset boleh dikeluarkan/dipindahkan:
     * GA menyetujui peminjamannya, HRD yang memutuskan asetnya boleh keluar atau tidak.
     */
    public function up(): void
    {
        Schema::table('peminjaman_aset', function (Blueprint $table) {
            $table->enum('status_hrd', ['Belum disetujui', 'Disetujui', 'Ditolak'])
                ->default('Belum disetujui')
                ->after('status');
            $table->dateTime('tgl_approve_hrd')->nullable()->after('tgl_approve_ga');
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman_aset', function (Blueprint $table) {
            $table->dropColumn(['status_hrd', 'tgl_approve_hrd']);
        });
    }
};
