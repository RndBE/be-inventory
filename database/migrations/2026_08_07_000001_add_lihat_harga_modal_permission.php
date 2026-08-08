<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission khusus untuk melihat harga modal (HPP) produk.
 *
 * Sengaja dibuat terpisah, tidak menumpang `lihat-produk-jadi` atau
 * `detail-bahan-setengahjadi`. Melihat daftar produk dan melihat biaya
 * produksinya adalah dua kewenangan yang berbeda, dan yang kedua perlu bisa
 * dicabut tanpa ikut mencabut yang pertama.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['name' => 'lihat-harga-modal', 'guard_name' => 'web'],
            ['category' => 'Produksi', 'updated_at' => $now, 'created_at' => $now]
        );

        $permissionId = DB::table('permissions')
            ->where('name', 'lihat-harga-modal')
            ->where('guard_name', 'web')
            ->value('id');

        DB::table('roles')
            ->whereIn('name', ['superadmin', 'marketing manager'])
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
            ->where('name', 'lihat-harga-modal')
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
