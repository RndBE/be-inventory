<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menyempitkan lihat-serah-terima-aset ke GA, HRD, dan superadmin saja.
 *
 * Sebelumnya permission ini diwarisi dari lihat-peminjaman-aset (lihat
 * 2026_08_01_200003), sehingga 29 role — marketing, produksi, teknisi, sampai
 * helper — melihat menu BAST di sidebar. Barisnya memang sudah disaring ke
 * dokumen milik sendiri, tapi BAST menyangkut data offboarding: alasan keluar,
 * tanggal efektif, dan jabatan terdahulu. Menunya tidak perlu ada di depan
 * mata seluruh perusahaan.
 *
 * HRD tetap ikut karena dialah PIHAK KEDUA yang menandatangani dokumennya,
 * jadi mencabutnya berarti HRD tidak bisa membuka berkas yang dia tanda
 * tangani sendiri.
 *
 * Konsekuensi yang sudah disepakati: karyawan biasa tidak lagi bisa membuka
 * BAST atas namanya sendiri, dan atasan tidak bisa membuka BAST bawahannya.
 * Penyaringan per-baris di SerahTerimaAsetTable::render() sengaja DIBIARKAN —
 * itu yang membuat pemberian permission ini ke role lain di kemudian hari
 * (lewat halaman Roles) tidak langsung membuka seluruh dokumen kepegawaian.
 */
return new class extends Migration
{
    private string $permission = 'lihat-serah-terima-aset';

    private array $rolesDipertahankan = ['superadmin', 'general_affair', 'hrd', 'hrd level 3'];

    public function up(): void
    {
        $permissionId = $this->permissionId();

        if (!$permissionId) {
            return;
        }

        $idDipertahankan = DB::table('roles')
            ->whereIn('name', $this->rolesDipertahankan)
            ->pluck('id');

        DB::table('role_has_permissions')
            ->where('permission_id', $permissionId)
            ->whereNotIn('role_id', $idDipertahankan)
            ->delete();

        // Sekaligus memastikan keempat role itu benar-benar punya, bukan hanya
        // "tidak tercabut" — kalau salah satunya belum pernah diberi, menunya
        // hilang justru dari pihak yang paling butuh.
        foreach ($idDipertahankan as $roleId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        // Pemberian langsung ke user (di luar role) ikut dicabut, supaya
        // pembatasannya tidak bisa dilewati lewat jalur itu.
        DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Memulihkan pola lama: semua role pemegang lihat-peminjaman-aset ikut
     * bisa melihat BAST. Dihitung ulang dari keadaan saat rollback, bukan dari
     * daftar yang dibekukan di sini — daftar role bisa berubah di antaranya.
     */
    public function down(): void
    {
        $permissionId = $this->permissionId();

        if (!$permissionId) {
            return;
        }

        $roleIds = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('permissions.name', 'lihat-peminjaman-aset')
            ->where('permissions.guard_name', 'web')
            ->pluck('role_has_permissions.role_id')
            ->merge(DB::table('roles')->whereIn('name', $this->rolesDipertahankan)->pluck('id'))
            ->unique();

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function permissionId(): ?int
    {
        return DB::table('permissions')
            ->where('name', $this->permission)
            ->where('guard_name', 'web')
            ->value('id');
    }
};
