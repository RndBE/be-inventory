<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot identitas PIHAK KEDUA pada Berita Acara Serah Terima Aset.
 *
 * Format resmi mencantumkan Nomor ID, Jabatan, dan Divisi PIHAK KEDUA. Ketiganya
 * dibekukan di dokumennya, tidak dibaca dari relasi user saat mencetak: mutasi
 * jabatan atau perbaikan data master setelah dokumen ditandatangani akan membuat
 * cetak ulang berbeda dari kertas yang sudah ada tanda tangannya.
 *
 * Ini juga menyiapkan penukaran sumber data. Ke depan identitas ini sebaiknya
 * diambil dari HRIS yang memang pemiliknya — data di inventory sudah terbukti
 * melenceng (divisi tercatat "Admin", padahal dokumen resmi menyebut "HRD &
 * Corporate Service"). Karena yang dicetak adalah kolom snapshot ini, penggantian
 * sumbernya nanti tidak mengubah PDF sama sekali: yang berubah hanya dari mana
 * kolom ini diisi saat BAST dibuat.
 *
 * Sengaja tidak dibaca dari HRIS pada saat cetak. Selain mengulang masalah
 * pembekuan di atas, itu membuat pencetakan dokumen gagal setiap kali HRIS tidak
 * bisa dihubungi — padahal berkasnya dibutuhkan tepat saat serah terima.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('serah_terima_aset', function (Blueprint $table) {
            $table->string('hrd_nomor_id')->nullable()->after('hrd_id');
            $table->string('hrd_jabatan')->nullable()->after('hrd_nomor_id');
            $table->string('hrd_divisi')->nullable()->after('hrd_jabatan');
        });

        // BAST yang sudah ada diisi dari data user inventory saat ini — sumber yang
        // tersedia sekarang. Begitu identitasnya dikoreksi (lewat HRIS maupun
        // manual), dokumen lama bisa disesuaikan manual bila memang perlu.
        $baris = DB::table('serah_terima_aset')
            ->join('users', 'users.id', '=', 'serah_terima_aset.hrd_id')
            ->leftJoin('job_position', 'job_position.id', '=', 'users.job_position_id')
            ->leftJoin('organization', 'organization.id', '=', 'users.organization_id')
            ->select(
                'serah_terima_aset.id',
                'users.nomor_id',
                'job_position.nama as jabatan',
                'organization.nama as divisi'
            )
            ->get();

        foreach ($baris as $satu) {
            DB::table('serah_terima_aset')->where('id', $satu->id)->update([
                'hrd_nomor_id' => $satu->nomor_id,
                'hrd_jabatan' => $satu->jabatan,
                'hrd_divisi' => $satu->divisi,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('serah_terima_aset', function (Blueprint $table) {
            $table->dropColumn(['hrd_nomor_id', 'hrd_jabatan', 'hrd_divisi']);
        });
    }
};
