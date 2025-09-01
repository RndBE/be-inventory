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
            $table->foreignId('produksi_produk_jadi_id')->after('projek_id')->nullable()->constrained('produksi_produk_jadi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan_retur', function (Blueprint $table) {
            $table->dropForeign(['produksi_produk_jadi_id']);
            $table->dropColumn('produksi_produk_jadi_id');
        });
    }
};
