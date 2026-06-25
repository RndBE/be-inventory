<?php

namespace Tests\Feature;

use App\Http\Controllers\ProdukSampleController;
use App\Livewire\Quality\QcProdukJadiTable;
use App\Livewire\Quality\QcProdukJadiWizard;
use App\Models\BahanKeluar;
use App\Models\BahanKeluarDetails;
use App\Models\ProduksiProdukJadiDetails;
use App\Models\ProduksiProdukJadi;
use App\Models\ProdukJadi;
use App\Models\ProdukSample;
use App\Models\ProdukSampleDetails;
use App\Models\Qc1ProdukJadi;
use App\Models\QcProdukJadiList;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ProdukSampleQcProdukJadiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::create('produk_jadi', function (Blueprint $table) {
            $table->id();
            $table->string('nama_produk');
            $table->string('kode_bahan')->nullable();
            $table->string('sub_solusi')->nullable();
            $table->timestamps();
        });

        Schema::create('produk_sample', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produk_sample')->unique();
            $table->foreignId('produk_jadi_id')->nullable();
            $table->dateTime('mulai_produk_sample');
            $table->dateTime('selesai_produk_sample')->nullable();
            $table->string('pengaju')->nullable();
            $table->string('keterangan')->nullable();
            $table->string('nama_produk_sample');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('qc_produk_jadi_list', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produksi_produk_jadi_id')->nullable();
            $table->unsignedBigInteger('produk_sample_id')->nullable();
            $table->unsignedBigInteger('produk_jadi_id')->nullable();
            $table->string('kode_list')->nullable();
            $table->dateTime('mulai_produksi')->nullable();
            $table->dateTime('selesai_produksi')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('petugas_produksi')->nullable();
            $table->string('id_logger')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->dateTime('tanggal_masuk_gudang')->nullable();
            $table->timestamps();
        });

        Schema::create('qc_1_produk_jadi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_qc')->nullable();
            $table->dateTime('tgl_qc')->nullable();
            $table->string('petugas_qc')->nullable();
            $table->unsignedBigInteger('id_produk_jadi_list');
            $table->enum('grade', ['A', 'B'])->nullable();
            $table->string('laporan_qc')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('qc_2_produk_jadi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_qc')->nullable();
            $table->dateTime('tgl_qc')->nullable();
            $table->string('petugas_qc')->nullable();
            $table->unsignedBigInteger('id_produk_jadi_list');
            $table->enum('grade', ['A', 'B'])->nullable();
            $table->string('laporan_qc')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('qc_dokumentasi_produk_jadi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('qc1_id')->nullable();
            $table->unsignedBigInteger('qc2_id')->nullable();
            $table->string('file_path');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
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

        Schema::create('produksi_produk_jadi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produksi')->unique();
            $table->unsignedBigInteger('produk_jadi_id');
            $table->unsignedBigInteger('produk_sample_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->dateTime('mulai_produksi');
            $table->dateTime('selesai_produksi')->nullable();
            $table->string('pengaju')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('jenis_produksi')->nullable();
            $table->integer('jml_produksi')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('produk_sample_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_sample_id');
            $table->unsignedBigInteger('bahan_id')->nullable();
            $table->unsignedBigInteger('produk_id')->nullable();
            $table->unsignedBigInteger('produk_jadis_id')->nullable();
            $table->decimal('qty', 15, 2)->nullable();
            $table->decimal('jml_bahan', 15, 2)->nullable();
            $table->decimal('used_materials', 15, 2)->nullable();
            $table->text('details')->nullable();
            $table->decimal('sub_total', 15, 2)->nullable();
            $table->string('serial_number')->nullable();
            $table->timestamps();
        });

        Schema::create('produksi_produk_jadi_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produksi_produk_jadi_id');
            $table->unsignedBigInteger('bahan_id')->nullable();
            $table->unsignedBigInteger('produk_id')->nullable();
            $table->unsignedBigInteger('produk_jadis_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('qty', 15, 2)->default(0)->nullable();
            $table->decimal('used_materials', 15, 2)->default(0)->nullable();
            $table->text('details')->nullable();
            $table->decimal('sub_total', 15, 2)->default(0)->nullable();
            $table->timestamps();
        });

        Schema::create('produk_jadis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_qc_produk_jadi')->nullable();
            $table->unsignedBigInteger('produksi_produk_jadi_id')->nullable();
            $table->unsignedBigInteger('produk_sample_id')->nullable();
            $table->dateTime('tgl_masuk');
            $table->string('kode_transaksi')->unique();
            $table->timestamps();
        });

        Schema::create('produk_jadi_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_jadis_id');
            $table->unsignedBigInteger('produk_id');
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('sisa', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->string('serial_number')->nullable();
            $table->string('nama_produk')->nullable();
            $table->timestamps();
        });

        Schema::create('bahan_keluars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_sample_id')->nullable();
            $table->string('kode_transaksi')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('bahan_keluar_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bahan_keluar_id');
            $table->unsignedBigInteger('bahan_id')->nullable();
            $table->unsignedBigInteger('produk_id')->nullable();
            $table->unsignedBigInteger('produk_jadis_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('qty', 15, 2)->default(0)->nullable();
            $table->decimal('jml_bahan', 15, 2)->default(0)->nullable();
            $table->decimal('used_materials', 15, 2)->default(0)->nullable();
            $table->text('details')->nullable();
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('log_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('method')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('url')->nullable();
            $table->string('platform')->nullable();
            $table->string('browser')->nullable();
            $table->string('status')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function test_bahan_keluar_detail_keeps_produk_jadi_detail_id_for_sample_origin_stock(): void
    {
        $bahanKeluar = BahanKeluar::create([
            'kode_transaksi' => 'KBK - 00010 PJPro',
            'status' => 'Belum disetujui',
        ]);

        $detail = BahanKeluarDetails::create([
            'bahan_keluar_id' => $bahanKeluar->id,
            'bahan_id' => null,
            'produk_id' => null,
            'produk_jadis_id' => 42,
            'serial_number' => 'SAMPLE-JADI-001',
            'qty' => 1,
            'jml_bahan' => 1,
            'used_materials' => 0,
            'details' => json_encode([
                ['qty' => 1, 'unit_price' => 175000, 'serial_number' => 'SAMPLE-JADI-001'],
            ]),
            'sub_total' => 175000,
        ]);

        $this->assertSame(42, $detail->refresh()->produk_jadis_id);
    }

    public function test_finished_produk_sample_creates_produk_jadi_and_is_sent_to_produksi_produk_jadi(): void
    {
        $sample = ProdukSample::create([
            'kode_produk_sample' => 'PRS - 00001',
            'produk_jadi_id' => null,
            'nama_produk_sample' => 'Sample Logger Air',
            'mulai_produk_sample' => now(),
            'selesai_produk_sample' => now(),
            'keterangan' => 'Sample final',
            'status' => 'Selesai',
        ]);

        $bahanKeluar = BahanKeluar::create([
            'produk_sample_id' => $sample->id,
            'kode_transaksi' => 'KBK - 00001 PRS',
            'status' => 'Disetujui',
        ]);

        BahanKeluarDetails::create([
            'bahan_keluar_id' => $bahanKeluar->id,
            'sub_total' => 150000,
        ]);

        ProdukSampleDetails::create([
            'produk_sample_id' => $sample->id,
            'bahan_id' => 834,
            'produk_id' => null,
            'produk_jadis_id' => null,
            'qty' => 2,
            'jml_bahan' => 0,
            'used_materials' => 2,
            'details' => json_encode([
                ['qty' => 2, 'unit_price' => 75000],
            ]),
            'sub_total' => 150000,
            'serial_number' => null,
        ]);

        ProdukSampleDetails::create([
            'produk_sample_id' => $sample->id,
            'bahan_id' => null,
            'produk_id' => 254,
            'produk_jadis_id' => 99,
            'qty' => 1,
            'jml_bahan' => 0,
            'used_materials' => 1,
            'details' => json_encode([
                ['qty' => 1, 'unit_price' => 125000, 'serial_number' => 'SN-SAMPLE-001'],
            ]),
            'sub_total' => 125000,
            'serial_number' => 'SN-SAMPLE-001',
        ]);

        (new ProdukSampleController())->masukkanKeStok(new Request([
            'nama_produk' => 'Logger Air Jadi',
        ]), $sample->id);

        $produkJadi = ProdukJadi::where('nama_produk', 'Logger Air Jadi')->first();

        $this->assertNotNull($produkJadi);
        $this->assertSame('Produk Sample', $produkJadi->sub_solusi);
        $this->assertNull($produkJadi->kode_bahan);

        $this->assertDatabaseHas('produk_sample', [
            'id' => $sample->id,
            'produk_jadi_id' => $produkJadi->id,
        ]);

        $this->assertDatabaseHas('produksi_produk_jadi', [
            'produk_sample_id' => $sample->id,
            'produk_jadi_id' => $produkJadi->id,
            'kode_produksi' => 'PRS - 00001-00001',
            'jml_produksi' => 1,
            'keterangan' => 'Dari Produk Sample PRS - 00001 - Sample final',
            'status' => 'Dalam Proses',
        ]);

        $produksiProdukJadi = ProduksiProdukJadi::first();

        $this->assertSame($sample->mulai_produk_sample->format('Y-m-d H:i:s'), $produksiProdukJadi->mulai_produksi);
        $this->assertSame(0, QcProdukJadiList::where('produk_sample_id', $sample->id)->count());

        $this->assertDatabaseHas('produksi_produk_jadi_details', [
            'produksi_produk_jadi_id' => $produksiProdukJadi->id,
            'bahan_id' => 834,
            'produk_id' => null,
            'produk_jadis_id' => null,
            'qty' => 2,
            'used_materials' => 2,
            'sub_total' => 150000,
            'serial_number' => null,
        ]);

        $this->assertDatabaseHas('produksi_produk_jadi_details', [
            'produksi_produk_jadi_id' => $produksiProdukJadi->id,
            'bahan_id' => null,
            'produk_id' => 254,
            'produk_jadis_id' => 99,
            'qty' => 1,
            'used_materials' => 1,
            'sub_total' => 125000,
            'serial_number' => 'SN-SAMPLE-001',
        ]);
    }

    public function test_missing_produksi_produk_jadi_details_can_be_copied_from_sample(): void
    {
        $produkJadi = ProdukJadi::create([
            'nama_produk' => 'Logger Air Jadi',
            'kode_bahan' => 'LA',
            'sub_solusi' => 'Logger',
        ]);

        $sample = ProdukSample::create([
            'kode_produk_sample' => 'PRS - 00004',
            'produk_jadi_id' => $produkJadi->id,
            'nama_produk_sample' => 'Sample Logger Air Final',
            'mulai_produk_sample' => now(),
            'selesai_produk_sample' => now(),
            'keterangan' => 'Sample final',
            'status' => 'Selesai',
        ]);

        ProdukSampleDetails::create([
            'produk_sample_id' => $sample->id,
            'bahan_id' => 834,
            'produk_id' => null,
            'qty' => 3,
            'jml_bahan' => 0,
            'used_materials' => 3,
            'details' => json_encode([
                ['qty' => 3, 'unit_price' => 1000],
            ]),
            'sub_total' => 3000,
        ]);

        $produksi = ProduksiProdukJadi::create([
            'produk_sample_id' => $sample->id,
            'produk_jadi_id' => $produkJadi->id,
            'kode_produksi' => 'LA-00004',
            'mulai_produksi' => now(),
            'jml_produksi' => 1,
            'status' => 'Dalam Proses',
        ]);

        app(\App\Services\ProdukSampleProductionDetailCopier::class)->copyMissingDetailsFromSample($produksi);

        $this->assertSame(1, ProduksiProdukJadiDetails::where('produksi_produk_jadi_id', $produksi->id)->count());
        $this->assertDatabaseHas('produksi_produk_jadi_details', [
            'produksi_produk_jadi_id' => $produksi->id,
            'bahan_id' => 834,
            'qty' => 3,
            'used_materials' => 3,
            'sub_total' => 3000,
        ]);
    }

    public function test_sample_origin_qc_produk_jadi_can_be_processed_to_finished_stock(): void
    {
        $produkJadi = ProdukJadi::create([
            'nama_produk' => 'Logger Air Jadi',
            'kode_bahan' => 'LA',
            'sub_solusi' => 'Logger',
        ]);

        $sample = ProdukSample::create([
            'kode_produk_sample' => 'PRS - 00002',
            'produk_jadi_id' => $produkJadi->id,
            'nama_produk_sample' => 'Sample Logger Air Final',
            'mulai_produk_sample' => now(),
            'selesai_produk_sample' => now(),
            'keterangan' => 'Sample final',
            'status' => 'Selesai',
        ]);

        $qc = QcProdukJadiList::create([
            'produk_sample_id' => $sample->id,
            'produk_jadi_id' => $produkJadi->id,
            'kode_list' => 'PRS - 00002-QC',
            'mulai_produksi' => $sample->mulai_produk_sample,
            'selesai_produksi' => $sample->selesai_produk_sample,
            'qty' => 1,
            'unit_price' => 175000,
            'sub_total' => 175000,
        ]);

        (new QcProdukJadiTable())->prosesKeGudang($qc->id, 'SAMPLE-JADI-001');

        $this->assertDatabaseHas('produk_jadis', [
            'kode_transaksi' => 'PRS - 00002-QC',
            'produk_sample_id' => $sample->id,
            'id_qc_produk_jadi' => $qc->id,
        ]);

        $this->assertDatabaseHas('produk_jadi_details', [
            'produk_id' => $produkJadi->id,
            'nama_produk' => 'Logger Air Jadi',
            'serial_number' => 'SAMPLE-JADI-001',
            'qty' => 1,
            'sisa' => 1,
            'unit_price' => 175000,
            'sub_total' => 175000,
        ]);
    }

    public function test_qc_wizard_keeps_sample_origin_from_produksi_produk_jadi(): void
    {
        $produkJadi = ProdukJadi::create([
            'nama_produk' => 'Logger Air Jadi',
            'kode_bahan' => 'LA',
            'sub_solusi' => 'Logger',
        ]);

        $sample = ProdukSample::create([
            'kode_produk_sample' => 'PRS - 00003',
            'produk_jadi_id' => $produkJadi->id,
            'nama_produk_sample' => 'Sample Logger Air Final',
            'mulai_produk_sample' => now(),
            'selesai_produk_sample' => now(),
            'keterangan' => 'Sample final',
            'status' => 'Selesai',
        ]);

        $produksi = ProduksiProdukJadi::create([
            'produk_sample_id' => $sample->id,
            'produk_jadi_id' => $produkJadi->id,
            'kode_produksi' => 'LA-00001',
            'mulai_produksi' => now(),
            'selesai_produksi' => now(),
            'jml_produksi' => 1,
            'status' => 'Selesai',
        ]);

        $wizard = new QcProdukJadiWizard();
        $wizard->selected_produksi_produk_jadi_id = $produksi->id;
        $wizard->selectedProdukJadiList = [[
            'produk_jadi_id' => $produkJadi->id,
            'nama_produk' => 'Logger Air Jadi',
            'nomor' => '1/1',
            'kode_produksi' => 'LA-00001',
            'mulai_produksi' => $produksi->mulai_produksi,
            'qty' => 1,
            'unit_price' => 125000,
            'sub_total' => 125000,
            'is_selected' => true,
            'id_logger' => '123',
        ]];

        $wizard->simpanQcProduk();

        $this->assertDatabaseHas('qc_produk_jadi_list', [
            'produksi_produk_jadi_id' => $produksi->id,
            'produk_sample_id' => $sample->id,
            'produk_jadi_id' => $produkJadi->id,
            'kode_list' => 'LA-00001-1/1',
        ]);
    }

    public function test_qc_produk_jadi_can_be_saved_without_laporan_qc(): void
    {
        $user = User::create([
            'name' => 'Petugas QC',
            'email' => 'qc@example.test',
            'password' => 'password',
        ]);

        $qcList = QcProdukJadiList::create([
            'kode_list' => 'PRDJD-QC-001',
            'produk_jadi_id' => 1,
            'qty' => 1,
            'unit_price' => 100000,
            'sub_total' => 100000,
        ]);

        $this->actingAs($user);
        Gate::before(fn () => false);

        Livewire::test(QcProdukJadiTable::class)
            ->set('grade', 'A')
            ->set('catatan', 'Laporan menyusul')
            ->call('simpanQc', $qcList->id, 1)
            ->assertHasNoErrors(['laporan_qc']);

        $qc = Qc1ProdukJadi::first();

        $this->assertNotNull($qc);
        $this->assertSame($qcList->id, $qc->id_produk_jadi_list);
        $this->assertSame('A', $qc->grade);
        $this->assertNull($qc->laporan_qc);
    }
}
