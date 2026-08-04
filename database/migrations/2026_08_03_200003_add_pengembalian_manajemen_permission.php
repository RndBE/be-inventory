<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission untuk mencatat aset ber-PIC yang diserahkan kembali ke manajemen.
 *
 * Dipisah dari edit-rekap-aset karena ini tindakan operasional General Affair,
 * bukan perbaikan data: ada tanggal serah terima, kondisi, dan bukti foto yang
 * dipertanggungjawabkan. Diberikan ke role yang sudah boleh mencatat pengembalian
 * peminjaman, karena pekerjaannya sama — hanya asetnya tidak lewat pengajuan.
 */
return new class extends Migration
{
    private string $permission = 'pengembalian-aset-manajemen';

    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['name' => $this->permission, 'guard_name' => 'web'],
            ['category' => 'Rekap Aset', 'updated_at' => $now, 'created_at' => $now]
        );

        $permissionId = DB::table('permissions')
            ->where('name', $this->permission)
            ->where('guard_name', 'web')
            ->value('id');

        $roleIds = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('permissions.name', 'pengembalian-peminjaman-aset')
            ->where('permissions.guard_name', 'web')
            ->pluck('role_has_permissions.role_id');

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

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
