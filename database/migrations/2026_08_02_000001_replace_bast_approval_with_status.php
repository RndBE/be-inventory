<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mengganti rantai persetujuan empat tahap BAST dengan status tunggal.
 *
 * Serah terima aset lazimnya ditandatangani basah di atas kertas saat barangnya
 * berpindah tangan, bukan diklik terpisah-pisah di sistem. Yang tetap perlu
 * disengaja hanyalah pelepasan asetnya — itu dipegang tombol "Tandai Selesai".
 *
 * atasan_id dipertahankan karena namanya tetap dicetak di blok tanda tangan.
 */
return new class extends Migration
{
    private array $kolomApproval = [
        'status_karyawan', 'status_atasan', 'status_ga', 'status_hrd',
        'tgl_approve_karyawan', 'tgl_approve_atasan', 'tgl_approve_ga', 'tgl_approve_hrd',
    ];

    public function up(): void
    {
        Schema::table('serah_terima_aset', function (Blueprint $table) {
            $table->enum('status', ['Draft', 'Selesai'])->default('Draft')->after('keterangan');
            $table->dateTime('tgl_selesai')->nullable()->after('status');
            $table->unsignedBigInteger('diselesaikan_oleh')->nullable()->after('tgl_selesai');

            $table->foreign('diselesaikan_oleh')->references('id')->on('users')->nullOnDelete();
        });

        // BAST yang keempat tahapnya sudah disetujui dianggap sudah selesai.
        DB::table('serah_terima_aset')
            ->where('status_karyawan', 'Disetujui')
            ->where('status_atasan', 'Disetujui')
            ->where('status_ga', 'Disetujui')
            ->where('status_hrd', 'Disetujui')
            ->update(['status' => 'Selesai', 'tgl_selesai' => DB::raw('tgl_approve_hrd')]);

        Schema::table('serah_terima_aset', function (Blueprint $table) {
            $table->dropColumn($this->kolomApproval);
        });

        // Catatan kendala per tahap tidak lagi punya tempat setelah tahapnya hilang.
        DB::table('approval_kendalas')->where('module', 'serah_terima_aset')->delete();
    }

    public function down(): void
    {
        Schema::table('serah_terima_aset', function (Blueprint $table) {
            foreach (['status_karyawan', 'status_atasan', 'status_ga', 'status_hrd'] as $kolom) {
                $table->enum($kolom, ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            }
            foreach (['tgl_approve_karyawan', 'tgl_approve_atasan', 'tgl_approve_ga', 'tgl_approve_hrd'] as $kolom) {
                $table->dateTime($kolom)->nullable();
            }
        });

        DB::table('serah_terima_aset')->where('status', 'Selesai')->update([
            'status_karyawan' => 'Disetujui',
            'status_atasan' => 'Disetujui',
            'status_ga' => 'Disetujui',
            'status_hrd' => 'Disetujui',
            'tgl_approve_hrd' => DB::raw('tgl_selesai'),
        ]);

        Schema::table('serah_terima_aset', function (Blueprint $table) {
            $table->dropForeign(['diselesaikan_oleh']);
            $table->dropColumn(['status', 'tgl_selesai', 'diselesaikan_oleh']);
        });
    }
};
