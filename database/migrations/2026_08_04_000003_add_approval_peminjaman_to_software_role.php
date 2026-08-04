<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Role 'software' ikut memegang permission approval peminjaman aset.
 *
 * Divisi Software satu-satunya yang leader-nya tidak bisa membuka layar
 * approval: FADEL ber-job_level 3 dan sudah tercatat sebagai atasan_level3_id
 * keempat staf software, tapi role 'software' tidak memegang permission-nya
 * sama sekali. Role 'administrasi' sudah sejak awal memegang keduanya, jadi
 * ini menyamakan software dengan pola yang sudah berjalan di sana.
 *
 * Permission dipegang per-role sementara jenjang tersimpan per-user di
 * job_level, sehingga pemberian ini otomatis juga menyentuh staf level 4.
 * Yang menahan mereka BUKAN migration ini, melainkan
 * PeminjamanAset::bolehBukaLayarApproval() — batas job_level dipasang di sana
 * supaya berlaku seragam untuk semua role, bukan ditambal per divisi di sini.
 */
return new class extends Migration
{
    private string $role = 'software';

    private array $permissions = [
        'lihat-approval-peminjaman-aset',
        'approve-leader-peminjaman-aset',
    ];

    public function up(): void
    {
        $roleId = $this->roleId();

        if (!$roleId) {
            return;
        }

        foreach ($this->permissionIds() as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $roleId = $this->roleId();

        if (!$roleId) {
            return;
        }

        DB::table('role_has_permissions')
            ->where('role_id', $roleId)
            ->whereIn('permission_id', $this->permissionIds())
            ->delete();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function roleId(): ?int
    {
        return DB::table('roles')->where('name', $this->role)->where('guard_name', 'web')->value('id');
    }

    private function permissionIds()
    {
        return DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->pluck('id');
    }
};
