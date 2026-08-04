<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rincian aset yang diserahkan pada satu BAST.
 *
 * Aset bisa datang dari dua sumber: tanggung jawab tetap (rekap_aset.pic_id)
 * atau peminjaman yang belum dikembalikan. Sumbernya disimpan supaya saat BAST
 * tuntas, tiap baris tahu apa yang harus dibereskan — melepas PIC, menutup
 * peminjaman, atau keduanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serah_terima_aset_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serah_terima_aset_id')->constrained('serah_terima_aset')->cascadeOnDelete();
            $table->unsignedBigInteger('rekap_aset_id');

            $table->enum('sumber', ['PIC', 'Peminjaman'])->default('PIC');
            $table->unsignedBigInteger('peminjaman_aset_detail_id')->nullable();

            $table->enum('kondisi_serah', ['Baik', 'Rusak', 'Hilang'])->default('Baik');
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('rekap_aset_id')->references('id')->on('rekap_aset')->cascadeOnDelete();
            $table->foreign('peminjaman_aset_detail_id')->references('id')->on('peminjaman_aset_details')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serah_terima_aset_details');
    }
};
