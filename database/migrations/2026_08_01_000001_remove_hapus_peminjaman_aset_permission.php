<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mencabut permission hapus pengajuan peminjaman aset.
 *
 * Menghapus header peminjaman ikut menghapus seluruh detailnya lewat cascade —
 * termasuk tanggal kembali, kondisi, dan bukti foto yang dicatat General Affair —
 * sekaligus melubangi riwayat peminjaman di Rekapitulasi Aset. Route dan
 * controllernya sudah dibuang, jadi permission ini tidak lagi menjaga apa pun
 * dan hanya akan membingungkan kalau tetap muncul di layar pengaturan Role.
 */
return new class extends Migration
{
    private const PERMISSION = 'hapus-peminjaman-aset';

    public function up(): void
    {
        $id = DB::table('permissions')
            ->where('name', self::PERMISSION)
            ->where('guard_name', 'web')
            ->value('id');

        if (!$id) {
            return;
        }

        DB::table('role_has_permissions')->where('permission_id', $id)->delete();
        DB::table('model_has_permissions')->where('permission_id', $id)->delete();
        DB::table('permissions')->where('id', $id)->delete();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Rollback hanya mengembalikan permission-nya ke superadmin & general_affair,
     * sesuai pemberian awal. Route dan method destroy tetap harus dikembalikan
     * manual kalau fitur ini memang mau dihidupkan lagi.
     */
    public function down(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['name' => self::PERMISSION, 'guard_name' => 'web'],
            ['category' => 'Peminjaman Aset', 'updated_at' => $now, 'created_at' => $now]
        );

        $id = DB::table('permissions')
            ->where('name', self::PERMISSION)
            ->where('guard_name', 'web')
            ->value('id');

        $roleIds = DB::table('roles')->whereIn('name', ['superadmin', 'general_affair'])->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $id,
                'role_id' => $roleId,
            ]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
