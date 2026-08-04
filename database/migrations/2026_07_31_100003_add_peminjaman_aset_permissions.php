<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        'lihat-peminjaman-aset',
        'tambah-peminjaman-aset',
        'hapus-peminjaman-aset',
        'approve-leader-peminjaman-aset',
        'approve-manager-peminjaman-aset',
        'approve-ga-peminjaman-aset',
        'pengembalian-peminjaman-aset',
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->permissions as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'web'],
                ['category' => 'Peminjaman Aset', 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $ids = DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->pluck('id', 'name');

        // Role approver Leader & Manager mengikuti yang sudah dipakai alur pengajuan pembelian,
        // supaya struktur persetujuan yang sudah dikonfigurasi tim tetap berlaku.
        $roleLeader = $this->rolesWithPermission('edit-approvepembelian-leader');
        $roleManager = $this->rolesWithPermission('edit-approve-manager');
        $roleLihatAset = $this->rolesWithPermission('lihat-rekap-aset');
        $roleGa = $this->roleIdsByName(['superadmin', 'general_affair']);

        // Semua calon peminjam & approver perlu bisa melihat dan membuat pengajuan.
        $roleUmum = $roleLihatAset->merge($roleLeader)->merge($roleManager)->merge($roleGa)->unique();

        $this->grant($ids['lihat-peminjaman-aset'], $roleUmum);
        $this->grant($ids['tambah-peminjaman-aset'], $roleUmum);
        $this->grant($ids['approve-leader-peminjaman-aset'], $roleLeader);
        $this->grant($ids['approve-manager-peminjaman-aset'], $roleManager);
        $this->grant($ids['approve-ga-peminjaman-aset'], $roleGa);
        $this->grant($ids['pengembalian-peminjaman-aset'], $roleGa);
        $this->grant($ids['hapus-peminjaman-aset'], $roleGa);

        // Permission ditulis lewat query mentah, jadi cache Spatie harus dibuang
        // supaya @can() langsung mengenali permission baru tanpa perlu perintah manual.
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
