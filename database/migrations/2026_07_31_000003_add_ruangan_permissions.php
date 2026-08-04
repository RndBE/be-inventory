<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permission untuk master data Ruangan Aset.
     */
    private array $permissions = [
        'lihat-ruangan',
        'tambah-ruangan',
        'edit-ruangan',
        'hapus-ruangan',
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->permissions as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'web'],
                ['category' => 'Ruangan Aset', 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->pluck('id', 'name');

        // Role yang sudah boleh melihat rekap aset otomatis boleh melihat daftar ruangan.
        $roleIdsLihat = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('permissions.name', 'lihat-rekap-aset')
            ->where('permissions.guard_name', 'web')
            ->pluck('role_has_permissions.role_id');

        foreach ($roleIdsLihat as $roleId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionIds['lihat-ruangan'],
                'role_id' => $roleId,
            ]);
        }

        // Role yang boleh mengubah rekap aset juga boleh mengelola master ruangan.
        $roleIdsKelola = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('permissions.name', 'tambah-rekap-aset')
            ->where('permissions.guard_name', 'web')
            ->pluck('role_has_permissions.role_id');

        foreach ($roleIdsKelola as $roleId) {
            foreach (['tambah-ruangan', 'edit-ruangan', 'hapus-ruangan'] as $name) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionIds[$name],
                    'role_id' => $roleId,
                ]);
            }
        }

        // Permission ditulis lewat query mentah, jadi cache Spatie harus dibuang
        // supaya @can() langsung mengenali permission baru tanpa perlu perintah manual.
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};
