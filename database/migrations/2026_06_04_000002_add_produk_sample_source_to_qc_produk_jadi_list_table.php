<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('qc_produk_jadi_list', function (Blueprint $table) {
                $table->dropForeign(['produksi_produk_jadi_id']);
            });

            DB::statement('ALTER TABLE qc_produk_jadi_list MODIFY produksi_produk_jadi_id BIGINT UNSIGNED NULL');

            Schema::table('qc_produk_jadi_list', function (Blueprint $table) {
                $table->foreign('produksi_produk_jadi_id')
                    ->references('id')
                    ->on('produksi_produk_jadi')
                    ->cascadeOnDelete();
            });
        } else {
            Schema::table('qc_produk_jadi_list', function (Blueprint $table) {
                $table->unsignedBigInteger('produksi_produk_jadi_id')->nullable()->change();
            });
        }

        Schema::table('qc_produk_jadi_list', function (Blueprint $table) {
            if (!Schema::hasColumn('qc_produk_jadi_list', 'produk_sample_id')) {
                $table->foreignId('produk_sample_id')
                    ->nullable()
                    ->after('produksi_produk_jadi_id')
                    ->constrained('produk_sample')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('qc_produk_jadi_list', function (Blueprint $table) {
            if (Schema::hasColumn('qc_produk_jadi_list', 'produk_sample_id')) {
                $table->dropForeign(['produk_sample_id']);
                $table->dropColumn('produk_sample_id');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('qc_produk_jadi_list', function (Blueprint $table) {
                $table->dropForeign(['produksi_produk_jadi_id']);
            });

            DB::statement('ALTER TABLE qc_produk_jadi_list MODIFY produksi_produk_jadi_id BIGINT UNSIGNED NOT NULL');

            Schema::table('qc_produk_jadi_list', function (Blueprint $table) {
                $table->foreign('produksi_produk_jadi_id')
                    ->references('id')
                    ->on('produksi_produk_jadi')
                    ->cascadeOnDelete();
            });
        } else {
            Schema::table('qc_produk_jadi_list', function (Blueprint $table) {
                $table->unsignedBigInteger('produksi_produk_jadi_id')->nullable(false)->change();
            });
        }
    }
};
