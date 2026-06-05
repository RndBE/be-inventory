<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->hasColumn('produksi_produk_jadi_details', 'produk_jadis_id')) {
            return;
        }

        Schema::table('produksi_produk_jadi_details', function (Blueprint $table) {
            $table->unsignedBigInteger('produk_jadis_id')->nullable()->after('produk_id');

            $table->foreign('produk_jadis_id')
                ->references('id')
                ->on('produk_jadi_details')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!$this->hasColumn('produksi_produk_jadi_details', 'produk_jadis_id')) {
            return;
        }

        Schema::table('produksi_produk_jadi_details', function (Blueprint $table) {
            $table->dropForeign(['produk_jadis_id']);
            $table->dropColumn('produk_jadis_id');
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
