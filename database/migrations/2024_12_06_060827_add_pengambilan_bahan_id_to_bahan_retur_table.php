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
        Schema::table('bahan_retur', function (Blueprint $table) {
            $table->foreignId('pengambilan_bahan_id')->nullable()->after('pengajuan_id')->constrained('pengambilan_bahan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan_retur', function (Blueprint $table) {
            $table->dropForeign(['pengambilan_bahan_id']);
            $table->dropColumn('pengambilan_bahan_id');
        });
    }
};
