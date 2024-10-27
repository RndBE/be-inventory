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
        Schema::create('projek', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bahan_keluar_id')->constrained('bahan_keluars')->onDelete('cascade');
            $table->string('kode_projek')->unique();
            $table->dateTime('mulai_projek');
            $table->dateTime('selesai_projek')->nullable();
            $table->string('nama_projek');
            $table->integer('jml_projek');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projek');
    }
};
