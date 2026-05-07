<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $newPermission = Permission::firstOrCreate([
            'name' => 'lihat-kalkulasi-restock-produk-jadi',
            'guard_name' => 'web',
        ]);

        Role::all()->each(function (Role $role) use ($newPermission) {
            if (in_array($role->name, ['superadmin', 'admin'], true)) {
                $role->givePermissionTo($newPermission);
            }
        });
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'lihat-kalkulasi-restock-produk-jadi')->first();

        if ($permission) {
            $permission->delete();
        }
    }
};
