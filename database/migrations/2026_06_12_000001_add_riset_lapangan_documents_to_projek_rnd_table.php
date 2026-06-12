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
        Schema::table('projek_rnd', function (Blueprint $table) {
            $table->boolean('is_riset_lapangan')->default(false)->after('keterangan_status');
            $table->string('file_proposal_riset')->nullable()->after('file_laporan');
            $table->string('file_surat_tugas_riset')->nullable()->after('file_proposal_riset');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projek_rnd', function (Blueprint $table) {
            $table->dropColumn([
                'is_riset_lapangan',
                'file_proposal_riset',
                'file_surat_tugas_riset',
            ]);
        });
    }
};
