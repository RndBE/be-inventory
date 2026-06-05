<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksi_produk_jadi', function (Blueprint $table) {
            if (!Schema::hasColumn('produksi_produk_jadi', 'produk_sample_id')) {
                $table->foreignId('produk_sample_id')
                    ->nullable()
                    ->after('produk_jadi_id')
                    ->constrained('produk_sample')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('produksi_produk_jadi', function (Blueprint $table) {
            if (Schema::hasColumn('produksi_produk_jadi', 'produk_sample_id')) {
                $table->dropForeign(['produk_sample_id']);
                $table->dropColumn('produk_sample_id');
            }
        });
    }
};
