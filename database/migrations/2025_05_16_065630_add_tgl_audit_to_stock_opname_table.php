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
        Schema::table('stock_opname', function (Blueprint $table) {
            $table->date('tgl_audit')->nullable()->after('tgl_pengajuan');
            $table->string('auditor')->nullable()->after('tgl_audit');
            $table->date('tgl_approve_finance')->nullable()->after('auditor');
            $table->date('tgl_approve_direktur')->nullable()->after('tgl_approve_finance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_opname', function (Blueprint $table) {
            $table->dropColumn('tgl_audit');
            $table->dropColumn('auditor');
            $table->dropColumn('tgl_approve_finance');
            $table->dropColumn('tgl_approve_direktur');
        });
    }
};
