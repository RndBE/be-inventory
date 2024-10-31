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
        Schema::create('projek_rnd', function (Blueprint $table) {
            $table->id();
            $table->string('kode_projek_rnd')->unique();
            $table->dateTime('mulai_projek_rnd');
            $table->dateTime('selesai_projek_rnd')->nullable();
            $table->string('nama_projek_rnd');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projek_rnd');
    }
};
