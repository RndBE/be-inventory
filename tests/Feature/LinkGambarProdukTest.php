<?php

namespace Tests\Feature;

use App\Livewire\Concerns\MengelolaLinkGambarProduk;
use App\Models\ProdukJadis;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Aksi isi/ubah tautan foto unit di tabel Produk Jadi & Produk Setengah Jadi.
 *
 * Diuji lewat komponen tipis, bukan lewat ProdukJadisTable langsung, supaya yang
 * diuji perilaku trait-nya — aturan hak akses dan validasi tautan — tanpa ikut
 * menyeret seluruh blade tabelnya beserta puluhan relasi yang dirender di sana.
 */
class LinkGambarProdukTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        Schema::create('produk_jadis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi')->nullable();
            $table->string('link_gambar')->nullable();
            $table->timestamps();
        });

        ProdukJadis::create(['kode_transaksi' => 'BE - CST - 110-00024-1/7']);

        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'edit-link-gambar-produk',
            'guard_name' => 'web',
        ]);
        $roleId = DB::table('roles')->insertGetId(['name' => 'gudang', 'guard_name' => 'web']);
        DB::table('role_has_permissions')->insert(['permission_id' => $permissionId, 'role_id' => $roleId]);

        $berhak = User::create(['name' => 'Gudang', 'email' => 'gudang@bejogja.com', 'password' => 'x']);
        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $berhak->id,
        ]);

        User::create(['name' => 'Tamu', 'email' => 'tamu@bejogja.com', 'password' => 'x']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_menyimpan_tautan_drive(): void
    {
        Livewire::actingAs(User::where('email', 'gudang@bejogja.com')->first())
            ->test(TabelUjiLinkGambar::class)
            ->call('editLinkGambar', 1)
            ->assertSet('modalLinkGambarTerbuka', true)
            ->set('linkGambar', 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOp/view')
            ->call('simpanLinkGambar')
            ->assertHasNoErrors()
            ->assertSet('modalLinkGambarTerbuka', false);

        $this->assertSame(
            'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOp/view',
            ProdukJadis::find(1)->link_gambar
        );
    }

    public function test_mengosongkan_tautan_menghapus_isinya(): void
    {
        ProdukJadis::find(1)->update(['link_gambar' => 'https://contoh.test/a.jpg']);

        Livewire::actingAs(User::where('email', 'gudang@bejogja.com')->first())
            ->test(TabelUjiLinkGambar::class)
            ->call('editLinkGambar', 1)
            ->set('linkGambar', '')
            ->call('simpanLinkGambar')
            ->assertHasNoErrors();

        $this->assertNull(ProdukJadis::find(1)->link_gambar);
    }

    /**
     * `javascript:` di atribut href tetap dieksekusi saat tautannya diklik, dan
     * Blade tidak menahannya karena secara teknis itu string yang sah.
     */
    public function test_menolak_skema_javascript(): void
    {
        Livewire::actingAs(User::where('email', 'gudang@bejogja.com')->first())
            ->test(TabelUjiLinkGambar::class)
            ->call('editLinkGambar', 1)
            ->set('linkGambar', 'javascript:alert(document.cookie)')
            ->call('simpanLinkGambar')
            ->assertHasErrors('linkGambar');

        $this->assertNull(ProdukJadis::find(1)->link_gambar);
    }

    public function test_klik_gambar_membuka_modal_pratinjau(): void
    {
        ProdukJadis::find(1)->update(['link_gambar' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOp/view']);

        Livewire::actingAs(User::where('email', 'gudang@bejogja.com')->first())
            ->test(TabelUjiLinkGambar::class)
            ->call('lihatGambar', 1)
            ->assertSet('modalPreviewGambarTerbuka', true)
            ->assertSet('linkPreviewGambar', 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOp/view')
            ->assertSet('judulPreviewGambar', 'BE - CST - 110-00024-1/7')
            ->call('tutupPreviewGambar')
            ->assertSet('modalPreviewGambarTerbuka', false);
    }

    /**
     * Melihat foto bukan kewenangan terpisah — yang boleh membuka halamannya sudah
     * lolos pemeriksaan di level route, dan fotonya tidak lebih rahasia daripada
     * baris yang menampilkannya.
     */
    public function test_pratinjau_tidak_menuntut_permission_edit(): void
    {
        Livewire::actingAs(User::where('email', 'tamu@bejogja.com')->first())
            ->test(TabelUjiLinkGambar::class)
            ->call('lihatGambar', 1)
            ->assertStatus(200)
            ->assertSet('modalPreviewGambarTerbuka', true);
    }

    public function test_user_tanpa_permission_tidak_bisa_menyimpan(): void
    {
        Livewire::actingAs(User::where('email', 'tamu@bejogja.com')->first())
            ->test(TabelUjiLinkGambar::class)
            ->call('editLinkGambar', 1)
            ->assertStatus(403);
    }

    /**
     * Tombolnya disembunyikan lewat @can, tapi aksi Livewire bisa dipanggil
     * langsung dari sisi klien tanpa melewati tombol itu.
     */
    public function test_simpan_tetap_ditolak_walau_modal_dilewati(): void
    {
        Livewire::actingAs(User::where('email', 'tamu@bejogja.com')->first())
            ->test(TabelUjiLinkGambar::class)
            ->set('idLinkGambar', 1)
            ->set('linkGambar', 'https://contoh.test/a.jpg')
            ->call('simpanLinkGambar')
            ->assertStatus(403);

        $this->assertNull(ProdukJadis::find(1)->link_gambar);
    }
}

class TabelUjiLinkGambar extends Component
{
    use MengelolaLinkGambarProduk;

    protected function cariUntukLinkGambar(int $id): ?Model
    {
        return ProdukJadis::find($id);
    }

    public function render()
    {
        return <<<'BLADE'
        <div></div>
        BLADE;
    }
}
