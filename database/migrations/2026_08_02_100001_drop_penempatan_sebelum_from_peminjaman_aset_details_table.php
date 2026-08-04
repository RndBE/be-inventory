<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membuang snapshot penempatan sebelum dipinjam.
 *
 * Aturannya diubah: aset yang dikembalikan kembali ke manajemen, bukan ke
 * penempatan pemegang sebelumnya. Dengan begitu tidak ada yang perlu dipulihkan,
 * dan ketiga kolom ini tidak lagi dibaca siapa pun. Asal-usul penempatan tetap
 * terbaca di riwayat_mutasi_aset lewat kolom dari_nama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman_aset_details', function (Blueprint $table) {
            $table->dropForeign(['pic_sebelum_id']);
            $table->dropForeign(['ruangan_sebelum_id']);
            $table->dropColumn(['pic_sebelum_id', 'ruangan_sebelum_id', 'dipindahkan_pada']);
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman_aset_details', function (Blueprint $table) {
            $table->unsignedBigInteger('pic_sebelum_id')->nullable()->after('keterangan');
            $table->unsignedBigInteger('ruangan_sebelum_id')->nullable()->after('pic_sebelum_id');
            $table->dateTime('dipindahkan_pada')->nullable()->after('ruangan_sebelum_id');

            $table->foreign('pic_sebelum_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('ruangan_sebelum_id')->references('id')->on('ruangan')->nullOnDelete();
        });
    }
};
