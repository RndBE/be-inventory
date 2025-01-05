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
        Schema::table('produksis', function (Blueprint $table) {
            $table->string('pengaju')->nullable()->after('selesai_produksi');
            $table->string('keterangan')->nullable()->after('pengaju');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produksis', function (Blueprint $table) {
            $table->dropColumn('keterangan');
            $table->dropColumn('pengaju');
        });
    }
};
