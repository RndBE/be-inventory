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
        Schema::table('bahan_setengahjadis', function (Blueprint $table) {
            $table->unsignedBigInteger('id_qc_produk_setengahjadi')->nullable()->after('id');

            // Tambahkan relasi foreign key
            $table->foreign('id_qc_produk_setengahjadi')
                ->references('id')
                ->on('qc_produk_setengah_jadi_list')
                ->onDelete('set null') // kalau data QC dihapus, otomatis null
                ->onUpdate('cascade'); // kalau ID QC berubah, ikut terupdate
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan_setengahjadis', function (Blueprint $table) {
            $table->dropForeign(['id_qc_produk_setengahjadi']);
            $table->dropColumn('id_qc_produk_setengahjadi');
        });
    }
};
