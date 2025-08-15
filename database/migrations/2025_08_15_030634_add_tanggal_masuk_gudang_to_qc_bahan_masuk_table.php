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
            $table->dateTime('tanggal_masuk_gudang')->nullable()->after('tanggal_qc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qc_bahan_masuk', function (Blueprint $table) {
            $table->dropColumn('tanggal_masuk_gudang');
        });
    }
};
