<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keterangan pengajuan sering melebihi 255 karakter sehingga insert gagal
     * dengan SQLSTATE[22001] pada MySQL strict mode.
     */
    public function up(): void
    {
        Schema::table('pembelian_bahan', function (Blueprint $table) {
            $table->text('keterangan')->nullable()->change();
        });

        Schema::table('pengajuan', function (Blueprint $table) {
            $table->text('keterangan')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pembelian_bahan', function (Blueprint $table) {
            $table->string('keterangan')->nullable()->change();
        });

        Schema::table('pengajuan', function (Blueprint $table) {
            $table->string('keterangan')->nullable()->change();
        });
    }
};
