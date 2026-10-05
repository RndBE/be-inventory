<?php

namespace Tests\Unit;

use App\Services\PerbaikanDataService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Label pilihan untuk baris detail yang isinya unit produk, bukan bahan.
 *
 * Baris seperti itu tidak punya `bahan_id`, jadi `label_relasi` kosong dan
 * semua unit dalam satu transaksi tampil sebagai "PRJ - 00050 #id · Bahan".
 * Pengaju hanya melihat deretan id dan tidak tahu unit mana yang dimaksud.
 * Kode unitnya ada di baris lot yang ditunjuk `produk_jadis_id` / `produk_id`.
 */
class PerbaikanDataLabelUnitTest extends TestCase
{
    private PerbaikanDataService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PerbaikanDataService();
        $this->siapkanModulUji();
    }

    protected function tearDown(): void
    {
        foreach (['uji_unit_baris', 'uji_unit_induk', 'uji_unit_lot', 'uji_unit_lot_induk', 'uji_unit_sj', 'uji_unit_sj_induk', 'uji_unit_bahan'] as $tabel) {
            Schema::dropIfExists($tabel);
        }

        parent::tearDown();
    }

    /**
     * Dua unit dari produk yang sama dibedakan oleh kodenya.
     */
    #[Test]
    public function unit_produk_jadi_dilabeli_kode_unitnya(): void
    {
        $induk = ModelUjiUnitInduk::create(['kode_transaksi' => 'PRJ - 00050']);

        $this->barisUnitJadi($induk, 'BE - CST - 11-00030-1/2');
        $this->barisUnitJadi($induk, 'BE - CST - 11-00030-2/2');

        $label = array_column($this->service->opsiRecordJenis(['Uji Unit'], 'PRJ - 00050')['opsi'], 'label');

        $this->assertContains('PRJ - 00050 — BE - CST - 11-00030-1/2 · Bahan', $label);
        $this->assertContains('PRJ - 00050 — BE - CST - 11-00030-2/2 · Bahan', $label);
    }

    /**
     * Unit setengah jadi lewat jalur yang berbeda, dan jalurnya bertingkat dua.
     */
    #[Test]
    public function unit_setengah_jadi_dilabeli_kode_produksinya(): void
    {
        $induk = ModelUjiUnitInduk::create(['kode_transaksi' => 'PRJ - 00051']);
        $sjInduk = ModelUjiUnitSjInduk::create(['kode_transaksi' => 'PRD-20260801090332-2']);
        $sj = ModelUjiUnitSj::create(['uji_unit_sj_induk_id' => $sjInduk->id]);

        ModelUjiUnitBaris::create(['uji_unit_induk_id' => $induk->id, 'produk_id' => $sj->id, 'serial_number' => '2609030108000001']);

        $label = array_column($this->service->opsiRecordJenis(['Uji Unit'], 'PRJ - 00051')['opsi'], 'label');

        $this->assertContains('PRJ - 00051 — PRD-20260801090332-2 · Bahan', $label);
    }

    /**
     * Baris bahan biasa tidak berubah: nama bahan tetap didahulukan.
     */
    #[Test]
    public function nama_bahan_tetap_didahulukan_dari_kode_unit(): void
    {
        $induk = ModelUjiUnitInduk::create(['kode_transaksi' => 'PRJ - 00050']);
        $bahan = ModelUjiUnitBahan::create(['nama_bahan' => 'Buzzer 5V']);

        ModelUjiUnitBaris::create(['uji_unit_induk_id' => $induk->id, 'uji_unit_bahan_id' => $bahan->id]);

        $label = array_column($this->service->opsiRecordJenis(['Uji Unit'], 'PRJ - 00050')['opsi'], 'label');

        $this->assertContains('PRJ - 00050 — Buzzer 5V · Bahan', $label);
    }

    /**
     * Lot yang tidak ketemu: serial number, lalu id baris sebagai pilihan akhir.
     */
    #[Test]
    public function tanpa_lot_dipakai_serial_number_lalu_id_baris(): void
    {
        $induk = ModelUjiUnitInduk::create(['kode_transaksi' => 'PRJ - 00050']);

        ModelUjiUnitBaris::create(['uji_unit_induk_id' => $induk->id, 'serial_number' => '260917020010398007']);
        $kosong = ModelUjiUnitBaris::create(['uji_unit_induk_id' => $induk->id]);

        $label = array_column($this->service->opsiRecordJenis(['Uji Unit'], 'PRJ - 00050')['opsi'], 'label');

        $this->assertContains('PRJ - 00050 — 260917020010398007 · Bahan', $label);
        $this->assertContains('PRJ - 00050 #' . $kosong->id . ' · Bahan', $label);
    }

    /**
     * Yang tercetak di dokumen adalah kode unitnya, jadi itu yang bisa diketik.
     */
    #[Test]
    public function baris_unit_bisa_dicari_lewat_kode_unit_dan_serial_number(): void
    {
        $induk = ModelUjiUnitInduk::create(['kode_transaksi' => 'PRJ - 00050']);
        $lain = ModelUjiUnitInduk::create(['kode_transaksi' => 'PRJ - 00099']);

        $this->barisUnitJadi($induk, 'BE - CST - 11-00030-1/2', '260917020010398007');
        $this->barisUnitJadi($induk, 'BE - CST - 110-00032-1/1', '260917010010397015');
        $this->barisUnitJadi($lain, 'BE - CST - 11-00030-2/2', '260917020010399008');

        $lewatKode = array_column($this->service->opsiRecordJenis(['Uji Unit'], '110-00032')['opsi'], 'label');
        $lewatSerial = array_column($this->service->opsiRecordJenis(['Uji Unit'], '260917020010398007')['opsi'], 'label');

        $this->assertSame(['PRJ - 00050 — BE - CST - 110-00032-1/1 · Bahan'], $lewatKode);
        $this->assertSame(['PRJ - 00050 — BE - CST - 11-00030-1/2 · Bahan'], $lewatSerial);
    }

    /**
     * Modul yang tidak bertanda `label_unit` tidak ikut berubah.
     */
    #[Test]
    public function modul_tanpa_tanda_label_unit_tetap_memakai_id_baris(): void
    {
        config()->set('perbaikan_data.modul.uji_unit_baris.label_unit', false);

        $induk = ModelUjiUnitInduk::create(['kode_transaksi' => 'PRJ - 00050']);
        $baris = $this->barisUnitJadi($induk, 'BE - CST - 11-00030-1/2');

        $label = array_column($this->service->opsiRecordJenis(['Uji Unit'], 'PRJ - 00050')['opsi'], 'label');

        $this->assertContains('PRJ - 00050 #' . $baris->id . ' · Bahan', $label);
    }

    private function barisUnitJadi(Model $induk, string $kodeUnit, ?string $serial = null): ModelUjiUnitBaris
    {
        $lotInduk = ModelUjiUnitLotInduk::create(['kode_transaksi' => $kodeUnit]);
        $lot = ModelUjiUnitLot::create(['uji_unit_lot_induk_id' => $lotInduk->id]);

        return ModelUjiUnitBaris::create([
            'uji_unit_induk_id' => $induk->id,
            'produk_jadis_id' => $lot->id,
            'serial_number' => $serial,
        ]);
    }

    /**
     * Tabel dan modul uji, dibentuk lewat Schema seperti di test saudaranya.
     *
     * Baris unit punya dua jalur: lot produk jadi (satu tingkat ke induknya)
     * dan lot setengah jadi, dengan nama relasi yang sama persis seperti model
     * aslinya supaya `jalur_unit` dari config ikut teruji apa adanya.
     */
    private function siapkanModulUji(): void
    {
        Schema::create('uji_unit_bahan', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nama_bahan');
        });

        Schema::create('uji_unit_induk', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('kode_transaksi');
        });

        Schema::create('uji_unit_lot_induk', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('kode_transaksi');
        });

        Schema::create('uji_unit_lot', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->unsignedBigInteger('uji_unit_lot_induk_id');
        });

        Schema::create('uji_unit_sj_induk', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('kode_transaksi');
        });

        Schema::create('uji_unit_sj', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->unsignedBigInteger('uji_unit_sj_induk_id');
        });

        Schema::create('uji_unit_baris', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->unsignedBigInteger('uji_unit_induk_id');
            $tabel->unsignedBigInteger('uji_unit_bahan_id')->nullable();
            $tabel->unsignedBigInteger('produk_jadis_id')->nullable();
            $tabel->unsignedBigInteger('produk_id')->nullable();
            $tabel->string('serial_number')->nullable();
        });

        config()->set('perbaikan_data.jalur_unit', [
            ['relasi' => 'dataProdukJadi.ProdukJadis', 'kolom' => 'kode_transaksi'],
            ['relasi' => 'dataProduk.bahanSetengahjadi', 'kolom' => 'kode_transaksi'],
        ]);

        config()->set('perbaikan_data.modul.uji_unit_baris', [
            'label' => 'Bahan',
            'model' => ModelUjiUnitBaris::class,
            'jenis' => ['Uji Unit'],
            'induk' => ['relasi' => 'induk', 'kode' => 'kode_transaksi'],
            'label_relasi' => ['relasi' => 'dataBahan', 'kolom' => 'nama_bahan'],
            'label_unit' => true,
            'field' => [
                'serial_number' => ['label' => 'Serial Number', 'tipe' => 'string'],
            ],
        ]);

        config()->set('perbaikan_data.modul.uji_unit_induk', [
            'label' => 'Transaksi Uji',
            'model' => ModelUjiUnitInduk::class,
            'kode' => 'kode_transaksi',
            'jenis' => ['Uji Unit'],
            'field' => [
                'kode_transaksi' => ['label' => 'Kode Transaksi', 'tipe' => 'string'],
            ],
        ]);
    }
}

class ModelUjiUnitBahan extends Model
{
    protected $table = 'uji_unit_bahan';

    public $timestamps = false;

    protected $guarded = [];
}

class ModelUjiUnitInduk extends Model
{
    protected $table = 'uji_unit_induk';

    public $timestamps = false;

    protected $guarded = [];
}

class ModelUjiUnitLotInduk extends Model
{
    protected $table = 'uji_unit_lot_induk';

    public $timestamps = false;

    protected $guarded = [];
}

class ModelUjiUnitLot extends Model
{
    protected $table = 'uji_unit_lot';

    public $timestamps = false;

    protected $guarded = [];

    public function ProdukJadis()
    {
        return $this->belongsTo(ModelUjiUnitLotInduk::class, 'uji_unit_lot_induk_id');
    }
}

class ModelUjiUnitSjInduk extends Model
{
    protected $table = 'uji_unit_sj_induk';

    public $timestamps = false;

    protected $guarded = [];
}

class ModelUjiUnitSj extends Model
{
    protected $table = 'uji_unit_sj';

    public $timestamps = false;

    protected $guarded = [];

    public function bahanSetengahjadi()
    {
        return $this->belongsTo(ModelUjiUnitSjInduk::class, 'uji_unit_sj_induk_id');
    }
}

class ModelUjiUnitBaris extends Model
{
    protected $table = 'uji_unit_baris';

    public $timestamps = false;

    protected $guarded = [];

    public function induk()
    {
        return $this->belongsTo(ModelUjiUnitInduk::class, 'uji_unit_induk_id');
    }

    public function dataBahan()
    {
        return $this->belongsTo(ModelUjiUnitBahan::class, 'uji_unit_bahan_id');
    }

    public function dataProdukJadi()
    {
        return $this->belongsTo(ModelUjiUnitLot::class, 'produk_jadis_id');
    }

    public function dataProduk()
    {
        return $this->belongsTo(ModelUjiUnitSj::class, 'produk_id');
    }
}
