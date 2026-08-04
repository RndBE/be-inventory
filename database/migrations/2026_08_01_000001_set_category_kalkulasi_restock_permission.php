<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permission 'lihat-kalkulasi-restock-produk-jadi' dibuat tanpa kolom category,
     * sehingga di layar Role ia muncul di bawah judul kelompok yang kosong.
     *
     * Ditempatkan ke kategori "Produk Jadi" yang sudah ada, bukan bikin kategori
     * baru beranggota satu, karena menunya memang bagian dari produk jadi.
     * Hak aksesnya tidak diubah — tetap hanya purchasing, purchasing level 3,
     * dan superadmin.
     */
    private string $permission = 'lihat-kalkulasi-restock-produk-jadi';

    public function up(): void
    {
        DB::table('permissions')
            ->where('name', $this->permission)
            ->where('guard_name', 'web')
            ->update([
                'category' => 'Produk Jadi',
                'updated_at' => now(),
            ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('name', $this->permission)
            ->where('guard_name', 'web')
            ->update([
                'category' => null,
                'updated_at' => now(),
            ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
