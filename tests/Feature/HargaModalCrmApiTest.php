<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HargaModalCrmApiTest extends TestCase
{
    private const KEY = 'kunci-crm-untuk-test';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('services.crm.key', self::KEY);
        config()->set('app.url', 'https://inventory.test');

        $this->buatTabelPermission();
        $this->buatTabelProduk();
        $this->isiData();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_menolak_permintaan_tanpa_api_key(): void
    {
        $this->getJson('/api/crm/harga-modal?email=marketing@bejogja.com')
            ->assertStatus(401);
    }

    public function test_menolak_api_key_yang_salah(): void
    {
        $this->withHeader('X-API-KEY', 'kunci-palsu')
            ->getJson('/api/crm/harga-modal?email=marketing@bejogja.com')
            ->assertStatus(401);
    }

    public function test_email_wajib_diisi(): void
    {
        $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal')
            ->assertStatus(422);
    }

    public function test_email_tidak_terdaftar_ditolak(): void
    {
        $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal?email=orangasing@example.com')
            ->assertStatus(404);
    }

    /**
     * Lapis kedua otorisasi. Tanpa ini, siapa pun yang memegang API key bisa
     * mengganti email di query string dan tetap menerima seluruh harga modal.
     */
    public function test_user_tanpa_permission_ditolak(): void
    {
        $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal?email=gudang@bejogja.com')
            ->assertStatus(403);
    }

    public function test_pencocokan_email_tidak_membedakan_huruf_besar_kecil(): void
    {
        $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal?email=MARKETING@BEJOGJA.COM')
            ->assertStatus(200);
    }

    public function test_mengembalikan_harga_modal_per_unit_untuk_kedua_tab(): void
    {
        $response = $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal?email=marketing@bejogja.com')
            ->assertStatus(200);

        $response->assertJsonPath('produk_jadi.jumlah_unit', 2);
        $response->assertJsonPath('produk_setengah_jadi.jumlah_unit', 1);

        // Unit terbaru lebih dulu: yang masuk gudang 15 Juli.
        $response->assertJsonPath('produk_jadi.data.0.harga_modal_satuan', 12530171.82);
        $response->assertJsonPath('produk_jadi.data.0.kode_produksi', 'BE - CST - 110-00024');
        $response->assertJsonPath('produk_jadi.data.0.kode_unit', 'BE - CST - 110-00024-1/7');
        $response->assertJsonPath('produk_jadi.data.0.sumber', 'Produksi');

        $response->assertJsonPath('produk_setengah_jadi.data.0.harga_modal_satuan', 389299.68);
        $response->assertJsonPath('produk_setengah_jadi.data.0.nama_produk', 'BE-MSCAM V.0');
        // Wajib dicek: join ke tabel produksi pernah salah nama dan lolos begitu saja
        // karena kolom ini tidak pernah diassert.
        $response->assertJsonPath('produk_setengah_jadi.data.0.kode_produksi', 'PRD-20260413135218');
        $response->assertJsonPath('produk_setengah_jadi.data.0.sumber', 'Produksi');
    }

    public function test_unit_dari_produk_sample_ditandai_dari_produk_sample_id(): void
    {
        $response = $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal?email=marketing@bejogja.com')
            ->assertStatus(200);

        // Baris kedua sengaja dibuat berasal dari produk sample, dan penandanya
        // hanya ada di daftar QC — bukan di tabel stok.
        $response->assertJsonPath('produk_jadi.data.1.sumber', 'Produk Sample');
        // Kode produksinya pun harus tetap ketemu lewat daftar QC.
        $response->assertJsonPath('produk_jadi.data.1.kode_produksi', 'BE - CST - 110-00024');
    }

    public function test_ringkasan_membawa_rentang_harga_antar_batch(): void
    {
        $response = $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal?email=marketing@bejogja.com')
            ->assertStatus(200);

        $ringkasan = $response->json('produk_jadi.ringkasan.0');

        $this->assertSame('BE - CST - 110', $ringkasan['nama_produk']);
        $this->assertSame(2, $ringkasan['jumlah_unit']);
        // assertEquals, bukan assertSame: JSON menurunkan float bulat jadi integer.
        $this->assertEquals(12530171.82, $ringkasan['harga_modal_terakhir']);
        $this->assertEquals(9000000, $ringkasan['harga_modal_terendah']);
        $this->assertEquals(12530171.82, $ringkasan['harga_modal_tertinggi']);
        // Hanya unit kedua yang stoknya masih ada.
        $this->assertEquals(1, $ringkasan['stok_tersedia']);
    }

    public function test_hanya_tersedia_menyaring_unit_yang_stoknya_habis(): void
    {
        $response = $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal?email=marketing@bejogja.com&hanya_tersedia=1')
            ->assertStatus(200);

        $response->assertJsonPath('produk_jadi.jumlah_unit', 1);
        $response->assertJsonPath('produk_jadi.data.0.sumber', 'Produk Sample');
    }

    public function test_tab_bahan_memakai_harga_beli_terakhir_dan_rata_rata_tertimbang(): void
    {
        $response = $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal?email=marketing@bejogja.com&tab=bahan')
            ->assertStatus(200);

        // Hanya bahan yang pernah dibeli. 'Bahan Tanpa Pembelian' tidak boleh ikut.
        $response->assertJsonPath('bahan.jumlah_bahan', 2);
        $response->assertJsonMissing(['nama_produk' => 'Bahan Tanpa Pembelian']);

        // Urut menurut nama: BE-MSCAM V.0 lalu Kabel UTP.
        $kabel = collect($response->json('bahan.data'))->firstWhere('nama_produk', 'Kabel UTP');

        $this->assertSame('KBL-UTP', $kabel['kode_produk']);
        $this->assertSame('Pcs', $kabel['unit']);
        $this->assertSame('Elektronik', $kabel['jenis_bahan']);
        $this->assertEquals(15, $kabel['stok_sisa']);
        // Harga beli terakhir, bukan rata-rata.
        $this->assertEquals(2000, $kabel['harga_modal_satuan']);
        // 10x1000 + 5x2000 = 20.000
        $this->assertEquals(20000, $kabel['nilai_persediaan']);
        // 20.000 / 15 = 1.333,33 tertimbang. Rata-rata polos akan memberi 1.500.
        $this->assertEquals(1333.33, $kabel['harga_modal_rata2']);
        $this->assertSame('Pembelian', $kabel['sumber']);
    }

    public function test_tab_bahan_ikut_menghormati_hanya_tersedia(): void
    {
        $response = $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal?email=marketing@bejogja.com&tab=bahan&hanya_tersedia=1')
            ->assertStatus(200);

        // BE-MSCAM V.0 stoknya 0, jadi tersisa Kabel UTP saja.
        $response->assertJsonPath('bahan.jumlah_bahan', 1);
        $response->assertJsonPath('bahan.data.0.nama_produk', 'Kabel UTP');
    }

    public function test_tanpa_parameter_tab_mengembalikan_ketiga_tab(): void
    {
        $response = $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal?email=marketing@bejogja.com')
            ->assertStatus(200);

        $response->assertJsonStructure(['produk_jadi', 'produk_setengah_jadi', 'bahan']);
    }

    public function test_tab_bisa_dipilih_sebagian(): void
    {
        $response = $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal?email=marketing@bejogja.com&tab=produk-jadi,bahan')
            ->assertStatus(200);

        $response->assertJsonStructure(['produk_jadi', 'bahan']);
        $response->assertJsonMissingPath('produk_setengah_jadi');
    }

    public function test_nilai_tab_tidak_dikenal_ditolak(): void
    {
        $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal?email=marketing@bejogja.com&tab=produk-mentah')
            ->assertStatus(422);
    }

    public function test_tab_bahan_membawa_url_gambar_dengan_spasi_terenkode(): void
    {
        $response = $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal?email=marketing@bejogja.com&tab=bahan')
            ->assertStatus(200);

        $data = collect($response->json('bahan.data'));

        $kabel = $data->firstWhere('nama_produk', 'Kabel UTP');
        $this->assertSame('bahan/kabel utp.png', $kabel['gambar_path']);
        $this->assertSame('https://inventory.test/storage/bahan/kabel%20utp.png', $kabel['gambar_url']);

        // Bahan tanpa gambar harus null, bukan URL yang menunjuk ke berkas kosong.
        $mscam = $data->firstWhere('nama_produk', 'BE-MSCAM V.0');
        $this->assertNull($mscam['gambar_path']);
        $this->assertNull($mscam['gambar_url']);
    }

    public function test_tab_produk_membawa_foto_unit_yang_masuk_gudang(): void
    {
        $response = $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal?email=marketing@bejogja.com&tab=produk-jadi,setengah-jadi')
            ->assertStatus(200);

        // Tautan Drive diubah jadi URL thumbnail yang bisa langsung dipasang di <img>.
        $response->assertJsonPath(
            'produk_jadi.data.0.gambar_url',
            'https://drive.google.com/thumbnail?id=1AbCdEfGhIjKlMnOp&sz=w400'
        );
        $response->assertJsonPath(
            'produk_jadi.data.0.link_gambar',
            'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOp/view?usp=sharing'
        );

        // Unit tanpa foto tetap null, bukan URL yang menunjuk ke mana-mana.
        $response->assertJsonPath('produk_jadi.data.1.gambar_url', null);

        // Tautan non-Drive diteruskan apa adanya — tidak ada thumbnail yang bisa dibuat.
        $response->assertJsonPath(
            'produk_setengah_jadi.data.0.gambar_url',
            'https://foto.internal/unit/mscam-30.jpg'
        );
    }

    public function test_baris_tab_membawa_produksi_id_untuk_tombol_rincian(): void
    {
        $response = $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal?email=marketing@bejogja.com&tab=produk-jadi,setengah-jadi')
            ->assertStatus(200);

        $response->assertJsonPath('produk_jadi.data.0.produksi_id', 34);
        $response->assertJsonPath('produk_setengah_jadi.data.0.produksi_id', 134);
    }

    /**
     * Invarian terpenting: total rincian dibagi jumlah produksi harus sama dengan
     * harga modal yang tampil di tab. Kalau ini pecah, marketing akan melihat dua
     * angka berbeda untuk unit yang sama.
     */
    public function test_rincian_produk_jadi_rekonsiliasi_dengan_harga_modal_di_tab(): void
    {
        $response = $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal/rincian?email=marketing@bejogja.com&tipe=produk-jadi&produksi_id=34')
            ->assertStatus(200);

        $response->assertJsonPath('kode_produksi', 'BE - CST - 110-00024');
        $response->assertJsonPath('keterangan', 'Project Jasa Tirta Lampung');
        $response->assertJsonPath('jumlah_item', 3);

        $this->assertEquals(87711202.74, $response->json('total_biaya_bahan'));
        $this->assertEquals(7, $response->json('jml_produksi'));
        // Angka yang sama dengan produk_jadi.data.0.harga_modal_satuan di tab.
        $this->assertEquals(12530171.82, $response->json('harga_modal_satuan'));
    }

    public function test_rincian_membedakan_bahan_mentah_dan_produk_setengah_jadi(): void
    {
        $data = collect($this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal/rincian?email=marketing@bejogja.com&tipe=produk-jadi&produksi_id=34')
            ->assertStatus(200)
            ->json('data'));

        $this->assertSame(2, $data->where('jenis', 'Bahan')->count());
        $this->assertSame(1, $data->where('jenis', 'Produk Setengah Jadi')->count());

        $komponen = $data->firstWhere('jenis', 'Produk Setengah Jadi');
        $this->assertSame('BE-MSCAM V.0', $komponen['nama']);
        $this->assertSame('2607081617000030', $komponen['serial_number']);
    }

    public function test_rincian_membawa_rincian_batch_harga_dan_harga_satuan_rata_rata(): void
    {
        $data = collect($this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal/rincian?email=marketing@bejogja.com&tipe=produk-jadi&produksi_id=34')
            ->assertStatus(200)
            ->json('data'));

        $kabel = $data->firstWhere('nama', 'Kabel UTP');

        $this->assertSame('https://inventory.test/storage/bahan/kabel%20utp.png', $kabel['gambar_url']);
        $this->assertEquals(7, $kabel['qty']);
        // 27.527.283,90 / 7 — rata-rata dua batch, bukan harga salah satu batch.
        $this->assertEquals(3932469.13, $kabel['harga_satuan']);
        $this->assertCount(2, $kabel['batch']);
        $this->assertEquals(3369589.30, $kabel['batch'][0]['unit_price']);
        $this->assertEquals(4354629.00, $kabel['batch'][1]['unit_price']);
    }

    public function test_rincian_setengah_jadi_juga_rekonsiliasi(): void
    {
        $response = $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal/rincian?email=marketing@bejogja.com&tipe=setengah-jadi&produksi_id=134')
            ->assertStatus(200);

        $response->assertJsonPath('kode_produksi', 'PRD-20260413135218');
        $response->assertJsonPath('jumlah_item', 2);
        $this->assertEquals(6228794.88, $response->json('total_biaya_bahan'));
        // Sama dengan produk_setengah_jadi.data.0.harga_modal_satuan di tab.
        $this->assertEquals(389299.68, $response->json('harga_modal_satuan'));
    }

    public function test_rincian_menolak_produksi_yang_tidak_ada(): void
    {
        $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal/rincian?email=marketing@bejogja.com&tipe=produk-jadi&produksi_id=999999')
            ->assertStatus(404);
    }

    public function test_rincian_menolak_tipe_yang_tidak_dikenal(): void
    {
        $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal/rincian?email=marketing@bejogja.com&tipe=bahan&produksi_id=34')
            ->assertStatus(422);
    }

    /**
     * Rincian membuka biaya per bahan, jadi tidak boleh lebih longgar dari tab-nya.
     */
    public function test_rincian_ikut_menolak_user_tanpa_permission(): void
    {
        $this->withHeader('X-API-KEY', self::KEY)
            ->getJson('/api/crm/harga-modal/rincian?email=gudang@bejogja.com&tipe=produk-jadi&produksi_id=34')
            ->assertStatus(403);
    }

    public function test_rincian_menolak_tanpa_api_key(): void
    {
        $this->getJson('/api/crm/harga-modal/rincian?email=marketing@bejogja.com&tipe=produk-jadi&produksi_id=34')
            ->assertStatus(401);
    }

    private function buatTabelPermission(): void
    {
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
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
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
    }

    private function buatTabelProduk(): void
    {
        Schema::create('produk_jadi', function (Blueprint $table) {
            $table->id();
            $table->string('nama_produk');
            $table->string('sub_solusi')->nullable();
            $table->string('kode_bahan')->nullable();
        });

        Schema::create('produksi_produk_jadi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produksi')->nullable();
            $table->decimal('jml_produksi', 15, 2)->default(0);
            $table->string('keterangan')->nullable();
            $table->dateTime('mulai_produksi')->nullable();
            $table->string('status')->nullable();
        });

        Schema::create('produksi_produk_jadi_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produksi_produk_jadi_id');
            $table->unsignedBigInteger('bahan_id')->nullable();
            $table->unsignedBigInteger('produk_id')->nullable();
            $table->unsignedBigInteger('produk_jadis_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('used_materials', 15, 2)->nullable();
            $table->text('details')->nullable();
            $table->decimal('sub_total', 15, 2)->default(0);
        });

        Schema::create('qc_produk_jadi_list', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produksi_produk_jadi_id')->nullable();
            $table->unsignedBigInteger('produk_sample_id')->nullable();
        });

        Schema::create('produk_jadis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_qc_produk_jadi')->nullable();
            $table->dateTime('tgl_masuk')->nullable();
            $table->string('kode_transaksi')->nullable();
            $table->string('link_gambar')->nullable();
            $table->unsignedBigInteger('produksi_produk_jadi_id')->nullable();
            $table->unsignedBigInteger('produk_sample_id')->nullable();
        });

        Schema::create('produk_jadi_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_jadis_id');
            $table->unsignedBigInteger('produk_id')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('sisa', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->string('serial_number')->nullable();
            $table->string('nama_produk')->nullable();
        });

        Schema::create('bahan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bahan');
            $table->string('kode_bahan')->nullable();
            $table->string('gambar')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('jenis_bahan_id')->nullable();
        });

        Schema::create('unit', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
        });

        Schema::create('jenis_bahan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tgl_masuk')->nullable();
        });

        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('bahan_id');
            $table->integer('qty')->default(0);
            $table->integer('sisa')->default(0);
            $table->integer('unit_price')->default(0);
            $table->integer('sub_total')->default(0);
        });

        // Nama tabelnya `produksis` (lihat App\Models\Produksi), bukan `produksi`.
        // Schema test sempat memakai nama yang salah sehingga menutupi bug join.
        Schema::create('produksis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produksi')->nullable();
            $table->decimal('jml_produksi', 15, 2)->default(0);
            $table->string('keterangan')->nullable();
            $table->dateTime('mulai_produksi')->nullable();
            $table->string('status')->nullable();
        });

        Schema::create('produksi_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produksi_id');
            $table->unsignedBigInteger('bahan_id')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('jml_bahan', 15, 2)->nullable();
            $table->decimal('used_materials', 15, 2)->nullable();
            $table->text('details')->nullable();
            $table->decimal('sub_total', 15, 2)->default(0);
        });

        Schema::create('qc_produk_setengah_jadi_list', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produksi_id')->nullable();
            $table->unsignedBigInteger('produk_sample_id')->nullable();
            $table->string('kode_produksi')->nullable();
        });

        Schema::create('bahan_setengahjadis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_qc_produk_setengahjadi')->nullable();
            $table->dateTime('tgl_masuk')->nullable();
            $table->string('kode_transaksi')->nullable();
            $table->string('link_gambar')->nullable();
            $table->unsignedBigInteger('produksi_id')->nullable();
            $table->unsignedBigInteger('produk_sample_id')->nullable();
        });

        Schema::create('bahan_setengahjadi_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bahan_setengahjadi_id');
            $table->unsignedBigInteger('bahan_id')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('sisa', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->string('serial_number')->nullable();
            $table->string('nama_bahan')->nullable();
        });
    }

    private function isiData(): void
    {
        $marketing = User::create([
            'name' => 'Manager Marketing',
            'email' => 'marketing@bejogja.com',
            'password' => 'rahasia',
        ]);

        User::create([
            'name' => 'Staf Gudang',
            'email' => 'gudang@bejogja.com',
            'password' => 'rahasia',
        ]);

        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'lihat-harga-modal',
            'guard_name' => 'web',
        ]);

        $roleId = DB::table('roles')->insertGetId([
            'name' => 'marketing manager',
            'guard_name' => 'web',
        ]);

        DB::table('role_has_permissions')->insert([
            'permission_id' => $permissionId,
            'role_id' => $roleId,
        ]);

        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $marketing->id,
        ]);

        // --- Produk jadi -------------------------------------------------
        DB::table('produk_jadi')->insert([
            'id' => 12,
            'nama_produk' => 'BE - CST - 110',
            'sub_solusi' => 'Automatic Water Level Recorder',
            'kode_bahan' => 'BE - CST - 110',
        ]);

        DB::table('produksi_produk_jadi')->insert([
            'id' => 34,
            'kode_produksi' => 'BE - CST - 110-00024',
            'jml_produksi' => 7,
            'keterangan' => 'Project Jasa Tirta Lampung',
            'mulai_produksi' => '2026-06-23 12:00:00',
            'status' => 'Selesai',
        ]);

        DB::table('qc_produk_jadi_list')->insert([
            ['id' => 55, 'produksi_produk_jadi_id' => 34, 'produk_sample_id' => null],
            ['id' => 56, 'produksi_produk_jadi_id' => 34, 'produk_sample_id' => 7],
        ]);

        // Unit 60 punya FK langsung; unit 61 sengaja dibiarkan kosong seperti
        // mayoritas data production, jadi kode produksi dan asal sample-nya
        // hanya bisa ditemukan lewat daftar QC.
        DB::table('produk_jadis')->insert([
            [
                'id' => 60,
                'id_qc_produk_jadi' => 55,
                'tgl_masuk' => '2026-07-15 16:28:19',
                'kode_transaksi' => 'BE - CST - 110-00024-1/7',
                'link_gambar' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOp/view?usp=sharing',
                'produksi_produk_jadi_id' => 34,
                'produk_sample_id' => null,
            ],
            [
                'id' => 61,
                'id_qc_produk_jadi' => 56,
                'tgl_masuk' => '2026-06-01 10:00:00',
                'kode_transaksi' => 'BE - CST - 110-00023-1/1',
                'link_gambar' => null,
                'produksi_produk_jadi_id' => null,
                'produk_sample_id' => null,
            ],
        ]);

        DB::table('produk_jadi_details')->insert([
            [
                'produk_jadis_id' => 60,
                'produk_id' => 12,
                'qty' => 1,
                'sisa' => 0,
                'unit_price' => 12530171.82,
                'sub_total' => 12530171.82,
                'serial_number' => '260715070010385008',
                'nama_produk' => 'BE - CST - 110',
            ],
            [
                'produk_jadis_id' => 61,
                'produk_id' => 12,
                'qty' => 1,
                'sisa' => 1,
                'unit_price' => 9000000,
                'sub_total' => 9000000,
                'serial_number' => '260601070010000001',
                'nama_produk' => 'BE - CST - 110',
            ],
        ]);

        // --- Produk setengah jadi ---------------------------------------
        DB::table('unit')->insert(['id' => 1, 'nama' => 'Pcs']);
        DB::table('jenis_bahan')->insert(['id' => 1, 'nama' => 'Elektronik']);

        DB::table('bahan')->insert([
            [
                'id' => 2249,
                'nama_bahan' => 'BE-MSCAM V.0',
                'kode_bahan' => 'BE-MSCAM',
                'gambar' => null,
                'unit_id' => 1,
                'jenis_bahan_id' => 1,
            ],
            [
                'id' => 900,
                'nama_bahan' => 'Kabel UTP',
                'kode_bahan' => 'KBL-UTP',
                // Spasi di nama file itu wajar di data asli, jadi harus ikut teruji.
                'gambar' => 'bahan/kabel utp.png',
                'unit_id' => 1,
                'jenis_bahan_id' => 1,
            ],
            // Belum pernah dibeli sama sekali, jadi tidak boleh muncul di tab bahan.
            [
                'id' => 901,
                'nama_bahan' => 'Bahan Tanpa Pembelian',
                'kode_bahan' => 'TANPA-BELI',
                'gambar' => null,
                'unit_id' => 1,
                'jenis_bahan_id' => 1,
            ],
        ]);

        DB::table('purchases')->insert([
            ['id' => 1, 'tgl_masuk' => '2026-01-10 09:00:00'],
            ['id' => 2, 'tgl_masuk' => '2026-05-20 09:00:00'],
        ]);

        // Dua batch harga berbeda untuk Kabel UTP: batch lama masih sisa 10 @1.000,
        // batch terbaru sisa 5 @2.000. Rata-rata tertimbangnya harus 1.333,33 —
        // bukan 1.500 (rata-rata polos) dan bukan 2.000 (harga terakhir).
        DB::table('purchase_details')->insert([
            [
                'purchase_id' => 1, 'bahan_id' => 900,
                'qty' => 20, 'sisa' => 10, 'unit_price' => 1000, 'sub_total' => 20000,
            ],
            [
                'purchase_id' => 2, 'bahan_id' => 900,
                'qty' => 5, 'sisa' => 5, 'unit_price' => 2000, 'sub_total' => 10000,
            ],
            // Bahan yang stoknya sudah habis — dipakai untuk uji hanya_tersedia.
            [
                'purchase_id' => 1, 'bahan_id' => 2249,
                'qty' => 3, 'sisa' => 0, 'unit_price' => 500000, 'sub_total' => 1500000,
            ],
        ]);

        DB::table('produksis')->insert([
            'id' => 134,
            'kode_produksi' => 'PRD-20260413135218',
            'jml_produksi' => 16,
            'keterangan' => 'Batch MSCAM',
            'mulai_produksi' => '2026-04-13 14:00:00',
            'status' => 'Selesai',
        ]);

        // Total dibuat pas: 6.000.000 + 228.794,88 = 6.228.794,88, dibagi 16 unit
        // menghasilkan 389.299,68 — sama dengan unit_price di tab setengah jadi.
        DB::table('produksi_details')->insert([
            [
                'produksi_id' => 134, 'bahan_id' => 900,
                'qty' => 48, 'jml_bahan' => 48, 'sub_total' => 6000000,
                'details' => json_encode([['qty' => '48.00', 'unit_price' => '125000.00']]),
            ],
            [
                'produksi_id' => 134, 'bahan_id' => 2249,
                'qty' => 16, 'jml_bahan' => 16, 'sub_total' => 228794.88,
                'details' => json_encode([['qty' => '16.00', 'unit_price' => '14299.68']]),
            ],
        ]);

        // Total 87.711.202,74 dibagi 7 unit = 12.530.171,82 — sama dengan
        // unit_price produk jadi di tab. Invarian ini yang membuat marketing bisa
        // menelusuri harga modal sampai ke bahan tanpa menemukan selisih.
        DB::table('produksi_produk_jadi_details')->insert([
            [
                'produksi_produk_jadi_id' => 34, 'bahan_id' => 900, 'produk_id' => null,
                'qty' => 7, 'sub_total' => 27527283.90,
                // Satu bahan dari dua batch harga berbeda.
                'details' => json_encode([
                    ['qty' => '3.00', 'unit_price' => '3369589.30'],
                    ['qty' => '4.00', 'unit_price' => '4354629.00'],
                ]),
            ],
            [
                'produksi_produk_jadi_id' => 34, 'bahan_id' => 2249, 'produk_id' => null,
                'qty' => 2, 'sub_total' => 50000000,
                'details' => json_encode([['qty' => '2.00', 'unit_price' => '25000000.00']]),
            ],
            // Komponen berupa produk setengah jadi, bukan bahan mentah.
            [
                'produksi_produk_jadi_id' => 34, 'bahan_id' => null, 'produk_id' => 500,
                'qty' => 1, 'sub_total' => 10183918.84,
                'details' => null,
            ],
        ]);

        DB::table('qc_produk_setengah_jadi_list')->insert([
            'id' => 183,
            'produksi_id' => 134,
            'produk_sample_id' => null,
            'kode_produksi' => null,
        ]);

        // produksi_id sengaja null — di production hanya 20 dari 271 baris yang
        // terisi, jadi kode produksinya harus datang dari daftar QC.
        DB::table('bahan_setengahjadis')->insert([
            'id' => 311,
            'id_qc_produk_setengahjadi' => 183,
            'tgl_masuk' => '2026-07-15 16:52:16',
            'kode_transaksi' => 'PRD-20260413135218-0002-16',
            // Bukan tautan Drive, jadi harus diteruskan apa adanya tanpa thumbnail.
            'link_gambar' => 'https://foto.internal/unit/mscam-30.jpg',
            'produksi_id' => null,
            'produk_sample_id' => null,
        ]);

        DB::table('bahan_setengahjadi_details')->insert([
            'id' => 500,
            'bahan_setengahjadi_id' => 311,
            'bahan_id' => 2249,
            'qty' => 1,
            'sisa' => 1,
            'unit_price' => 389299.68,
            'sub_total' => 389299.68,
            'serial_number' => '2607081617000030',
            'nama_bahan' => 'BE-MSCAM V.0',
        ]);
    }
}
