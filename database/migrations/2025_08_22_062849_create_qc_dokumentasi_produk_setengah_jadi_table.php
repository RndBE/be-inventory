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
        Schema::create('qc_dokumentasi_produk_setengah_jadi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('qc1_id')->nullable();
            $table->unsignedBigInteger('qc2_id')->nullable();
            $table->string('file_path');
            $table->timestamps();

            $table->foreign('qc1_id')->references('id')->on('qc_1_produk_setengah_jadi')->onDelete('cascade');
            $table->foreign('qc2_id')->references('id')->on('qc_2_produk_setengah_jadi')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qc_dokumentasi_produk_setengah_jadi');
    }
};
