<?php

namespace Tests\Feature;

use App\Livewire\Quality\QcProdukSetengahJadiTable;
use App\Models\BahanSetengahjadi;
use App\Models\BahanSetengahjadiDetails;
use App\Models\ProdukSample;
use App\Models\QcProdukSetengahJadiList;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QcProdukSetengahJadiSampleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::create('produk_sample', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produk_sample')->unique();
            $table->dateTime('mulai_produk_sample');
            $table->dateTime('selesai_produk_sample')->nullable();
            $table->string('pengaju')->nullable();
            $table->string('keterangan')->nullable();
            $table->string('nama_produk_sample');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('qc_produk_setengah_jadi_list', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produksi_id')->nullable();
            $table->unsignedBigInteger('produk_sample_id')->nullable();
            $table->unsignedBigInteger('bahan_id')->nullable();
            $table->string('kode_list')->nullable();
            $table->string('kode_produksi')->nullable();
            $table->dateTime('mulai_produksi')->nullable();
            $table->dateTime('selesai_produksi')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('jenis_sn')->nullable();
            $table->string('id_bluetooth')->nullable();
            $table->string('kode_jenis_unit')->nullable();
            $table->string('kode_wiring_unit')->nullable();
            $table->dateTime('tanggal_masuk_gudang')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('bahan_setengahjadis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produksi_id')->nullable();
            $table->unsignedBigInteger('produk_sample_id')->nullable();
            $table->unsignedBigInteger('id_qc_produk_setengahjadi')->nullable();
            $table->dateTime('tgl_masuk');
            $table->string('kode_transaksi')->unique();
            $table->timestamps();
        });

        Schema::create('bahan_setengahjadi_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bahan_setengahjadi_id');
            $table->unsignedBigInteger('bahan_id')->nullable();
            $table->string('nama_bahan')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('sisa', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->string('serial_number')->nullable();
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

    public function test_produk_sample_qc_can_be_processed_to_half_finished_stock_with_qc_serial_number(): void
    {
        $sample = ProdukSample::create([
            'kode_produk_sample' => 'PRS - 00001',
            'nama_produk_sample' => 'Sample Sensor Air',
            'mulai_produk_sample' => now(),
            'selesai_produk_sample' => now(),
            'keterangan' => 'Sample untuk validasi QC',
            'status' => 'Selesai',
        ]);

        $qc = QcProdukSetengahJadiList::create([
            'produk_sample_id' => $sample->id,
            'kode_list' => 'PRS - 00001-QC',
            'mulai_produksi' => $sample->mulai_produk_sample,
            'selesai_produksi' => $sample->selesai_produk_sample,
            'jenis_sn' => 'Vendor',
            'qty' => 1,
            'unit_price' => 125000,
            'sub_total' => 125000,
        ]);

        (new QcProdukSetengahJadiTable())->prosesKeGudang($qc->id, 'SAMPLE-SN-001');

        $this->assertDatabaseHas('qc_produk_setengah_jadi_list', [
            'id' => $qc->id,
            'serial_number' => 'SAMPLE-SN-001',
        ]);

        $this->assertDatabaseHas('bahan_setengahjadis', [
            'kode_transaksi' => 'PRS - 00001-QC',
            'produk_sample_id' => $sample->id,
            'id_qc_produk_setengahjadi' => $qc->id,
        ]);

        $this->assertDatabaseHas('bahan_setengahjadi_details', [
            'nama_bahan' => 'Sample Sensor Air',
            'serial_number' => 'SAMPLE-SN-001',
            'qty' => 1,
            'sisa' => 1,
            'unit_price' => 125000,
            'sub_total' => 125000,
        ]);

        $stock = BahanSetengahjadi::with('bahanSetengahjadiDetails')->first();
        $this->assertCount(1, $stock->bahanSetengahjadiDetails);
        $this->assertSame('SAMPLE-SN-001', BahanSetengahjadiDetails::first()->serial_number);
    }
}
