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
            Schema::table('qc_produk_setengah_jadi_list', function (Blueprint $table) {
                $table->dropForeign(['produksi_id']);
            });

            DB::statement('ALTER TABLE qc_produk_setengah_jadi_list MODIFY produksi_id BIGINT UNSIGNED NULL');

            Schema::table('qc_produk_setengah_jadi_list', function (Blueprint $table) {
                $table->foreign('produksi_id')->references('id')->on('produksis')->cascadeOnDelete();
            });
        } else {
            Schema::table('qc_produk_setengah_jadi_list', function (Blueprint $table) {
                $table->unsignedBigInteger('produksi_id')->nullable()->change();
            });
        }

        Schema::table('qc_produk_setengah_jadi_list', function (Blueprint $table) {
            $table->foreignId('produk_sample_id')
                ->nullable()
                ->after('produksi_id')
                ->constrained('produk_sample')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('qc_produk_setengah_jadi_list', function (Blueprint $table) {
            $table->dropForeign(['produk_sample_id']);
            $table->dropColumn('produk_sample_id');
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('qc_produk_setengah_jadi_list', function (Blueprint $table) {
                $table->dropForeign(['produksi_id']);
            });

            DB::statement('ALTER TABLE qc_produk_setengah_jadi_list MODIFY produksi_id BIGINT UNSIGNED NOT NULL');

            Schema::table('qc_produk_setengah_jadi_list', function (Blueprint $table) {
                $table->foreign('produksi_id')->references('id')->on('produksis')->cascadeOnDelete();
            });
        } else {
            Schema::table('qc_produk_setengah_jadi_list', function (Blueprint $table) {
                $table->unsignedBigInteger('produksi_id')->nullable(false)->change();
            });
        }
    }
};
