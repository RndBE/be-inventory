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
            $table->unsignedBigInteger('bahan_id')->nullable()->after('produksi_id');

            // Tambahkan relasi foreign key
            $table->foreign('bahan_id')
                ->references('id')
                ->on('bahan')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qc_produk_setengah_jadi_list', function (Blueprint $table) {
            $table->dropForeign(['bahan_id']);
            $table->dropColumn('bahan_id');
        });
    }
};
