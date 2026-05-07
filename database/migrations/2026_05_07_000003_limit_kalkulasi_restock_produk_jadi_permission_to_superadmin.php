<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'lihat-kalkulasi-restock-produk-jadi',
            'guard_name' => 'web',
        ]);

        Role::all()->each(function (Role $role) use ($permission) {
            if ($role->name === 'superadmin') {
                $role->givePermissionTo($permission);
                return;
            }

            if ($role->hasPermissionTo($permission)) {
                $role->revokePermissionTo($permission);
            }
        });
    }

    public function down(): void
    {
        //
    }
};
