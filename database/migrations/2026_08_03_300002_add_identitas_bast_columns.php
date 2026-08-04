<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Identitas yang dicetak di Berita Acara Serah Terima Aset format resmi.
 *
 * Dua hal yang ditambahkan:
 *
 * 1. users.nomor_id — nomor identitas pegawai, mis. 003/HRDCS/II/2026. Format
 *    resmi mencantumkannya untuk PIHAK KEDUA. Disimpan sebagai kolom, bukan
 *    ditulis langsung di blade: menuliskannya di berkas akan mengulang persis
 *    masalah nama GA yang tercetak atas nama karyawan yang sudah keluar.
 *
 * 2. jabatan & divisi terdahulu PIHAK PERTAMA, dibekukan di dokumennya. Format
 *    resmi menyebutnya "Jabatan Terdahulu" dan "Divisi Terdahulu" — jabatan saat
 *    karyawan masih bekerja. Kalau diambil dari relasi user saat mencetak, akun
 *    yang dirapikan setelah karyawan keluar membuat cetak ulang BAST berbeda dari
 *    kertas yang sudah ditandatangani. Alasannya sama dengan atasan_id, ga_id,
 *    dan hrd_id yang sudah lebih dulu dibekukan.
 *
 * Disimpan sebagai teks, bukan foreign key: yang dicetak adalah keadaan pada saat
 * itu, dan nama jabatan di master boleh berubah tanpa mengubah dokumen lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nomor_id')->nullable()->after('email');
        });

        Schema::table('serah_terima_aset', function (Blueprint $table) {
            $table->string('jabatan_terdahulu')->nullable()->after('karyawan_id');
            $table->string('divisi_terdahulu')->nullable()->after('jabatan_terdahulu');
        });

        // Kolom "Tempat" pada format resmi. Wajib dibekukan: menandai BAST selesai
        // mengosongkan ruangan_id di rekap aset, jadi kalau dibaca langsung dari
        // relasinya, dokumen yang dicetak ulang setelah selesai akan kehilangan
        // seluruh isi kolom Tempat-nya.
        Schema::table('serah_terima_aset_details', function (Blueprint $table) {
            $table->string('tempat_serah')->nullable()->after('kondisi_serah');
        });

        $detail = DB::table('serah_terima_aset_details')
            ->join('rekap_aset', 'rekap_aset.id', '=', 'serah_terima_aset_details.rekap_aset_id')
            ->leftJoin('ruangan', 'ruangan.id', '=', 'rekap_aset.ruangan_id')
            ->select('serah_terima_aset_details.id', 'ruangan.nama_ruangan')
            ->get();

        foreach ($detail as $satu) {
            DB::table('serah_terima_aset_details')->where('id', $satu->id)->update([
                'tempat_serah' => $satu->nama_ruangan,
            ]);
        }

        // BAST yang sudah ada diisi dari jabatan & divisi karyawannya saat ini.
        // Untuk dokumen yang belum ditandatangani ini benar; kalau ada yang sudah
        // ditandatangani dengan jabatan lain, kolomnya bisa dikoreksi manual.
        $baris = DB::table('serah_terima_aset')
            ->join('users', 'users.id', '=', 'serah_terima_aset.karyawan_id')
            ->leftJoin('job_position', 'job_position.id', '=', 'users.job_position_id')
            ->leftJoin('organization', 'organization.id', '=', 'users.organization_id')
            ->select(
                'serah_terima_aset.id',
                'job_position.nama as jabatan',
                'organization.nama as divisi'
            )
            ->get();

        foreach ($baris as $satu) {
            DB::table('serah_terima_aset')->where('id', $satu->id)->update([
                'jabatan_terdahulu' => $satu->jabatan,
                'divisi_terdahulu' => $satu->divisi,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('serah_terima_aset_details', function (Blueprint $table) {
            $table->dropColumn('tempat_serah');
        });

        Schema::table('serah_terima_aset', function (Blueprint $table) {
            $table->dropColumn(['jabatan_terdahulu', 'divisi_terdahulu']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nomor_id');
        });
    }
};
