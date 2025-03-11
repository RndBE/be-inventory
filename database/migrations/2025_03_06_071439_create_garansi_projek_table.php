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
        Schema::create('garansi_projek', function (Blueprint $table) {
            $table->id();
            $table->string('kode_garansi')->unique();
            $table->dateTime('mulai_garansi');
            $table->dateTime('selesai_garansi')->nullable();
            $table->string('pengaju')->nullable();
            $table->string('keterangan')->nullable();
            $table->unsignedBigInteger('kontrak_id')->nullable();
            $table->string('status');
            $table->decimal('anggaran', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('kontrak_id')->references('id')->on('kontrak')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garansi_projek');
    }
};
