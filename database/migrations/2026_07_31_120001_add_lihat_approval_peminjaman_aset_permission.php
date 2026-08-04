<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permission untuk membuka layar approval peminjaman aset.
     * Mengikuti pola pengajuan pembelian: layar pemohon (lihat-peminjaman-aset)
     * dipisah dari layar approver (lihat-approval-peminjaman-aset).
     */
    private string $permission = 'lihat-approval-peminjaman-aset';

    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['name' => $this->permission, 'guard_name' => 'web'],
            ['category' => 'Peminjaman Aset', 'updated_at' => $now, 'created_at' => $now]
        );

        $permissionId = DB::table('permissions')
            ->where('name', $this->permission)
            ->where('guard_name', 'web')
            ->value('id');

        // Semua role yang punya wewenang approve di tahap mana pun boleh membuka layar ini.
        $roleIds = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->whereIn('permissions.name', [
                'approve-leader-peminjaman-aset',
                'approve-manager-peminjaman-aset',
                'approve-ga-peminjaman-aset',
                'pengembalian-peminjaman-aset',
            ])
            ->where('permissions.guard_name', 'web')
            ->distinct()
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
