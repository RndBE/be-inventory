<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mengunci penanda tangan General Affair & HRD pada dokumen BAST.
 *
 * Sebelumnya kedua nama itu dicari ulang setiap kali PDF dicetak, hanya dari
 * kepemilikan role, tanpa menyaring siapa yang masih menjabat. Hasilnya nama GA
 * tercetak atas nama karyawan yang sudah keluar — role-nya belum dicabut, dan
 * id-nya lebih kecil, jadi selalu dia yang terambil lebih dulu.
 *
 * Menyaring yang masih aktif saja belum cukup: selama namanya diambil saat
 * cetak, mutasi jabatan bikin cetak ulang BAST lama memunculkan nama yang beda
 * dari yang tanda tangan basah di kertasnya. Karena itu keduanya dibekukan saat
 * dokumen dibuat, sama seperti atasan_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('serah_terima_aset', function (Blueprint $table) {
            $table->unsignedBigInteger('ga_id')->nullable()->after('atasan_id');
            $table->unsignedBigInteger('hrd_id')->nullable()->after('ga_id');

            // nullOnDelete, bukan cascade: hapusnya akun pejabat tidak boleh
            // ikut menghapus berita acaranya. Kolom kosong bikin PDF mencetak
            // garis tanda tangan tanpa nama, dan itu memang keadaan sebenarnya.
            $table->foreign('ga_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('hrd_id')->references('id')->on('users')->nullOnDelete();
        });

        // BAST yang sudah ada diisi dengan pejabat yang menjabat sekarang. Untuk
        // dokumen yang belum ditandatangani ini benar; kalau ada yang sudah
        // ditandatangani basah dengan nama lain, kolomnya bisa dikoreksi manual.
        $isian = array_filter([
            'ga_id' => $this->pemegangRoleAktif(['general_affair']),
            'hrd_id' => $this->pemegangRoleAktif(['hrd', 'hrd level 3']),
        ]);

        if ($isian !== []) {
            DB::table('serah_terima_aset')->update($isian);
        }
    }

    public function down(): void
    {
        Schema::table('serah_terima_aset', function (Blueprint $table) {
            $table->dropForeign(['ga_id']);
            $table->dropForeign(['hrd_id']);
            $table->dropColumn(['ga_id', 'hrd_id']);
        });
    }

    /**
     * Pemegang role yang statusnya masih Aktif. Sengaja tidak memakai model User
     * supaya migration ini tetap jalan kalau relasi modelnya berubah nanti.
     */
    private function pemegangRoleAktif(array $namaRole): ?int
    {
        return DB::table('users')
            ->join('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', 'App\Models\User');
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn('roles.name', $namaRole)
            ->where('users.status', 'Aktif')
            ->orderBy('users.id')
            ->value('users.id');
    }
};
