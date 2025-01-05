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
        Schema::create('pembelian_bahan', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tgl_pengajuan')->nullable();
            $table->dateTime('tgl_keluar')->nullable();
            $table->string('kode_transaksi')->unique();
            $table->foreignId('produksi_id')->nullable()->constrained('produksis');
            $table->foreignId('projek_id')->nullable()->constrained('projek');
            $table->foreignId('projek_rnd_id')->nullable()->constrained('projek_rnd');
            $table->foreignId('pengajuan_id')->nullable()->constrained('pengajuan');
            $table->string('tujuan')->nullable();
            $table->string('keterangan')->nullable();
            $table->string('divisi')->nullable();
            $table->foreignId('pengaju')->nullable()->constrained('users')->onDelete('set null')->onUpdate('cascade');
            $table->enum('status_leader', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->enum('status_purchasing', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->enum('status_manager', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->enum('status_finance', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->enum('status_admin_manager', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembelian_bahan');
    }
};
