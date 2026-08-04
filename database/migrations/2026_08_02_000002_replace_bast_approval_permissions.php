<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission approval per tahap BAST diganti satu permission "selesaikan".
 */
return new class extends Migration
{
    private array $dicabut = [
        'approve-atasan-serah-terima-aset',
        'approve-ga-serah-terima-aset',
        'approve-hrd-serah-terima-aset',
    ];

    private string $baru = 'selesaikan-serah-terima-aset';

    public function up(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', $this->dicabut)
            ->where('guard_name', 'web')
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }

        $now = now();
        DB::table('permissions')->updateOrInsert(
            ['name' => $this->baru, 'guard_name' => 'web'],
            ['category' => 'Serah Terima Aset', 'updated_at' => $now, 'created_at' => $now]
        );

        $permissionId = DB::table('permissions')
            ->where('name', $this->baru)->where('guard_name', 'web')->value('id');

        // Yang boleh menyelesaikan sama dengan yang boleh membuat: GA & HRD.
        // Merekalah yang menerima fisik asetnya dan menutup proses offboarding.
        $roleIds = DB::table('roles')
            ->whereIn('name', ['superadmin', 'general_affair', 'hrd', 'hrd level 3'])
            ->pluck('id');

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
        $id = DB::table('permissions')->where('name', $this->baru)->where('guard_name', 'web')->value('id');

        if ($id) {
            DB::table('role_has_permissions')->where('permission_id', $id)->delete();
            DB::table('model_has_permissions')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }

        $now = now();
        $roleGa = DB::table('roles')->whereIn('name', ['superadmin', 'general_affair'])->pluck('id');
        $roleHrd = DB::table('roles')->whereIn('name', ['superadmin', 'hrd', 'hrd level 3'])->pluck('id');

        foreach ($this->dicabut as $nama) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $nama, 'guard_name' => 'web'],
                ['category' => 'Serah Terima Aset', 'updated_at' => $now, 'created_at' => $now]
            );

            $pid = DB::table('permissions')->where('name', $nama)->where('guard_name', 'web')->value('id');
            $target = $nama === 'approve-hrd-serah-terima-aset' ? $roleHrd : $roleGa;

            foreach ($target as $roleId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $pid,
                    'role_id' => $roleId,
                ]);
            }
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
