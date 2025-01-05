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
        Schema::create('stock_opname', function (Blueprint $table) {
            $table->id();
            $table->date('tgl_pengajuan');
            $table->date('tgl_diterima');
            $table->string('nomor_referensi')->unique();
            $table->text('keterangan')->nullable();
            $table->enum('status_finance', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->enum('status_direktur', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname');
    }
};
