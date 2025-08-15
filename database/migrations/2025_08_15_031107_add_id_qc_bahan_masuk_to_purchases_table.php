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
        Schema::table('purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('id_qc_bahan_masuk')->nullable()->after('id');

            // Tambahkan relasi foreign key
            $table->foreign('id_qc_bahan_masuk')
                ->references('id_qc_bahan_masuk')
                ->on('qc_bahan_masuk')
                ->onDelete('set null') // kalau data QC dihapus, otomatis null
                ->onUpdate('cascade'); // kalau ID QC berubah, ikut terupdate
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['id_qc_bahan_masuk']);
            $table->dropColumn('id_qc_bahan_masuk');
        });
    }
};
