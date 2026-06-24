<?php

namespace Tests\Feature;

use Tests\TestCase;

class LihatSemuaPengajuanPembelianPermissionTest extends TestCase
{
    public function test_migration_creates_permission_and_assigns_to_see_all_roles(): void
    {
        $files = glob(database_path('migrations/*_add_lihat_semua_pengajuan_pembelian_permission.php'));
        $this->assertNotEmpty($files, 'Migration permission lihat-semua-pengajuan-pembelian tidak ditemukan.');

        $source = file_get_contents($files[0]);

        // Permission dibuat dengan guard 'web' dan category supaya muncul di halaman role.
        $this->assertStringContainsString("'name' => 'lihat-semua-pengajuan-pembelian'", $source);
        $this->assertStringContainsString("'guard_name' => 'web'", $source);
        $this->assertStringContainsString("'category' => 'Pengajuan Bahan'", $source);

        // Role yang sudah bisa lihat semua tetap diberi permission ini.
        $this->assertStringContainsString("['superadmin', 'general_affair']", $source);
        $this->assertStringContainsString('role_has_permissions', $source);

        // down() membersihkan permission & relasinya.
        $this->assertStringContainsString('function down', $source);
        $this->assertStringContainsString("->where('permission_id', \$permissionId)->delete()", $source);
    }

    public function test_table_component_gates_visibility_by_role_or_permission(): void
    {
        $source = file_get_contents(app_path('Livewire/PengajuanPembelianTable.php'));

        // Tetap ada daftar role yang boleh lihat semua.
        $this->assertStringContainsString("'superadmin'", $source);
        $this->assertStringContainsString("'general_affair'", $source);

        // Boleh lihat semua jika punya role ATAU permission baru.
        $this->assertStringContainsString('$user->hasAnyRole($rolesCanSeeAll)', $source);
        $this->assertStringContainsString("\$user->can('lihat-semua-pengajuan-pembelian')", $source);

        // Kalau tidak boleh lihat semua, dibatasi ke pengajuan sendiri + user tertentu.
        $this->assertStringContainsString('if (!$canSeeAll)', $source);
        $this->assertStringContainsString('$user->pengajuanViewableUsers()', $source);
        $this->assertStringContainsString("\$pembelian_bahan->whereIn('pengaju', \$allowedPengaju)", $source);
    }

    public function test_user_model_has_pengajuan_viewable_users_relationship(): void
    {
        $source = file_get_contents(app_path('Models/User.php'));

        $this->assertStringContainsString('function pengajuanViewableUsers', $source);
        $this->assertStringContainsString(
            "belongsToMany(User::class, 'pengajuan_pembelian_viewers', 'viewer_id', 'target_id')",
            $source
        );
    }

    public function test_viewers_pivot_migration_exists(): void
    {
        $files = glob(database_path('migrations/*_create_pengajuan_pembelian_viewers_table.php'));
        $this->assertNotEmpty($files, 'Migration tabel pengajuan_pembelian_viewers tidak ditemukan.');

        $source = file_get_contents($files[0]);
        $this->assertStringContainsString("Schema::create('pengajuan_pembelian_viewers'", $source);
        $this->assertStringContainsString("\$table->unique(['viewer_id', 'target_id'])", $source);
    }

    public function test_user_edit_flow_manages_viewable_users(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/UserController.php'));
        $this->assertStringContainsString("'pengajuan_viewable_users' => 'nullable|array'", $controller);
        $this->assertStringContainsString('$user->pengajuanViewableUsers()->sync($viewableIds)', $controller);

        $view = file_get_contents(resource_path('views/pages/user/edit.blade.php'));
        $this->assertStringContainsString('name="pengajuan_viewable_users[]"', $view);
    }
}
