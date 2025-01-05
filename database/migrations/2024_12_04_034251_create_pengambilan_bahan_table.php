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
        Schema::create('pengambilan_bahan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pengajuan')->unique();
            $table->dateTime('mulai_pengajuan')->nullable();
            $table->dateTime('selesai_pengajuan')->nullable();
            $table->string('divisi')->nullable();
            $table->string('pengaju')->nullable();
            $table->string('project')->nullable();
            $table->string('keterangan')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengambilan_bahan');
    }
};
