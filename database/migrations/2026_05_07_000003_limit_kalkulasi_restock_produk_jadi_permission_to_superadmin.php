<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['name' => 'lihat-kalkulasi-restock-produk-jadi', 'guard_name' => 'web'],
            ['updated_at' => $now, 'created_at' => $now]
        );

        $permissionId = DB::table('permissions')
            ->where('name', 'lihat-kalkulasi-restock-produk-jadi')
            ->where('guard_name', 'web')
            ->value('id');

        $superadminRoleId = DB::table('roles')
            ->where('name', 'superadmin')
            ->value('id');

        DB::table('role_has_permissions')
            ->where('permission_id', $permissionId)
            ->when($superadminRoleId, function ($query) use ($superadminRoleId) {
                $query->where('role_id', '!=', $superadminRoleId);
            })
            ->delete();

        if ($superadminRoleId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $superadminRoleId,
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
