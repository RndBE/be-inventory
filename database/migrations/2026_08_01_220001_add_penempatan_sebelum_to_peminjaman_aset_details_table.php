<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyimpan penempatan aset sebelum dipinjam, supaya bisa dipulihkan saat kembali.
 *
 * Peminjaman yang disetujui memindahkan pic_id dan ruangan_id aset ke peminjam.
 * Tanpa catatan nilai asalnya, pengembalian hanya bisa mengosongkan kolom itu —
 * dan itu akan menghapus lokasi asal aset setiap kali terjadi pinjam-kembali.
 *
 * dipindahkan_pada dipakai sebagai penanda bahwa snapshot-nya sah. Tidak bisa
 * mengandalkan null pada kedua kolom lain, karena "belum punya PIC" memang nilai
 * yang wajar dan harus bisa dipulihkan apa adanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman_aset_details', function (Blueprint $table) {
            $table->unsignedBigInteger('pic_sebelum_id')->nullable()->after('keterangan');
            $table->unsignedBigInteger('ruangan_sebelum_id')->nullable()->after('pic_sebelum_id');
            $table->dateTime('dipindahkan_pada')->nullable()->after('ruangan_sebelum_id');

            $table->foreign('pic_sebelum_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('ruangan_sebelum_id')->references('id')->on('ruangan')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman_aset_details', function (Blueprint $table) {
            $table->dropForeign(['pic_sebelum_id']);
            $table->dropForeign(['ruangan_sebelum_id']);
            $table->dropColumn(['pic_sebelum_id', 'ruangan_sebelum_id', 'dipindahkan_pada']);
        });
    }
};
