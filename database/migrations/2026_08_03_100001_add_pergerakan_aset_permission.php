<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission untuk halaman pemantauan Pergerakan Aset.
 *
 * Halamannya murni baca, jadi cukup satu permission tanpa tambah/edit/hapus.
 * Isinya riwayat perpindahan PIC & ruangan seluruh aset — sudah bisa dilihat
 * per aset lewat modal riwayat di Rekap Aset, halaman ini hanya menyatukannya.
 */
return new class extends Migration
{
    private string $permission = 'lihat-pergerakan-aset';

    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['name' => $this->permission, 'guard_name' => 'web'],
            ['category' => 'Pergerakan Aset', 'updated_at' => $now, 'created_at' => $now]
        );

        $permissionId = DB::table('permissions')
            ->where('name', $this->permission)
            ->where('guard_name', 'web')
            ->value('id');

        // Mengikuti 'lihat-rekap-aset': datanya memang riwayat aset yang sama, jadi
        // siapa pun yang sudah boleh melihat rekap aset tidak mendapat akses ke
        // informasi baru — hanya cara pandang yang berbeda atas data yang sama.
        $roleIds = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('permissions.name', 'lihat-rekap-aset')
            ->where('permissions.guard_name', 'web')
            ->pluck('role_has_permissions.role_id');

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        // Permission ditulis lewat query mentah, jadi cache Spatie harus dibuang
        // supaya @can() langsung mengenalinya tanpa perintah manual.
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', $this->permission)
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
