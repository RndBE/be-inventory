<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permission untuk tahap "Mengetahui" HRD pada peminjaman aset.
     */
    private string $permission = 'approve-hrd-peminjaman-aset';

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

        $roleIds = DB::table('roles')
            ->whereIn('name', ['superadmin', 'hrd', 'hrd level 3'])
            ->pluck('id');

        // HRD juga perlu bisa membuka layar approval untuk menjalankan tahap ini.
        $lihatApprovalId = DB::table('permissions')
            ->where('name', 'lihat-approval-peminjaman-aset')
            ->where('guard_name', 'web')
            ->value('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);

            if ($lihatApprovalId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $lihatApprovalId,
                    'role_id' => $roleId,
                ]);
            }
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
