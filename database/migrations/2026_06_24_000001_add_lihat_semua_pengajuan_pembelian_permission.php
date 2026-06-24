<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['name' => 'lihat-semua-pengajuan-pembelian', 'guard_name' => 'web'],
            ['category' => 'Pengajuan Bahan', 'updated_at' => $now, 'created_at' => $now]
        );

        $permissionId = DB::table('permissions')
            ->where('name', 'lihat-semua-pengajuan-pembelian')
            ->where('guard_name', 'web')
            ->value('id');

        // Role yang selama ini sudah bisa lihat semua pengajuan tetap dapat permission ini.
        DB::table('roles')
            ->whereIn('name', ['superadmin', 'general_affair'])
            ->pluck('id')
            ->each(function ($roleId) use ($permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            });
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', 'lihat-semua-pengajuan-pembelian')
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
