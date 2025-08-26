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
        Schema::table('qc_2_produk_setengah_jadi', function (Blueprint $table) {
            $table->string('kode_qc')->nullable()->after('id');
            $table->dateTime('tgl_qc')->nullable()->after('kode_qc');
            $table->string('petugas_qc')->nullable()->after('tgl_qc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qc_2_produk_setengah_jadi', function (Blueprint $table) {
            //
        });
    }
};
