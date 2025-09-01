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
        Schema::create('produk_jadis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_qc_produk_jadi')->nullable();
            $table->dateTime('tgl_masuk');
            $table->string('kode_transaksi')->unique();
            $table->foreignId('produksi_produk_jadi_id')->nullable()->constrained('produksi_produk_jadi');

            // Tambahkan relasi foreign key
            $table->foreign('id_qc_produk_jadi')
                ->references('id')
                ->on('qc_produk_jadi_list')
                ->onDelete('set null') // kalau data QC dihapus, otomatis null
                ->onUpdate('cascade'); // kalau ID QC berubah, ikut terupdate

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk_jadis');
    }
};
