<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission untuk modul Berita Acara Serah Terima Aset (offboarding).
 *
 * Mengikuti pemetaan role yang sudah dipakai peminjaman aset supaya struktur
 * persetujuan yang dikonfigurasi tim tetap berlaku tanpa penyesuaian ulang.
 */
return new class extends Migration
{
    private array $permissions = [
        'lihat-serah-terima-aset',
        'tambah-serah-terima-aset',
        'approve-atasan-serah-terima-aset',
        'approve-ga-serah-terima-aset',
        'approve-hrd-serah-terima-aset',
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->permissions as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'web'],
                ['category' => 'Serah Terima Aset', 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $ids = DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->pluck('id', 'name');

        $roleGa = $this->roleIdsByName(['superadmin', 'general_affair']);
        $roleHrd = $this->roleIdsByName(['superadmin', 'hrd', 'hrd level 3']);
        $roleAtasan = $this->rolesWithPermission('approve-manager-peminjaman-aset')
            ->merge($this->rolesWithPermission('approve-leader-peminjaman-aset'))
            ->unique();

        // Semua orang yang bisa melihat peminjaman aset juga perlu melihat BAST-nya:
        // karyawan wajib bisa membuka dan menyetujui BAST atas namanya sendiri.
        $roleUmum = $this->rolesWithPermission('lihat-peminjaman-aset')
            ->merge($roleGa)->merge($roleHrd)->merge($roleAtasan)->unique();

        $this->grant($ids['lihat-serah-terima-aset'], $roleUmum);
        $this->grant($ids['tambah-serah-terima-aset'], $roleHrd->merge($roleGa)->unique());
        $this->grant($ids['approve-atasan-serah-terima-aset'], $roleAtasan);
        $this->grant($ids['approve-ga-serah-terima-aset'], $roleGa);
        $this->grant($ids['approve-hrd-serah-terima-aset'], $roleHrd);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function rolesWithPermission(string $name)
    {
        return DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('permissions.name', $name)
            ->where('permissions.guard_name', 'web')
            ->pluck('role_has_permissions.role_id');
    }

    private function roleIdsByName(array $names)
    {
        return DB::table('roles')->whereIn('name', $names)->pluck('id');
    }

    private function grant($permissionId, $roleIds): void
    {
        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }
};
