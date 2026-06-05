<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produk_sample', function (Blueprint $table) {
            if (!Schema::hasColumn('produk_sample', 'produk_jadi_id')) {
                $table->foreignId('produk_jadi_id')
                    ->nullable()
                    ->after('kode_produk_sample')
                    ->constrained('produk_jadi')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('produk_sample', function (Blueprint $table) {
            if (Schema::hasColumn('produk_sample', 'produk_jadi_id')) {
                $table->dropForeign(['produk_jadi_id']);
                $table->dropColumn('produk_jadi_id');
            }
        });
    }
};
