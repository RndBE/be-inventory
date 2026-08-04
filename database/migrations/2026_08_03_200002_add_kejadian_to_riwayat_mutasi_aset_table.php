<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Memisahkan "kapan kejadiannya" dari "kapan dicatat" pada riwayat mutasi.
 *
 * Sebelumnya riwayat cuma punya created_at, dan itu waktu pengetikan. Aset yang
 * diserahkan Senin tapi baru dicatat Kamis tercatat sebagai Kamis, dan tanggal
 * sebenarnya tidak punya tempat sama sekali — persis kelemahan yang tidak dimiliki
 * alur peminjaman, karena di sana ada kolom tgl_kembali tersendiri.
 *
 * tgl_kejadian sengaja nullable: perpindahan yang dicatat otomatis (peminjaman,
 * pengembalian, edit manual) memang terjadi saat itu juga, jadi created_at sudah
 * benar. Kolom ini hanya terisi kalau pencatatnya menyebutkan tanggal lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riwayat_mutasi_aset', function (Blueprint $table) {
            $table->date('tgl_kejadian')->nullable()->after('ke_nama');

            // Penghubung ke satu kali serah terima ke manajemen. Lewat kolom ini
            // baris mutasi berperan sebagai rincian pengembalian, sehingga bukti
            // foto dan catatannya bisa ditampilkan di riwayat aset maupun di
            // halaman Pergerakan Aset tanpa menduplikasi data.
            $table->unsignedBigInteger('pengembalian_manajemen_id')->nullable()->after('tgl_kejadian');

            // nullOnDelete: menghapus catatan pengembalian tidak boleh ikut
            // menghapus jejak perpindahannya — perpindahan asetnya tetap terjadi.
            $table->foreign('pengembalian_manajemen_id')
                ->references('id')->on('pengembalian_manajemen')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_mutasi_aset', function (Blueprint $table) {
            $table->dropForeign(['pengembalian_manajemen_id']);
            $table->dropColumn(['tgl_kejadian', 'pengembalian_manajemen_id']);
        });
    }
};
