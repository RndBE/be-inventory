<?php

namespace Tests\Feature;

use App\Http\Controllers\ProduksiController;
use App\Livewire\Quality\QcProdukSetengahJadiWizard;
use App\Models\Produksi;
use App\Models\QcProdukSetengahJadiList;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProduksiSetengahJadiPartialReleaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::create('produksis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produksi')->nullable();
            $table->unsignedBigInteger('bahan_id')->nullable();
            $table->string('pengaju')->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('jml_produksi')->default(0);
            $table->dateTime('mulai_produksi')->nullable();
            $table->dateTime('selesai_produksi')->nullable();
            $table->string('jenis_produksi')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('status')->nullable();
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

    private function buatProduksi(array $override = []): Produksi
    {
        return Produksi::create(array_merge([
            'kode_produksi' => 'PRD-20260619092608',
            'bahan_id' => 1,
            'pengaju' => 'Teknisi',
            'keterangan' => 'Batch uji',
            'jml_produksi' => 16,
            'mulai_produksi' => now(),
            'jenis_produksi' => 'Produk Setengah Jadi',
            'status' => 'Dalam proses',
        ], $override));
    }

    /**
     * Bangun item selectedProdukList seperti yang dihasilkan wizard pada step 2.
     */
    private function itemUnit(Produksi $produksi, int $i): array
    {
        return [
            'bahan_id' => $produksi->bahan_id,
            'nama_bahan' => 'Sensor',
            'nomor' => $i . '/' . $produksi->jml_produksi,
            'kode_list' => $produksi->kode_produksi . '-' . $i . '/' . $produksi->jml_produksi,
            'mulai_produksi' => $produksi->mulai_produksi,
            'qty' => 1,
            'unit_price' => 10000,
            'sub_total' => 10000,
            'is_selected' => true,
            'is_disabled' => false,
            'id_bluetooth' => '000',
            'kode_jenis_unit' => null,
            'kode_wiring_unit' => null,
        ];
    }

    private function keluarkanUnit(Produksi $produksi, int $dari, int $sampai): void
    {
        $wizard = new QcProdukSetengahJadiWizard();
        $wizard->selected_produksi_id = $produksi->id;
        $wizard->selected_jenis_sn = 'Vendor';
        $wizard->selectedProdukList = collect(range($dari, $sampai))
            ->map(fn ($i) => $this->itemUnit($produksi, $i))
            ->all();

        $wizard->simpanQcProduk();
    }

    public function test_keluarkan_sebagian_membuka_produksi_berjalan_ke_qc(): void
    {
        $produksi = $this->buatProduksi();

        (new ProduksiController())->keluarkanSebagian($produksi->id);

        $this->assertSame('Sebagian', $produksi->fresh()->status);
    }

    public function test_unit_bisa_dikeluarkan_bertahap_dan_status_auto_selesai(): void
    {
        $produksi = $this->buatProduksi(['status' => 'Sebagian']);

        // Tahap 1: keluarkan 3 dari 16 unit.
        $this->keluarkanUnit($produksi, 1, 3);

        $this->assertSame(3, QcProdukSetengahJadiList::where('produksi_id', $produksi->id)->count());
        $this->assertSame('Sebagian', $produksi->fresh()->status, 'Belum semua unit dikeluarkan, status harus tetap Sebagian.');

        // Tahap 2: keluarkan sisa 13 unit.
        $this->keluarkanUnit($produksi, 4, 16);

        $this->assertSame(16, QcProdukSetengahJadiList::where('produksi_id', $produksi->id)->count());
        $this->assertSame('Selesai', $produksi->fresh()->status, 'Semua unit sudah dikeluarkan, status harus otomatis Selesai.');
        $this->assertNotNull($produksi->fresh()->selesai_produksi);
    }
}
