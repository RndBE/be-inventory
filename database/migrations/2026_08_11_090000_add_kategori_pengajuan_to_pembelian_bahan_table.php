<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori pengajuan untuk Pembelian Bahan/Barang/Alat (Lokal & Impor):
     * 'Produksi' memakai approval Leader (atasan level 3) lalu Manager
     * (atasan level 2), sedangkan 'Riset' melewati Leader dan langsung ke
     * Manager. Kolom dibiarkan nullable supaya data lama (dan jenis Aset,
     * yang tidak memakai toggle ini) tetap valid.
     */
    public function up(): void
    {
        Schema::table('pembelian_bahan', function (Blueprint $table) {
            $table->string('kategori_pengajuan')->nullable()->after('jenis_pengajuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembelian_bahan', function (Blueprint $table) {
            $table->dropColumn('kategori_pengajuan');
        });
    }
};
