<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Meminjam aset adalah kebutuhan semua karyawan, bukan hanya level tertentu.
     * Migration sebelumnya menurunkan hak akses dari permission rekap aset & approval
     * pembelian, sehingga role staf seperti rnd, software, produksi, dan helper
     * sama sekali tidak bisa membuka layar peminjaman — artinya tidak bisa mengajukan.
     *
     * Yang dibuka hanya "lihat daftar" dan "buat pengajuan". Persetujuan tetap
     * dijaga permission approve-* yang tidak diubah di sini.
     */
    private array $permissions = [
        'lihat-peminjaman-aset',
        'tambah-peminjaman-aset',
    ];

    public function up(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->pluck('id');

        $roleIds = DB::table('roles')->pluck('id');

        foreach ($permissionIds as $permissionId) {
            foreach ($roleIds as $roleId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Tidak mengembalikan sebagian role saja, karena tidak ada catatan
        // role mana yang sebelumnya punya. Cukup lepas dari semua role;
        // migration pembuat permission yang akan memberikannya lagi bila di-rerun.
        $permissionIds = DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
