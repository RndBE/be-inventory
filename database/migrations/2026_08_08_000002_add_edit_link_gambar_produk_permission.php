<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission untuk mengisi tautan foto unit di Produk Jadi dan Produk Setengah Jadi.
 *
 * Hanya diberikan ke superadmin di sini. Role lain yang perlu — biasanya gudang
 * dan produksi — ditambahkan lewat halaman Role & Permission, karena penamaan role
 * di sistem ini banyak dan berjenjang, dan menebaknya dari migration berisiko
 * memberi akses ke role yang tidak dimaksud.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['name' => 'edit-link-gambar-produk', 'guard_name' => 'web'],
            ['category' => 'Produksi', 'updated_at' => $now, 'created_at' => $now]
        );

        $permissionId = DB::table('permissions')
            ->where('name', 'edit-link-gambar-produk')
            ->where('guard_name', 'web')
            ->value('id');

        DB::table('roles')
            ->whereIn('name', ['superadmin'])
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
            ->where('name', 'edit-link-gambar-produk')
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
