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
        Schema::table('pembelian_bahan', function (Blueprint $table) {
            $table->dateTime('tgl_isi_harga')->nullable()->after('tgl_approve_purchasing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembelian_bahan', function (Blueprint $table) {
            //
        });
    }
};
