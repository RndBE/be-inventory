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
        Schema::table('qc_bahan_masuk_details', function (Blueprint $table) {
            $table->decimal('jumlah_pembelian', 10, 2)->nullable()->after('jumlah_pengajuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qc_bahan_masuk_details', function (Blueprint $table) {
            //
        });
    }
};
