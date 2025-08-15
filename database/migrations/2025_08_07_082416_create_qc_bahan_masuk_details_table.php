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
        Schema::create('qc_bahan_masuk_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_qc_bahan_masuk');
            $table->unsignedBigInteger('bahan_id');
            $table->unsignedBigInteger('supplier_id');

            $table->string('no_invoice');
            $table->decimal('jumlah_pengajuan', 10, 2);
            $table->decimal('stok_lama', 10, 2);
            $table->decimal('jumlah_diterima', 10, 2);
            $table->decimal('fisik_baik', 10, 2);
            $table->decimal('fisik_rusak', 10, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('sub_total', 15, 2);
            $table->enum('status', ['Belum Diterima', 'Diterima Semua', 'Diterima Sebagian', 'Ditolak'])->default('Belum Diterima');

            $table->text('notes')->nullable();

            $table->timestamps();

            // Relasi ke hasil QC
            $table->foreign('id_qc_bahan_masuk')
                ->references('id_qc_bahan_masuk')
                ->on('qc_bahan_masuk')
                ->onDelete('cascade');

            // Relasi ke tabel bahan
            $table->foreign('bahan_id')
                ->references('id')
                ->on('bahan');

            // Relasi ke tabel supplier
            $table->foreign('supplier_id')
                ->references('id')
                ->on('supplier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qc_bahan_masuk_details');
    }
};
