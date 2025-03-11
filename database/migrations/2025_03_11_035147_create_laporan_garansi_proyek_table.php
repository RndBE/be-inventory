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
        Schema::create('laporan_garansi_proyek', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('garansi_projek_id')->nullable();
            $table->string('pembuat_laporan')->nullable();
            $table->dateTime('tanggal');
            $table->string('nama_biaya_tambahan')->nullable();
            $table->integer('qty')->nullable();
            $table->string('satuan')->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_biaya', 15, 2)->nullable();
            $table->string('keterangan')->nullable();

            $table->foreign('garansi_projek_id')->references('id')->on('garansi_projek')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_garansi_proyek');
    }
};
