<?php

namespace Tests\Feature;

use App\Helpers\SatuanBahanHelper;
use App\Livewire\Concerns\MemilihSatuanBahan;
use App\Models\Bahan;
use App\Models\BahanKeluarDetails;
use App\Models\BahanReturDetails;
use App\Models\PurchaseDetail;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Keranjang tiruan untuk mengubah angka yang diketik user jadi satuan dasar.
 */
class KeranjangProyekTiruan
{
    use MemilihSatuanBahan;

    public $cart = [];

    public $qty = [];

    public function batasi($itemId, $qtyInput, $stokDasar)
    {
        return $this->batasiQtyInput($itemId, $qtyInput, $stokDasar);
    }
}

/**
 * Alur lengkap bahan batangan: masuk, diambil untuk proyek, lalu diretur.
 *
 * Dipakai hollow 400 cm supaya jelas panjangnya tidak harus 600 dan angkanya
 * tidak kebetulan cocok dengan contoh di tempat lain.
 *
 * Tabelnya dibuat sendiri di SQLite in-memory, bukan lewat RefreshDatabase,
 * supaya database di .env tidak pernah tersentuh.
 *
 * Batas yang perlu diketahui: pemotongan stok FIFO yang sesungguhnya ada di
 * dalam BahanKeluarController, satu method panjang yang tidak bisa dipanggil
 * tanpa membangun belasan tabel dan sesi login. Yang diuji di sini rantai
 * angkanya — konversi input, pencatatan lot, pencatatan retur, dan lot baru
 * hasil retur — plus invarian totalnya. Pemotongan lotnya sendiri ditiru
 * dengan aturan yang sama, dan salinan itu tidak akan ikut berubah kalau
 * aturan aslinya diubah.
 */
class SatuanBahanAlurProyekTest extends TestCase
{
    private const PANJANG = 400;

    private const HARGA_PER_BATANG = 120000;

    private const HARGA_PER_CM = 300.0;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.alur_uji' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'database.default' => 'alur_uji',
        ]);

        DB::purge('alur_uji');

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
    }

    private function hollow(): Bahan
    {
        $unit = Unit::create(['nama' => 'Batang']);

        return Bahan::create([
            'nama_bahan' => 'Hollow 4x4',
            'unit_id' => $unit->id,
            'panjang_standar' => self::PANJANG,
        ]);
    }

    /**
     * Beli sejumlah batang lewat jalur bahan masuk.
     */
    private function beliBatang(Bahan $bahan, int $purchaseId, float $batang): PurchaseDetail
    {
        return PurchaseDetail::catatLot([
            'purchase_id' => $purchaseId,
            'bahan_id' => $bahan->id,
            'qty' => $batang,
            'unit_price' => self::HARGA_PER_BATANG,
        ], SatuanBahanHelper::SATUAN_BATANG);
    }

    /**
     * Tiruan pemotongan FIFO: ambil dari lot terlama sampai kebutuhan terpenuhi.
     */
    private function potongStok(Bahan $bahan, float $qtyDasar): array
    {
        $sisaKebutuhan = $qtyDasar;
        $rincian = [];

        $lots = PurchaseDetail::where('bahan_id', $bahan->id)
            ->where('sisa', '>', 0)
            ->orderBy('id')
            ->get();

        foreach ($lots as $lot) {
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

    private function totalStok(Bahan $bahan): float
    {
        return (float) PurchaseDetail::where('bahan_id', $bahan->id)->sum('sisa');
    }

    private function keranjang(Bahan $bahan, ?int $panjangStandar = self::PANJANG): KeranjangProyekTiruan
    {
        $keranjang = new KeranjangProyekTiruan();
        $keranjang->cart = [(object) ['bahan_id' => $bahan->id, 'panjang_standar' => $panjangStandar]];

        return $keranjang;
    }

    /**
     * Harga per batang tersimpan sebagai harga per cm, dan dua lot terpisah
     * menghasilkan stok gabungan dalam cm.
     */
    public function test_beli_dua_lot_batang_jadi_stok_cm(): void
    {
        $hollow = $this->hollow();
        $this->beliBatang($hollow, 1, 2);
        $this->beliBatang($hollow, 2, 3);

        $this->assertSame(2000.0, $this->totalStok($hollow));
        $this->assertEquals(self::HARGA_PER_CM, PurchaseDetail::first()->unit_price);
        $this->assertSame('5 Batang', $hollow->formatQty($this->totalStok($hollow)));
    }

    /**
     * Ambil 1 batang untuk proyek: yang dipotong 400 cm, dan angka "1" yang
     * diketik user tetap tersimpan terpisah untuk cetakan.
     */
    public function test_ambil_satu_batang_memotong_empat_ratus_cm(): void
    {
        $hollow = $this->hollow();
        $lot = $this->beliBatang($hollow, 1, 2);

        $keranjang = $this->keranjang($hollow);
        $keranjang->satuan[$hollow->id] = SatuanBahanHelper::SATUAN_BATANG;
        $keranjang->qty[$hollow->id] = 1;

        $qtyDasar = $keranjang->qtyDasar($hollow->id);
        $this->assertSame(400.0, $qtyDasar);

        $rincian = $this->potongStok($hollow, $qtyDasar);

        $keluar = BahanKeluarDetails::create([
            'bahan_keluar_id' => 1,
            'bahan_id' => $hollow->id,
            'qty' => $qtyDasar,
            'qty_input' => $keranjang->qty[$hollow->id],
            'satuan_input' => $keranjang->satuanUntuk($hollow->id),
            'unit_price' => $rincian[0]['unit_price'],
            'sub_total' => SatuanBahanHelper::nilaiSatuanDasar($qtyDasar, $rincian[0]['unit_price']),
        ]);

        $this->assertSame(400.0, (float) $lot->fresh()->sisa);
        $this->assertEquals(400, $keluar->qty);
        $this->assertEquals(1, $keluar->qty_input);
        $this->assertSame('batang', $keluar->satuan_input);
        $this->assertEquals(120000, $keluar->sub_total);
    }

    /**
     * Ambil per batang, retur per cm — inti pertanyaannya.
     *
     * Ambil 1 batang (400 cm), proyek cuma pakai 250 cm, sisanya 150 cm balik
     * ke gudang jadi lot baru.
     */
    public function test_ambil_per_batang_lalu_retur_potongannya(): void
    {
        $hollow = $this->hollow();
        $lotAwal = $this->beliBatang($hollow, 1, 2);

        $rincian = $this->potongStok($hollow, 400);
        $hargaPerCm = $rincian[0]['unit_price'];

        // Yang dikembalikan potongan, jadi angkanya memang dalam cm.
        $retur = BahanReturDetails::catatRetur([
            'bahan_retur_id' => 1,
            'bahan_id' => $hollow->id,
            'qty' => 150,
            'unit_price' => $hargaPerCm,
            'sub_total' => SatuanBahanHelper::nilaiSatuanDasar(150, $hargaPerCm),
        ]);

        $this->assertEquals(150, $retur->qty_input);
        $this->assertSame('cm', $retur->satuan_input);
        $this->assertEquals(45000, $retur->sub_total);

        // Retur disetujui: masuk lagi sebagai lot baru, tanpa konversi.
        $lotRetur = PurchaseDetail::catatLot([
            'purchase_id' => 99,
            'bahan_id' => $hollow->id,
            'qty' => $retur->qty,
            'unit_price' => $retur->unit_price,
            'sub_total' => $retur->sub_total,
        ], SatuanBahanHelper::SATUAN_DASAR)->refresh();

        $this->assertEquals(150, $lotRetur->qty);
        $this->assertEquals(150, $lotRetur->sisa);
        $this->assertEquals(self::PANJANG, $lotRetur->panjang_standar);
        $this->assertEquals($hargaPerCm, $lotRetur->unit_price);

        // Sisa lot awal 400 cm, plus lot retur 150 cm.
        $this->assertSame(550.0, $this->totalStok($hollow));
        $this->assertSame(400.0, (float) $lotAwal->fresh()->sisa);

        // Netto terpakai: 800 dibeli, 550 tersisa, jadi 250 cm benar-benar habis.
        $this->assertSame(250.0, 800.0 - $this->totalStok($hollow));

        // Lot retur bukan kelipatan batang, dan tampil sebagai potongan.
        $this->assertFalse(SatuanBahanHelper::kelipatanBatang($lotRetur->sisa, self::PANJANG));
        $this->assertSame('150 cm', $lotRetur->formatSisa());
    }

    /**
     * Stok gabungan sesudah retur tampil sebagai batang plus sisa potongan.
     */
    public function test_stok_gabungan_sesudah_retur_terbaca_benar(): void
    {
        $hollow = $this->hollow();
        $this->beliBatang($hollow, 1, 2);
        $this->potongStok($hollow, 400);

        PurchaseDetail::catatLot([
            'purchase_id' => 99,
            'bahan_id' => $hollow->id,
            'qty' => 150,
            'unit_price' => self::HARGA_PER_CM,
        ], SatuanBahanHelper::SATUAN_DASAR);

        $this->assertSame('1 Batang + 150 cm', $hollow->formatQty($this->totalStok($hollow)));
    }

    /**
     * Sesudah retur, batas pengambilan berikutnya ikut menyesuaikan: 550 cm
     * cuma cukup untuk 1 batang utuh, sisanya harus diambil dengan satuan cm.
     */
    public function test_batas_pengambilan_berikutnya_ikut_sisa_campuran(): void
    {
        $hollow = $this->hollow();
        $keranjang = $this->keranjang($hollow);

        $keranjang->satuan[$hollow->id] = SatuanBahanHelper::SATUAN_BATANG;
        $this->assertSame(1.0, $keranjang->maksInput($hollow->id, 550));
        $this->assertSame(1.0, $keranjang->batasi($hollow->id, 2, 550));

        $keranjang->satuan[$hollow->id] = SatuanBahanHelper::SATUAN_DASAR;
        $this->assertSame(550.0, $keranjang->maksInput($hollow->id, 550));
        $this->assertSame(500, $keranjang->batasi($hollow->id, 500, 550));
    }

    /**
     * Bahan non-batangan lewat alur yang sama tidak berubah sama sekali.
     */
    public function test_bahan_biasa_lewat_alur_yang_sama_tidak_berubah(): void
    {
        $unit = Unit::create(['nama' => 'Pcs']);
        $baut = Bahan::create([
            'nama_bahan' => 'Baut M8',
            'unit_id' => $unit->id,
            'panjang_standar' => null,
        ]);

        PurchaseDetail::catatLot([
            'purchase_id' => 1,
            'bahan_id' => $baut->id,
            'qty' => 100,
            'unit_price' => 2500,
        ], SatuanBahanHelper::SATUAN_BATANG);

        $this->potongStok($baut, 30);

        $retur = BahanReturDetails::catatRetur([
            'bahan_retur_id' => 1,
            'bahan_id' => $baut->id,
            'qty' => 5,
            'unit_price' => 2500,
            'sub_total' => 12500,
        ]);

        $this->assertSame(70.0, $this->totalStok($baut));
        $this->assertNull($retur->satuan_input);
        $this->assertSame('70 Pcs', $baut->formatQty($this->totalStok($baut)));
    }
}
