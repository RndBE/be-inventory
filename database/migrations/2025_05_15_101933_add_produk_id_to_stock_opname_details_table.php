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
            $table->foreignId('produk_id')
                ->nullable()
                ->after('bahan_id')
                ->constrained('bahan_setengahjadi_details');

            $table->string('serial_number')
                ->nullable()
                ->after('selisih_audit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_opname_details', function (Blueprint $table) {
            $table->dropForeign(['produk_id']);
            $table->dropColumn(['produk_id', 'serial_number']);
        });
    }
};
