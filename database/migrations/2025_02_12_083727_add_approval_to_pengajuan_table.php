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
        Schema::table('pengajuan', function (Blueprint $table) {
            $table->enum('status_leader', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->enum('status_general_manager', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->enum('status_purchasing', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->enum('status_manager', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->enum('status_finance', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->enum('status_admin_manager', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->enum('status', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan', function (Blueprint $table) {
            //
        });
    }
};
