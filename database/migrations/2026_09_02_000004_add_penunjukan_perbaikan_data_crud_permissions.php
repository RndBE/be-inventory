<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Melengkapi permission tab Penunjukan jadi satu set CRUD utuh.
 *
 * Migration 2026_09_02_000002 hanya membuat `tambah-` dan
 * `isi-pelaksanaan-`. Tiga sisanya ditambahkan di sini mengikuti penamaan yang
 * dipakai seluruh modul lain — `lihat-`, `edit-`, `hapus-` — supaya halaman
 * Role & Permission menampilkannya satu kelompok dan tidak ada satu tindakan
 * yang izinnya harus ditebak dari izin tindakan lain.
 *
 * Cara pemberiannya sengaja berbeda antara baca dan tulis:
 *
 * - `lihat-penunjukan-perbaikan-data` diberikan ke SEMUA role yang sudah
 *   memegang `lihat-perbaikan-data`, bukan ke daftar role yang ditebak dari
 *   sini. Yang sudah boleh melihat pengajuannya sudah berada di dalam alur ini,
 *   dan pelaksana yang ditunjuk termasuk di antaranya. Kalau izin baca ini
 *   hanya diberikan ke superadmin, pelaksana tidak bisa membuka surat yang
 *   menugaskannya sampai ada yang ingat memberikannya — penunjukan yang tidak
 *   bisa dikerjakan. Penyaringan siapa melihat surat siapa tetap dikerjakan
 *   App\Livewire\PenunjukanPerbaikanDataTable: yang tidak boleh melihat semua
 *   hanya melihat surat yang menyangkut dirinya, sebagai pengaju atau pelaksana.
 * - `edit-` dan `hapus-` hanya ke superadmin. Keduanya mengubah dan menghapus
 *   surat yang jadi dasar wewenang orang lain menyentuh data; role lain
 *   ditambahkan lewat halaman Role & Permission, karena penamaan role di sistem
 *   ini banyak dan berjenjang dan menebaknya dari migration berisiko memberi
 *   akses ke role yang tidak dimaksud.
 */
return new class extends Migration
{
    /**
     * Permission baru => role mana yang langsung diberi.
     *
     * 'warisan:<permission>' berarti: berikan ke setiap role yang sudah
     * memegang permission itu.
     */
    private const PERMISSION = [
        'lihat-penunjukan-perbaikan-data' => 'warisan:lihat-perbaikan-data',
        'edit-penunjukan-perbaikan-data' => 'superadmin',
        'hapus-penunjukan-perbaikan-data' => 'superadmin',
    ];

    private const KATEGORI = 'Perbaikan Data';

    public function up(): void
    {
        $now = now();

        // `permissions.category` tidak pernah dibuat oleh migration mana pun —
        // kolomnya ditambahkan langsung di database produksi. Di database yang
        // dibangun murni dari riwayat migration kolom itu tidak ada, dan
        // menulisnya akan menggagalkan migration ini dengan "Unknown column
        // 'category'". Kategorinya hanya untuk mengelompokkan tampilan di
        // halaman Role & Permission, jadi tidak ada yang hilang kalau dilewati.
        $punyaKategori = $this->punyaKolom('permissions', 'category');

        foreach (self::PERMISSION as $nama => $penerima) {
            $nilai = ['updated_at' => $now, 'created_at' => $now];

            if ($punyaKategori) {
                $nilai['category'] = self::KATEGORI;
            }

            DB::table('permissions')->updateOrInsert(
                ['name' => $nama, 'guard_name' => 'web'],
                $nilai
            );

            $permissionId = $this->idPermission($nama);

            if (! $permissionId) {
                continue;
            }

            foreach ($this->rolePenerima($penerima) as $roleId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }

        $this->segarkanCachePermission();
    }

    public function down(): void
    {
        foreach (array_keys(self::PERMISSION) as $nama) {
            $permissionId = $this->idPermission($nama);

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
     * @return array<int, int> id role yang diberi permission
     */
    private function rolePenerima(string $penerima): array
    {
        if (! str_starts_with($penerima, 'warisan:')) {
            return DB::table('roles')->where('name', $penerima)->pluck('id')->all();
        }

        $sumber = $this->idPermission(substr($penerima, strlen('warisan:')));

        // Superadmin selalu ikut, termasuk kalau permission sumbernya belum ada
        // di database ini — supaya minimal ada satu role yang bisa membuka
        // halamannya setelah migration jalan.
        $role = DB::table('roles')->where('name', 'superadmin')->pluck('id')->all();

        if ($sumber) {
            $role = array_merge(
                $role,
                DB::table('role_has_permissions')->where('permission_id', $sumber)->pluck('role_id')->all()
            );
        }

        return array_values(array_unique(array_map('intval', $role)));
    }

    private function idPermission(string $nama): ?int
    {
        $id = DB::table('permissions')
            ->where('name', $nama)
            ->where('guard_name', 'web')
            ->value('id');

        return $id ? (int) $id : null;
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
