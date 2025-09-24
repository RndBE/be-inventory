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
        Schema::table('qc_produk_setengah_jadi_list', function (Blueprint $table) {
            $table->string('jenis_sn')->nullable()->after('serial_number');
            $table->string('id_bluetooth')->nullable()->after('jenis_sn');
            $table->string('kode_jenis_unit')->nullable()->after('id_bluetooth');
            $table->string('kode_wiring_unit')->nullable()->after('kode_jenis_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qc_produk_setengah_jadi_list', function (Blueprint $table) {
            //
        });
    }
};
