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
        Schema::table('stock_opname_details', function (Blueprint $table) {
            $table->decimal('tersedia_fisik_audit', 10,2)->nullable()->after('tersedia_fisik');
            $table->decimal('selisih_audit', 10,2)->nullable()->after('selisih');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_opname_details', function (Blueprint $table) {
            $table->dropColumn('tersedia_fisik_audit');
            $table->dropColumn('selisih_audit');
        });
    }
};
