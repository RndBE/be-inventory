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
        Schema::table('bahan_rusaks', function (Blueprint $table) {
            $table->foreignId('pengajuan_id')->nullable()->constrained('pengajuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan_rusaks', function (Blueprint $table) {
            $table->dropForeign(['pengajuan_id']);
            $table->dropColumn('pengajuan_id');
        });
    }
};
