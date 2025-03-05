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
        Schema::table('projek_rnd_details', function (Blueprint $table) {
            $table->unsignedBigInteger('produk_id')->nullable()->after('bahan_id');
            $table->string('serial_number')->nullable();
            $table->foreign('produk_id')->references('id')->on('bahan_setengahjadi_details')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projek_rnd_details', function (Blueprint $table) {
            $table->dropForeign(['produk_id']);
            $table->dropColumn('produk_id');
            $table->dropColumn('serial_number');
        });
    }
};
