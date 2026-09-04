<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission untuk eksekusi perbaikan data dan halaman jejaknya.
 *
 * Dipisah dari `approve-perbaikan-data` yang sudah ada. Menyetujui dan
 * menerapkan adalah dua tindakan berbeda: kalau digabung, kegagalan penerapan
 * (nilainya sudah berubah orang lain, lotnya sudah terpakai) meninggalkan tiket
 * berstatus "disetujui" yang datanya tidak berubah — status yang tidak jujur.
 * Dipisah juga berarti nanti bisa dipegang dua orang tanpa migration baru.
 *
 * `lihat-audit-perubahan-data` membuka halaman jejaknya. Read-only, dan justru
 * perlu dilihat lebih banyak orang daripada yang boleh mengubah — accounting dan
 * direksi termasuk.
 *
 * Keduanya hanya diberikan ke superadmin di sini. Role lain ditambahkan lewat
 * halaman Role & Permission: penamaan role di sistem ini banyak dan berjenjang,
 * dan menebaknya dari migration berisiko memberi akses ke role yang tidak
 * dimaksud. Maritza masuk sebagai pemegang permission lewat halaman itu, bukan
 * sebagai nama di dalam kode — hardcode nama orang sudah jadi utang di
 * BahanKeluarController dan tidak perlu ditambah.
 */
return new class extends Migration
{
    private const PERMISSION = [
        'eksekusi-perbaikan-data' => 'Perbaikan Data',
        'lihat-audit-perubahan-data' => 'Perbaikan Data',
    ];

    public function up(): void
    {
        $now = now();

        // `permissions.category` tidak pernah dibuat oleh migration mana pun —
        // kolomnya ditambahkan langsung di database produksi. Jadi di database
        // yang dibangun murni dari riwayat migration kolom itu tidak ada, dan
        // menulisnya akan menggagalkan migration ini dengan "Unknown column
        // 'category'". Kategorinya hanya dipakai untuk mengelompokkan tampilan
        // di halaman Role & Permission, jadi tidak ada yang hilang kalau
        // dilewati.
        $punyaKategori = $this->punyaKolom('permissions', 'category');

        foreach (self::PERMISSION as $nama => $kategori) {
            $nilai = ['updated_at' => $now, 'created_at' => $now];

            if ($punyaKategori) {
                $nilai['category'] = $kategori;
            }

            DB::table('permissions')->updateOrInsert(
                ['name' => $nama, 'guard_name' => 'web'],
                $nilai
            );

            $permissionId = DB::table('permissions')
                ->where('name', $nama)
                ->where('guard_name', 'web')
                ->value('id');

            if (! $permissionId) {
                continue;
            }

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

        $this->segarkanCachePermission();
    }

    public function down(): void
    {
        foreach (array_keys(self::PERMISSION) as $nama) {
            $permissionId = DB::table('permissions')
                ->where('name', $nama)
                ->where('guard_name', 'web')
                ->value('id');

            if (! $permissionId) {
                continue;
            }

            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        $this->segarkanCachePermission();
    }

    /**
     * Apakah tabel ini punya kolom tersebut.
     *
     * Memakai query information_schema seadanya, bukan Schema::hasColumn():
     * introspeksi skema Laravel 11 ikut membaca kolom `generation_expression`
     * yang belum ada di MySQL/MariaDB versi server produksi.
     */
    private function punyaKolom(string $tabel, string $kolom): bool
    {
        return (int) DB::selectOne(
            'select count(*) as jumlah from information_schema.columns
             where table_schema = database() and table_name = ? and column_name = ?',
            [$tabel, $kolom]
        )->jumlah > 0;
    }

    /**
     * Buang cache permission Spatie.
     *
     * Wajib dipanggil karena migration ini menulis ke tabel `permissions` dan
     * `role_has_permissions` lewat DB::table(), bukan lewat model Spatie — jadi
     * tidak ada yang otomatis menyegarkan cachenya. Tanpa ini, barisnya sudah
     * ada di database tapi can() masih menjawab false sampai cachenya
     * kedaluwarsa, dan menunya hilang tanpa penjelasan yang kelihatan di mana
     * pun. Persis itu yang terjadi setelah migration ini pertama kali jalan.
     */
    private function segarkanCachePermission(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
