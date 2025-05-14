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
            $table->foreignId('produk_sample_id')->nullable()->after('pengambilan_bahan_id')->constrained('produk_sample');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan_rusaks', function (Blueprint $table) {
            $table->dropForeign(['produk_sample_id']);
            $table->dropColumn('produk_sample_id');
        });
    }
};
