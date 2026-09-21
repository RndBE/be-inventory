<?php

namespace Tests\Unit;

use App\Services\PerbaikanDataService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dropdown "Kode transaksi" pada form Perbaikan Data.
 *
 * Daftarnya dirakit dari beberapa modul sekaligus dan selalu dipotong, jadi
 * yang menentukan berguna atau tidaknya bukan isi tabelnya, melainkan mana yang
 * lolos ke layar. Tiga hal yang diuji di sini semuanya pernah gagal di produksi
 * pada satu kasus yang sama: produksi dengan 107 baris bahan, daftar dipotong
 * di 15, urutan menaruh baris detail di depan, dan pencarian cuma menyentuh
 * kode induk. Akibatnya baris bahan yang paling tua tidak bisa dipilih sama
 * sekali, dan tidak ada tanda apa pun bahwa daftarnya belum lengkap.
 */
class PerbaikanDataOpsiRecordTest extends TestCase
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
        Schema::dropIfExists('uji_baris');
        Schema::dropIfExists('uji_induk');
        Schema::dropIfExists('uji_bahan');

        parent::tearDown();
    }

    /**
     * Transaksinya sendiri jadi pilihan pertama, baris bahannya menyusul.
     *
     * Modul baris sengaja didaftarkan lebih dulu di config, persis seperti
     * keadaan sebenarnya. Kalau urutannya ikut urutan config, satu produksi
     * dengan ratusan baris akan mengubur transaksinya sendiri di paling bawah —
     * padahal transaksi itulah yang dipegang pengaju saat membuka form.
     */
    #[Test]
    public function record_induk_muncul_lebih_dulu_daripada_baris_bahannya(): void
    {
        $induk = ModelUjiInduk::create(['kode_transaksi' => 'PRD-001']);
        $bahan = ModelUjiBahan::create(['nama_bahan' => 'Buzzer 5V']);
        ModelUjiBaris::create(['uji_induk_id' => $induk->id, 'uji_bahan_id' => $bahan->id, 'qty' => 3]);

        $hasil = $this->service->opsiRecordJenis(['Uji Jenis'], 'PRD-001');
        $label = array_column($hasil['opsi'], 'label');

        $this->assertSame('PRD-001 · Transaksi Uji', $label[0]);
        $this->assertSame('PRD-001 — Buzzer 5V · Bahan', $label[1]);
    }

    /**
     * Nama bahan bisa diketik, bukan cuma kode transaksinya.
     *
     * Nama itu sudah tertulis di tiap pilihan. Kalau tidak ikut dicari, satu-
     * satunya jalan menemukan baris adalah menggulir seluruh daftar — dan
     * daftarnya dipotong.
     */
    #[Test]
    public function baris_bisa_dicari_lewat_nama_bahannya(): void
    {
        $induk = ModelUjiInduk::create(['kode_transaksi' => 'PRD-001']);
        $buzzer = ModelUjiBahan::create(['nama_bahan' => 'Buzzer 5V']);
        $resistor = ModelUjiBahan::create(['nama_bahan' => 'Resistor 10k']);

        ModelUjiBaris::create(['uji_induk_id' => $induk->id, 'uji_bahan_id' => $buzzer->id, 'qty' => 3]);
        ModelUjiBaris::create(['uji_induk_id' => $induk->id, 'uji_bahan_id' => $resistor->id, 'qty' => 4]);

        $hasil = $this->service->opsiRecordJenis(['Uji Jenis'], 'buzzer');
        $label = array_column($hasil['opsi'], 'label');

        $this->assertSame(['PRD-001 — Buzzer 5V · Bahan'], $label);
    }

    /**
     * Kata pencarian melebarkan jatahnya.
     *
     * Tanpa kata pencarian daftarnya memang cuma jendela ke record terbaru.
     * Begitu kode transaksinya diketik, yang diminta sudah spesifik dan seluruh
     * barisnya harus bisa dipilih — termasuk baris yang id-nya paling kecil,
     * yang justru paling sering jadi bahan pertama transaksi itu.
     */
    #[Test]
    public function kode_transaksi_yang_diketik_memunculkan_semua_barisnya(): void
    {
        $induk = ModelUjiInduk::create(['kode_transaksi' => 'PRD-001']);

        for ($i = 1; $i <= 60; $i++) {
            $bahan = ModelUjiBahan::create(['nama_bahan' => 'Bahan ke-' . $i]);
            ModelUjiBaris::create(['uji_induk_id' => $induk->id, 'uji_bahan_id' => $bahan->id, 'qty' => 1]);
        }

        $hasil = $this->service->opsiRecordJenis(['Uji Jenis'], 'PRD-001');
        $label = array_column($hasil['opsi'], 'label');

        $this->assertCount(61, $hasil['opsi']);
        $this->assertFalse($hasil['terpotong']);
        $this->assertContains('PRD-001 — Bahan ke-1 · Bahan', $label);
    }

    /**
     * Daftar yang dipotong mengatakannya.
     *
     * Ini bagian yang paling mudah dianggap sepele dan paling merugikan kalau
     * hilang: tanpa tanda, baris yang tidak terkirim tidak bisa dibedakan dari
     * baris yang memang tidak ada, dan pengaju berhenti mencari.
     */
    #[Test]
    public function daftar_tanpa_kata_pencarian_melaporkan_kalau_masih_ada_sisa(): void
    {
        $induk = ModelUjiInduk::create(['kode_transaksi' => 'PRD-001']);

        for ($i = 1; $i <= 40; $i++) {
            $bahan = ModelUjiBahan::create(['nama_bahan' => 'Bahan ke-' . $i]);
            ModelUjiBaris::create(['uji_induk_id' => $induk->id, 'uji_bahan_id' => $bahan->id, 'qty' => 1]);
        }

        $hasil = $this->service->opsiRecordJenis(['Uji Jenis']);

        $this->assertTrue($hasil['terpotong']);
        $this->assertLessThan(41, count($hasil['opsi']));
    }

    /**
     * Tabel dan modul uji, dibentuk lewat Schema.
     *
     * Bukan lewat migration: riwayat migration proyek ini belum bisa jalan dari
     * database kosong. Modul barisnya didaftarkan lebih dulu supaya urutan
     * config benar-benar berlawanan dengan urutan yang diharapkan keluar.
     */
    private function siapkanModulUji(): void
    {
        Schema::create('uji_bahan', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nama_bahan');
        });

        Schema::create('uji_induk', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('kode_transaksi');
        });

        Schema::create('uji_baris', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->unsignedBigInteger('uji_induk_id');
            $tabel->unsignedBigInteger('uji_bahan_id');
            $tabel->decimal('qty', 15, 2)->nullable();
        });

        config()->set('perbaikan_data.modul.uji_baris', [
            'label' => 'Bahan',
            'model' => ModelUjiBaris::class,
            'jenis' => ['Uji Jenis'],
            'induk' => ['relasi' => 'induk', 'kode' => 'kode_transaksi'],
            'label_relasi' => ['relasi' => 'dataBahan', 'kolom' => 'nama_bahan'],
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal'],
            ],
        ]);

        config()->set('perbaikan_data.modul.uji_induk', [
            'label' => 'Transaksi Uji',
            'model' => ModelUjiInduk::class,
            'kode' => 'kode_transaksi',
            'jenis' => ['Uji Jenis'],
            'field' => [
                'kode_transaksi' => ['label' => 'Kode Transaksi', 'tipe' => 'string'],
            ],
        ]);
    }
}

class ModelUjiInduk extends Model
{
    protected $table = 'uji_induk';

    public $timestamps = false;

    protected $guarded = [];
}

class ModelUjiBahan extends Model
{
    protected $table = 'uji_bahan';

    public $timestamps = false;

    protected $guarded = [];
}

class ModelUjiBaris extends Model
{
    protected $table = 'uji_baris';

    public $timestamps = false;

    protected $guarded = [];

    public function induk()
    {
        return $this->belongsTo(ModelUjiInduk::class, 'uji_induk_id');
    }

    public function dataBahan()
    {
        return $this->belongsTo(ModelUjiBahan::class, 'uji_bahan_id');
    }
}
