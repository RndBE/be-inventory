<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->hasColumn('produksi_produk_jadi', 'produk_sample_id')) {
            return;
        }

        Schema::table('produksi_produk_jadi', function (Blueprint $table) {
            $table->foreignId('produk_sample_id')
                ->nullable()
                ->after('produk_jadi_id')
                ->constrained('produk_sample')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!$this->hasColumn('produksi_produk_jadi', 'produk_sample_id')) {
            return;
        }

        Schema::table('produksi_produk_jadi', function (Blueprint $table) {
            $table->dropForeign(['produk_sample_id']);
            $table->dropColumn('produk_sample_id');
        });
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return Schema::hasColumn($table, $column);
        }

        return !empty(DB::select(
            'select column_name from information_schema.columns where table_schema = database() and table_name = ? and column_name = ? limit 1',
            [$table, $column]
        ));
    }
};
