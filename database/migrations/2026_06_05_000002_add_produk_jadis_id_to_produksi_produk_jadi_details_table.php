<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksi_produk_jadi_details', function (Blueprint $table) {
            if (!Schema::hasColumn('produksi_produk_jadi_details', 'produk_jadis_id')) {
                $table->unsignedBigInteger('produk_jadis_id')->nullable()->after('produk_id');

                $table->foreign('produk_jadis_id')
                    ->references('id')
                    ->on('produk_jadi_details')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('produksi_produk_jadi_details', function (Blueprint $table) {
            if (Schema::hasColumn('produksi_produk_jadi_details', 'produk_jadis_id')) {
                $table->dropForeign(['produk_jadis_id']);
                $table->dropColumn('produk_jadis_id');
            }
        });
    }
};
