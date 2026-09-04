<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Persempit `lihat-penunjukan-perbaikan-data` ke role yang berkepentingan.
 *
 * Permission ini terlanjur menempel ke 29 role — praktis seluruh sistem. Tab
 * Penunjukan karena itu muncul untuk semua orang, walau isinya kosong: barisnya
 * sendiri sudah disaring PenunjukanPerbaikanDataTable, yang tanpa
 * `lihat-semua-perbaikan-data` hanya menampilkan surat yang menyangkut dirinya.
 *
 * Tab kosong yang muncul di mana-mana bukan cuma berantakan. Ia mengiklankan
 * pintu yang tidak akan pernah bisa dipakai orangnya, dan menambah satu tempat
 * lagi yang harus diperiksa saat menelusuri siapa boleh melihat surat dinas.
 *
 * Empat role yang ditahan:
 *
 * - `superadmin`       memegang semua permission, aturan tetap di sistem ini.
 * - `software`         yang mengerjakan perubahan datanya di database, dan yang
 *                      namanya muncul sebagai kandidat pelaksana di surat.
 * - `software manager` yang menerbitkan dan menandatangani penunjukannya.
 * - `administrasi`     yang mengarsipkan surat dinasnya.
 *
 * Permission-nya TIDAK dihapus, hanya dilepas dari role lain. Menghapusnya akan
 * ikut mencabutnya dari empat role di atas dan mematikan tabnya untuk semua
 * orang.
 *
 * Aturan susulan yang tidak bisa dijaga migration ini: siapa pun yang nanti
 * diberi `eksekusi-perbaikan-data` di luar keempat role ini harus diberi
 * `lihat-penunjukan-perbaikan-data` juga. Dia muncul sebagai kandidat pelaksana
 * — daftar kandidat memang menyertakan pemegang permission eksekusi, bukan role
 * software saja — jadi tanpa itu dia bisa ditunjuk di surat yang tidak bisa
 * dibukanya sendiri.
 */
return new class extends Migration
{
    private const PERMISSION = 'lihat-penunjukan-perbaikan-data';

    private const DITAHAN = [
        'superadmin',
        'software',
        'software manager',
        'administrasi',
    ];

    /**
     * Role yang dilepas, dicatat apa adanya supaya down() bisa mengembalikan
     * keadaan semula persis. Membangunnya ulang saat rollback dari "semua role
     * yang ada sekarang" akan memberi permission ini ke role yang dibuat
     * sesudah migration ini jalan — mengembalikan lebih banyak daripada yang
     * pernah dicabut.
     */
    private const DILEPAS = [
        'purchasing', 'produksi', 'admin', 'rnd', 'publikasi', 'marketing', 'hse', 'op',
        'sekretaris', 'direksi', 'marketing manager', 'administration manager',
        'hardware manager', 'teknisi', 'helper', 'rnd level 3', 'teknisi level 3',
        'marketing level 3', 'purchasing level 3', 'general_affair', 'demo',
        'produksi level 3', 'hrd', 'hrd level 3', 'BD_manager',
    ];

    public function up(): void
    {
        $permissionId = $this->permissionId();

        if (! $permissionId) {
            return;
        }

        $idDitahan = DB::table('roles')->whereIn('name', self::DITAHAN)->pluck('id');

        // Dilepas dari SEMUA role selain yang ditahan, bukan cuma dari daftar
        // DILEPAS. Daftar itu untuk rollback; kalau sebuah role menyusul
        // mendapat permission ini sebelum migration jalan di server lain,
        // menyaring pakai daftar tetap akan meninggalkannya menempel.
        DB::table('role_has_permissions')
            ->where('permission_id', $permissionId)
            ->whereNotIn('role_id', $idDitahan->all())
            ->delete();

        // Yang ditahan dipastikan ada, bukan diasumsikan. Di server yang
        // permission-nya belum pernah ditempelkan ke role software, migration
        // ini tanpa langkah ini justru mematikan tabnya untuk yang butuh.
        $idDitahan->each(function ($roleId) use ($permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        });

        $this->segarkanCachePermission();
    }

    public function down(): void
    {
        $permissionId = $this->permissionId();

        if (! $permissionId) {
            return;
        }

        DB::table('roles')
            ->whereIn('name', self::DILEPAS)
            ->pluck('id')
            ->each(function ($roleId) use ($permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            });

        $this->segarkanCachePermission();
    }

    private function permissionId(): ?int
    {
        $id = DB::table('permissions')
            ->where('name', self::PERMISSION)
            ->where('guard_name', 'web')
            ->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * Buang cache permission Spatie.
     *
     * Wajib: migration ini menulis lewat DB::table(), bukan model Spatie, jadi
     * tidak ada yang menyegarkan cachenya sendiri. Tanpa ini barisnya sudah
     * berubah di database tapi can() masih menjawab dengan nilai lama, dan
     * menunya berubah entah kapan tanpa penjelasan yang kelihatan.
     */
    private function segarkanCachePermission(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
