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
        Schema::table('projek_details', function (Blueprint $table) {
            $table->unsignedBigInteger('produk_jadis_id')->nullable()->after('produk_id');

            $table->foreign('produk_jadis_id')->references('id')->on('produk_jadi_details')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projek_details', function (Blueprint $table) {
            $table->dropForeign(['produk_jadis_id']);
            $table->dropColumn('produk_jadis_id');
        });
    }
};
