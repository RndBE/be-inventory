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
        // Kolom `status` sudah dibuat di migration pembuatan tabelnya, jadi
        // penambahan di sini harus dilewati kalau sudah ada — kalau tidak,
        // `migrate` pada database kosong berhenti di sini.
        $kolomApproval = [
            'status_leader',
            'status_general_manager',
            'status_purchasing',
            'status_manager',
            'status_finance',
            'status_admin_manager',
            'status',
        ];

        Schema::table('pengajuan', function (Blueprint $table) use ($kolomApproval) {
            foreach ($kolomApproval as $kolom) {
                if (Schema::hasColumn('pengajuan', $kolom)) {
                    continue;
                }

                $table->enum($kolom, ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            }
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
