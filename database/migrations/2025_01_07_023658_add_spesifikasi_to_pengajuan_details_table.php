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
        Schema::table('pengajuan_details', function (Blueprint $table) {
            $table->text('spesifikasi')->nullable();
            $table->text('keterangan_pembayaran')->nullable();
            $table->text('penanggungjawabaset')->nullable();
            $table->text('alasan')->nullable();
            $table->text('details_usd')->nullable();
            $table->float('sub_total_usd')->nullable();
            $table->text('new_details_usd')->nullable();
            $table->float('new_sub_total_usd')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_details', function (Blueprint $table) {
            $table->dropColumn('spesifikasi');
            $table->dropColumn('keterangan_pembayaran');
            $table->dropColumn('penanggungjawabaset');
            $table->dropColumn('alasan');
            $table->dropColumn('details_usd');
            $table->dropColumn('sub_total_usd');
            $table->dropColumn('new_details_usd');
            $table->dropColumn('new_sub_total_usd');
        });
    }
};
