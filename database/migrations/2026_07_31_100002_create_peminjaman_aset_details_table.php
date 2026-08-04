<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Detail aset yang dipinjam, sekaligus tempat mencatat pengembaliannya.
     */
    public function up(): void
    {
        Schema::create('peminjaman_aset_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peminjaman_aset_id')->constrained('peminjaman_aset')->cascadeOnDelete();
            $table->unsignedBigInteger('rekap_aset_id');
            $table->integer('jumlah')->default(1);
            $table->string('keterangan')->nullable();

            $table->enum('status_pengembalian', ['Belum dikembalikan', 'Dikembalikan'])->default('Belum dikembalikan');
            $table->dateTime('tgl_kembali')->nullable();
            $table->string('kondisi_kembali')->nullable();
            $table->string('catatan_pengembalian')->nullable();
            $table->timestamps();

            $table->foreign('rekap_aset_id')->references('id')->on('rekap_aset')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman_aset_details');
    }
};
