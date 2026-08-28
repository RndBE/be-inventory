<?php

namespace Tests\Feature;

use App\Helpers\SatuanBahanHelper;
use App\Livewire\Concerns\MemilihSatuanBahan;
use App\Models\Bahan;
use App\Models\BahanKeluarDetails;
use App\Models\BahanReturDetails;
use App\Models\BahanRusakDetails;
use App\Models\PurchaseDetail;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Keranjang tiruan untuk mengubah angka yang diketik user jadi satuan dasar.
 */
class KeranjangPeragaan
{
    use MemilihSatuanBahan;

    public $cart = [];

    public $qty = [];
}

/**
 * Peragaan berurutan satu bahan batangan, dari nol sampai selesai dipakai.
 *
 * Bedanya dengan SatuanBahanAlurProyekTest: di sana tiap langkah diuji
 * terpisah, di sini satu bahan dilewatkan seluruh alur secara berurutan —
 * mengisi panjang di master, beli, ambil, retur, rusak — supaya angka yang
 * salah di satu langkah ikut terlihat di langkah berikutnya.
 *
 * Hollow 400 cm, harga Rp 120.000 per batang, jadi Rp 300 per cm.
 *
 * Tabelnya dibuat sendiri di SQLite in-memory. Database di .env tidak
 * tersentuh.
 */
class SatuanBahanPeragaanTest extends TestCase
{
    private const PANJANG = 400;

    private const HARGA_PER_BATANG = 120000;

    private const HARGA_PER_CM = 300.0;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.peragaan_uji' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'database.default' => 'peragaan_uji',
        ]);

        DB::purge('peragaan_uji');

        Schema::create('unit', function ($tabel) {
            $tabel->id();
            $tabel->string('nama');
            $tabel->timestamps();
        });

        Schema::create('bahan', function ($tabel) {
            $tabel->id();
            $tabel->string('nama_bahan');
            $tabel->unsignedBigInteger('unit_id')->nullable();
            $tabel->integer('panjang_standar')->nullable();
            $tabel->timestamps();
        });

        Schema::create('purchase_details', function ($tabel) {
            $tabel->id();
            $tabel->unsignedBigInteger('purchase_id')->nullable();
            $tabel->unsignedBigInteger('bahan_id');
            $tabel->integer('panjang_standar')->nullable();
            $tabel->decimal('qty', 15, 2)->default(0);
            $tabel->decimal('sisa', 15, 2)->default(0);
            $tabel->decimal('unit_price', 15, 4)->default(0);
            $tabel->decimal('sub_total', 15, 2)->default(0);
            $tabel->timestamps();
        });

        Schema::create('bahan_keluar_details', function ($tabel) {
            $tabel->id();
            $tabel->unsignedBigInteger('bahan_keluar_id')->nullable();
            $tabel->unsignedBigInteger('bahan_id')->nullable();
            $tabel->decimal('qty', 15, 2)->default(0);
            $tabel->decimal('qty_input', 15, 2)->nullable();
            $tabel->string('satuan_input', 20)->nullable();
            $tabel->decimal('unit_price', 15, 4)->default(0);
            $tabel->decimal('sub_total', 15, 2)->default(0);
            $tabel->timestamps();
        });

        Schema::create('bahan_retur_details', function ($tabel) {
            $tabel->id();
            $tabel->unsignedBigInteger('bahan_retur_id')->nullable();
            $tabel->unsignedBigInteger('bahan_id')->nullable();
            $tabel->unsignedBigInteger('produk_id')->nullable();
            $tabel->decimal('qty', 15, 2)->default(0);
            $tabel->decimal('qty_input', 15, 2)->nullable();
            $tabel->string('satuan_input', 20)->nullable();
            $tabel->decimal('unit_price', 15, 4)->default(0);
            $tabel->decimal('sub_total', 15, 2)->default(0);
            $tabel->timestamps();
        });

        Schema::create('bahan_rusak_details', function ($tabel) {
            $tabel->id();
            $tabel->unsignedBigInteger('bahan_rusak_id')->nullable();
            $tabel->unsignedBigInteger('bahan_id')->nullable();
            $tabel->unsignedBigInteger('produk_id')->nullable();
            $tabel->decimal('qty', 15, 2)->default(0);
            $tabel->decimal('qty_input', 15, 2)->nullable();
            $tabel->string('satuan_input', 20)->nullable();
            $tabel->decimal('sisa', 15, 2)->default(0);
            $tabel->decimal('unit_price', 15, 4)->default(0);
            $tabel->decimal('sub_total', 15, 2)->default(0);
            $tabel->timestamps();
        });
    }

    private function totalStok(Bahan $bahan): float
    {
        return (float) PurchaseDetail::where('bahan_id', $bahan->id)->sum('sisa');
    }

    /**
     * Tiruan pemotongan FIFO, aturannya sama dengan BahanKeluarController.
     */
    private function potongStok(Bahan $bahan, float $qtyDasar): array
    {
        $sisaKebutuhan = $qtyDasar;
        $rincian = [];

        foreach (PurchaseDetail::where('bahan_id', $bahan->id)->where('sisa', '>', 0)->orderBy('id')->get() as $lot) {
            if ($sisaKebutuhan <= 0) {
                break;
            }

            $ambil = min((float) $lot->sisa, $sisaKebutuhan);
            $lot->sisa = $lot->sisa - $ambil;
            $lot->save();

            $rincian[] = ['qty' => $ambil, 'unit_price' => (float) $lot->unit_price];
            $sisaKebutuhan -= $ambil;
        }

        return $rincian;
    }

    /**
     * Peragaan lengkap: isi cm di master, beli, ambil, retur, rusak.
     */
    public function test_peragaan_lengkap_bahan_batangan(): void
    {
        $unit = Unit::create(['nama' => 'Batang']);

        // ---- Langkah 0: bahan lama, belum ditandai batangan, stok sudah habis.
        $hollow = Bahan::create([
            'nama_bahan' => 'Hollow 4x4',
            'unit_id' => $unit->id,
            'panjang_standar' => null,
        ]);

        $lotLama = PurchaseDetail::create([
            'purchase_id' => 1,
            'bahan_id' => $hollow->id,
            'panjang_standar' => null,
            'qty' => 1,
            'sisa' => 0,
            'unit_price' => self::HARGA_PER_BATANG,
            'sub_total' => self::HARGA_PER_BATANG,
        ]);

        // ---- Langkah 1: isi "Panjang per Batang (cm)" di master bahan.
        // Ini yang dijalankan BahanController::update dalam satu transaksi.
        $terkonversi = PurchaseDetail::konversiLotLama($hollow, self::PANJANG);
        $hollow->update(['panjang_standar' => self::PANJANG]);
        $hollow->refresh();
        $lotLama->refresh();

        $this->assertSame(1, $terkonversi);
        $this->assertEquals(400, $lotLama->qty, 'riwayat 1 batang jadi 400 cm');
        $this->assertEquals(self::HARGA_PER_CM, $lotLama->unit_price, 'harga per batang jadi harga per cm');
        $this->assertEquals(self::HARGA_PER_BATANG, $lotLama->sub_total, 'nilai pembukuan tidak bergeser');
        $this->assertSame(0.0, $this->totalStok($hollow), 'stoknya tetap nol');
        $this->assertTrue($hollow->dwiSatuan(), 'sesudah diisi, bahan ini jadi bahan batangan');

        // ---- Langkah 2: beli 3 batang lewat Bahan Masuk.
        $lotBaru = PurchaseDetail::catatLot([
            'purchase_id' => 2,
            'bahan_id' => $hollow->id,
            'qty' => 3,
            'unit_price' => self::HARGA_PER_BATANG,
            'sub_total' => 3 * self::HARGA_PER_BATANG,
        ], SatuanBahanHelper::SATUAN_BATANG)->refresh();

        $this->assertEquals(1200, $lotBaru->qty);
        $this->assertEquals(self::HARGA_PER_CM, $lotBaru->unit_price);
        $this->assertEquals(360000, $lotBaru->sub_total, 'subtotal dari 3 x 120.000, eksak');
        $this->assertEquals(self::PANJANG, $lotBaru->panjang_standar);
        $this->assertSame(1200.0, $this->totalStok($hollow));
        $this->assertSame('3 Batang', $hollow->formatQty($this->totalStok($hollow)));

        // ---- Langkah 3: ambil 2 batang untuk proyek.
        $keranjang = new KeranjangPeragaan();
        $keranjang->cart = [(object) ['bahan_id' => $hollow->id, 'panjang_standar' => self::PANJANG]];
        $keranjang->satuan[$hollow->id] = SatuanBahanHelper::SATUAN_BATANG;
        $keranjang->qty[$hollow->id] = 2;

        // Batasnya 3 batang; minta 4 harus dipotong.
        $this->assertSame(3.0, $keranjang->maksInput($hollow->id, $this->totalStok($hollow)));

        $qtyKeluar = $keranjang->qtyDasar($hollow->id);
        $this->assertSame(800.0, $qtyKeluar);

        $rincian = $this->potongStok($hollow, $qtyKeluar);
        $hargaPerCm = $rincian[0]['unit_price'];

        $keluar = BahanKeluarDetails::create([
            'bahan_keluar_id' => 1,
            'bahan_id' => $hollow->id,
            'qty' => $qtyKeluar,
            'qty_input' => $keranjang->qty[$hollow->id],
            'satuan_input' => $keranjang->satuanUntuk($hollow->id),
            'unit_price' => $hargaPerCm,
            'sub_total' => SatuanBahanHelper::nilaiSatuanDasar($qtyKeluar, $hargaPerCm),
        ]);

        $this->assertEquals(800, $keluar->qty, 'ledger dalam cm');
        $this->assertEquals(2, $keluar->qty_input, 'angka yang diketik user tetap tersimpan');
        $this->assertSame('batang', $keluar->satuan_input);
        $this->assertEquals(240000, $keluar->sub_total, '2 batang x 120.000');
        $this->assertSame(400.0, $this->totalStok($hollow));
        $this->assertSame('1 Batang', $hollow->formatQty($this->totalStok($hollow)));

        // ---- Langkah 4: dari 800 cm yang dibawa, 150 cm balik ke gudang.
        $retur = BahanReturDetails::catatRetur([
            'bahan_retur_id' => 1,
            'bahan_id' => $hollow->id,
            'qty' => 150,
            'unit_price' => $hargaPerCm,
            'sub_total' => SatuanBahanHelper::nilaiSatuanDasar(150, $hargaPerCm),
        ]);

        $this->assertEquals(150, $retur->qty_input);
        $this->assertSame('cm', $retur->satuan_input, 'retur dari proyek selalu cm');
        $this->assertEquals(45000, $retur->sub_total);

        // Retur disetujui: masuk lagi sebagai lot baru, tanpa konversi.
        $lotRetur = PurchaseDetail::catatLot([
            'purchase_id' => 3,
            'bahan_id' => $hollow->id,
            'qty' => $retur->qty,
            'unit_price' => $retur->unit_price,
            'sub_total' => $retur->sub_total,
        ], SatuanBahanHelper::SATUAN_DASAR)->refresh();

        $this->assertEquals(150, $lotRetur->sisa);
        $this->assertEquals(self::PANJANG, $lotRetur->panjang_standar);
        $this->assertSame('150 cm', $lotRetur->formatSisa());
        $this->assertSame(550.0, $this->totalStok($hollow));
        $this->assertSame('1 Batang + 150 cm', $hollow->formatQty($this->totalStok($hollow)));

        // ---- Langkah 5: 50 cm rusak di proyek.
        // Bahan rusak tidak mengembalikan apa pun ke stok - barangnya sudah
        // keluar dan memang tidak balik. Yang dicatat cuma kerugiannya.
        $rusak = BahanRusakDetails::create([
            'bahan_rusak_id' => 1,
            'bahan_id' => $hollow->id,
            'qty' => 50,
            'sisa' => 50,
            'unit_price' => $hargaPerCm,
            'sub_total' => SatuanBahanHelper::nilaiSatuanDasar(50, $hargaPerCm),
        ]);

        $this->assertEquals(50, $rusak->qty, 'angka rusak juga dalam cm');
        $this->assertEquals(15000, $rusak->sub_total);
        $this->assertSame(550.0, $this->totalStok($hollow), 'stok tidak berubah karena rusak');

        // Jejak satuan bahan rusak belum diisi oleh kode aplikasi mana pun.
        // Kolomnya ada, tapi tidak ada pemanggil yang mengisinya - dicatat di
        // sini supaya kalau nanti diisi, test ini yang memberi tahu.
        $this->assertNull($rusak->satuan_input);
        $this->assertNull($rusak->qty_input);

        // ---- Penutup: neraca cm harus tertutup.
        $dibeli = 1200.0;
        $tersisa = $this->totalStok($hollow);
        $terpakaiProyek = 800.0 - 150.0 - 50.0;

        $this->assertSame(650.0, $dibeli - $tersisa, 'yang benar-benar habis 650 cm');
        $this->assertSame(650.0, $terpakaiProyek + 50.0, 'terpakai 600 cm plus rusak 50 cm');
    }

    /**
     * Peragaan yang sama untuk bahan biasa: tidak boleh ada yang berubah.
     */
    public function test_peragaan_bahan_biasa_tidak_berubah(): void
    {
        $unit = Unit::create(['nama' => 'Pcs']);
        $baut = Bahan::create([
            'nama_bahan' => 'Baut M8',
            'unit_id' => $unit->id,
            'panjang_standar' => null,
        ]);

        // Beli 100 pcs. Satuan 'batang' dikirim oleh klien lama yang tidak tahu
        // apa-apa soal fitur ini, dan harus diabaikan.
        $lot = PurchaseDetail::catatLot([
            'purchase_id' => 1,
            'bahan_id' => $baut->id,
            'qty' => 100,
            'unit_price' => 2500,
        ], SatuanBahanHelper::SATUAN_BATANG)->refresh();

        $this->assertEquals(100, $lot->qty);
        $this->assertEquals(2500, $lot->unit_price);
        $this->assertNull($lot->panjang_standar);

        $keranjang = new KeranjangPeragaan();
        $keranjang->cart = [(object) ['bahan_id' => $baut->id, 'panjang_standar' => null]];
        $keranjang->satuan[$baut->id] = SatuanBahanHelper::SATUAN_BATANG;
        $keranjang->qty[$baut->id] = 30;

        $this->assertSame(30.0, $keranjang->qtyDasar($baut->id), 'tidak dikali apa pun');
        $this->assertSame([], SatuanBahanHelper::pilihanSatuan($baut), 'dropdown tidak dirender');

        $this->potongStok($baut, 30);

        $retur = BahanReturDetails::catatRetur([
            'bahan_retur_id' => 1,
            'bahan_id' => $baut->id,
            'qty' => 5,
            'unit_price' => 2500,
            'sub_total' => 12500,
        ]);

        PurchaseDetail::catatLot([
            'purchase_id' => 2,
            'bahan_id' => $baut->id,
            'qty' => $retur->qty,
            'unit_price' => $retur->unit_price,
            'sub_total' => $retur->sub_total,
        ], SatuanBahanHelper::SATUAN_DASAR);

        $this->assertNull($retur->satuan_input, 'bahan biasa tidak punya jejak satuan');
        $this->assertSame(75.0, $this->totalStok($baut));
        $this->assertSame('75 Pcs', $baut->formatQty($this->totalStok($baut)));
    }

    /**
     * Bahan yang masih punya sisa tidak boleh dikonversi diam-diam.
     *
     * Ini pasangan dari penjagaan di BahanController: konversi hanya dijalankan
     * kalau tidak ada sisa berjalan. Kalau penjagaan itu hilang, angka stok
     * hidup akan terkali panjang standar tanpa ada yang tahu.
     */
    public function test_konversi_menaikkan_sisa_yang_masih_berjalan(): void
    {
        $unit = Unit::create(['nama' => 'Batang']);
        $hollow = Bahan::create([
            'nama_bahan' => 'Hollow 4x4',
            'unit_id' => $unit->id,
            'panjang_standar' => null,
        ]);

        $lot = PurchaseDetail::create([
            'purchase_id' => 1,
            'bahan_id' => $hollow->id,
            'panjang_standar' => null,
            'qty' => 12,
            'sisa' => 12,
            'unit_price' => self::HARGA_PER_BATANG,
            'sub_total' => 12 * self::HARGA_PER_BATANG,
        ]);

        PurchaseDetail::konversiLotLama($hollow, self::PANJANG);

        // 12 batang jadi 4800 cm. Benar sebagai konversi, tapi inilah kenapa
        // pemanggilnya wajib menolak bahan yang sisanya masih jalan: kalau
        // panjangnya salah, stok hidupnya yang jadi korban.
        $this->assertEquals(4800, $lot->refresh()->sisa);
    }
}
