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
            $table->string('kode_produksi')->nullable()->after('kode_list');
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
