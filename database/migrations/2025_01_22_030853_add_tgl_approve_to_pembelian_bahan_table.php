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
        Schema::table('pembelian_bahan', function (Blueprint $table) {
            $table->dateTime('tgl_approve_leader')->nullable();
            $table->dateTime('tgl_approve_purchasing')->nullable();
            $table->dateTime('tgl_approve_manager')->nullable();
            $table->dateTime('tgl_approve_finance')->nullable();
            $table->dateTime('tgl_approve_admin_manager')->nullable();
            $table->dateTime('tgl_approve_general_manager')->nullable();
            $table->dateTime('tgl_approve_direktur')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembelian_bahan', function (Blueprint $table) {
            $table->dropColumn('tgl_approve_leader');
            $table->dropColumn('tgl_approve_purchasing');
            $table->dropColumn('tgl_approve_manager');
            $table->dropColumn('tgl_approve_finance');
            $table->dropColumn('tgl_approve_admin_manager');
            $table->dropColumn('tgl_approve_general_manager');
            $table->dropColumn('tgl_approve_direktur');
        });
    }
};
