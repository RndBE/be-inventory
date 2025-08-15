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
        Schema::table('qc_bahan_masuk', function (Blueprint $table) {
            $table->boolean('is_verified')
                ->default(false)
                ->after('id_petugas_input_qc')
                ->comment('Status checkbox QC, true jika sudah checklist');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qc_bahan_masuk', function (Blueprint $table) {
            $table->dropColumn('is_verified');
        });
    }
};
