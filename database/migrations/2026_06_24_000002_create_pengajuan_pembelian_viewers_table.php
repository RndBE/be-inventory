<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel pemetaan: user "viewer" boleh melihat pengajuan pembelian milik user "target".
     * Dipakai saat user TIDAK punya akses lihat-semua, untuk membuka akses ke orang tertentu saja.
     */
    public function up(): void
    {
        Schema::create('pengajuan_pembelian_viewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['viewer_id', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_pembelian_viewers');
    }
};
