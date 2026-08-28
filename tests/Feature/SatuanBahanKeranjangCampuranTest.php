<?php

namespace Tests\Feature;

use App\Helpers\SatuanBahanHelper;
use App\Livewire\BahanKeluarCart;
use App\Livewire\BahanPurchaseCart;
use App\Models\Bahan;
use App\Models\PurchaseDetail;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Satu transaksi bahan masuk yang isinya campur: bahan batangan dan bahan biasa.
 *
 * Ini kasus yang paling gampang salah, karena satuan disimpan per baris dalam
 * array yang di-key bahan_id. Kalau ada satu titik yang memakai satuan baris
 * lain — atau memakai satu satuan untuk seluruh keranjang — barisnya saling
 * merusak dan angkanya tidak kelihatan salah di layar.
 *
 * Pipa 600 cm @ Rp 175.000 per batang, dan baut Pcs @ Rp 2.500, dalam satu
 * keranjang.
 */
class SatuanBahanKeranjangCampuranTest extends TestCase
{
    private const PANJANG = 600;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.campuran_uji' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'database.default' => 'campuran_uji',
        ]);

        DB::purge('campuran_uji');

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
    }

    private function bahan(string $nama, string $namaUnit, ?int $panjang): Bahan
    {
        return Bahan::create([
            'nama_bahan' => $nama,
            'unit_id' => Unit::create(['nama' => $namaUnit])->id,
            'panjang_standar' => $panjang,
        ]);
    }

    /**
     * Payload yang dikirim komponen pencarian. Sengaja TIDAK memuat
     * `panjang_standar`, persis seperti SearchBahanDanProduk yang asli —
     * keranjangnya yang wajib mencarinya sendiri.
     */
    private function payload(Bahan $bahan): object
    {
        return (object) [
            'bahan_id' => $bahan->id,
            'nama' => $bahan->nama_bahan,
            'kode' => 'KODE-' . $bahan->id,
            'stok' => 0,
            'unit' => $bahan->dataUnit->nama ?? 'N/A',
            'type' => 'bahan',
        ];
    }

    private function keranjangCampuran(): array
    {
        $pipa = $this->bahan('Pipa Galvanis 1"', 'Batang', self::PANJANG);
        $baut = $this->bahan('Baut M8', 'Pcs', null);

        $keranjang = new BahanPurchaseCart();
        $keranjang->mount();
        $keranjang->addToCart($this->payload($pipa));
        $keranjang->addToCart($this->payload($baut));

        return [$keranjang, $pipa, $baut];
    }

    /**
     * Panjang standar harus tetap terbaca walau payload pencarian tidak
     * membawanya. Ini yang bikin dropdown satuan muncul di halaman bahan masuk.
     */
    public function test_panjang_standar_dicari_sendiri_walau_payload_tidak_bawa(): void
    {
        [$keranjang, $pipa, $baut] = $this->keranjangCampuran();

        $this->assertSame(self::PANJANG, $keranjang->panjangStandarUntuk($pipa->id));
        $this->assertNull($keranjang->panjangStandarUntuk($baut->id));
    }

    /**
     * Satuan awal ditentukan per baris, bukan satu untuk seluruh keranjang.
     */
    public function test_satuan_awal_beda_per_baris(): void
    {
        [$keranjang, $pipa, $baut] = $this->keranjangCampuran();

        $this->assertSame('batang', $keranjang->satuanUntuk($pipa->id));
        $this->assertSame('cm', $keranjang->satuanUntuk($baut->id));
    }

    /**
     * Pemeriksaan silang "masuk stok N cm" hanya berlaku untuk baris batangan.
     */
    public function test_cross_check_cm_hanya_untuk_baris_batangan(): void
    {
        [$keranjang, $pipa, $baut] = $this->keranjangCampuran();

        $keranjang->qty[$pipa->id] = 5;
        $keranjang->qty[$baut->id] = 10;

        $this->assertSame(3000.0, $keranjang->totalSatuanDasar($pipa->id));
        $this->assertSame(10.0, $keranjang->totalSatuanDasar($baut->id));
    }

    /**
     * Subtotal tiap baris dihitung dari angka yang diketik di baris itu, dan
     * total keranjangnya jumlah keduanya tanpa pembulatan yang bergeser.
     */
    public function test_subtotal_per_baris_dan_total_keranjang(): void
    {
        [$keranjang, $pipa, $baut] = $this->keranjangCampuran();

        $keranjang->qty[$pipa->id] = 5;
        $keranjang->unit_price_raw[$pipa->id] = '175000';
        $keranjang->formatToRupiah($pipa->id);

        $keranjang->qty[$baut->id] = 10;
        $keranjang->unit_price_raw[$baut->id] = '2500';
        $keranjang->formatToRupiah($baut->id);

        $this->assertSame(875000.0, $keranjang->subtotals[$pipa->id]);
        $this->assertSame(25000.0, $keranjang->subtotals[$baut->id]);
        $this->assertSame(900000.0, $keranjang->totalharga);
    }

    /**
     * Payload yang dikirim ke controller: satuan hanya ikut untuk baris
     * batangan, dan null untuk baris biasa supaya perilaku lamanya utuh.
     */
    public function test_payload_ke_controller_membawa_satuan_per_baris(): void
    {
        [$keranjang, $pipa, $baut] = $this->keranjangCampuran();

        $keranjang->qty[$pipa->id] = 5;
        $keranjang->unit_price_raw[$pipa->id] = '175000';
        $keranjang->formatToRupiah($pipa->id);

        $keranjang->qty[$baut->id] = 10;
        $keranjang->unit_price_raw[$baut->id] = '2500';
        $keranjang->formatToRupiah($baut->id);

        $items = collect($keranjang->getCartItemsForStorage())->keyBy('id');

        $this->assertSame('batang', $items[$pipa->id]['satuan']);
        $this->assertNull($items[$baut->id]['satuan']);
        $this->assertSame(5.0, $items[$pipa->id]['qty']);
        $this->assertSame(10.0, $items[$baut->id]['qty']);
    }

    /**
     * Ujungnya: dua baris itu disimpan lewat catatLot seperti yang dilakukan
     * PurchaseController, dan masing-masing dikonversi menurut satuannya sendiri.
     */
    public function test_dua_baris_tersimpan_dengan_konversi_masing_masing(): void
    {
        [$keranjang, $pipa, $baut] = $this->keranjangCampuran();

        $keranjang->qty[$pipa->id] = 5;
        $keranjang->unit_price_raw[$pipa->id] = '175000';
        $keranjang->formatToRupiah($pipa->id);

        $keranjang->qty[$baut->id] = 10;
        $keranjang->unit_price_raw[$baut->id] = '2500';
        $keranjang->formatToRupiah($baut->id);

        foreach ($keranjang->getCartItemsForStorage() as $item) {
            PurchaseDetail::catatLot([
                'purchase_id' => 1,
                'bahan_id' => $item['id'],
                'qty' => $item['qty'],
                'unit_price' => $item['unit_price'],
                'sub_total' => $item['sub_total'],
            ], $item['satuan'] ?? SatuanBahanHelper::SATUAN_BATANG);
        }

        $lotPipa = PurchaseDetail::where('bahan_id', $pipa->id)->first();
        $lotBaut = PurchaseDetail::where('bahan_id', $baut->id)->first();

        $this->assertEquals(3000, $lotPipa->qty);
        $this->assertEquals(291.6667, $lotPipa->unit_price);
        $this->assertEquals(875000, $lotPipa->sub_total);
        $this->assertEquals(self::PANJANG, $lotPipa->panjang_standar);

        $this->assertEquals(10, $lotBaut->qty);
        $this->assertEquals(2500, $lotBaut->unit_price);
        $this->assertEquals(25000, $lotBaut->sub_total);
        $this->assertNull($lotBaut->panjang_standar);

        $this->assertSame('5 Batang', $pipa->formatQty($lotPipa->sisa));
        $this->assertSame('10 Pcs', $baut->formatQty($lotBaut->sisa));
    }

    /**
     * Pembelian ke supplier selalu per batang, jadi baris pipa tidak punya
     * pilihan satuan sama sekali - angka dan harganya selalu per batang.
     */
    public function test_baris_batangan_selalu_dibeli_per_batang(): void
    {
        [$keranjang, $pipa, $baut] = $this->keranjangCampuran();

        $this->assertSame(SatuanBahanHelper::SATUAN_BATANG, $keranjang->satuanUntuk($pipa->id));
        $this->assertSame(SatuanBahanHelper::SATUAN_DASAR, $keranjang->satuanUntuk($baut->id));

        $keranjang->qty[$pipa->id] = 5;
        $keranjang->unit_price_raw[$pipa->id] = '175000';
        $keranjang->formatToRupiah($pipa->id);

        // 5 batang x 600 cm, dan harganya tetap harga per batang.
        $this->assertSame(3000.0, $keranjang->totalSatuanDasar($pipa->id));
        $this->assertSame(875000.0, $keranjang->subtotals[$pipa->id]);

        $items = collect($keranjang->getCartItemsForStorage())->keyBy('id');
        $this->assertSame(SatuanBahanHelper::SATUAN_BATANG, $items[$pipa->id]['satuan']);
        $this->assertNull($items[$baut->id]['satuan']);
    }

    /**
     * Baris batangan dan baris biasa dalam satu keranjang tidak saling
     * mengganggu: masing-masing dikonversi dengan aturannya sendiri.
     */
    public function test_baris_batangan_dan_baris_biasa_berdampingan(): void
    {
        [$keranjang, $pipa, $baut] = $this->keranjangCampuran();

        $keranjang->qty[$baut->id] = 10;
        $keranjang->unit_price_raw[$baut->id] = '2500';
        $keranjang->formatToRupiah($baut->id);

        $keranjang->qty[$pipa->id] = 2;
        $keranjang->unit_price_raw[$pipa->id] = '175000';
        $keranjang->formatToRupiah($pipa->id);

        // Pipa: 2 batang = 1.200 cm. Baut: 10 pcs, tanpa konversi.
        $this->assertSame(1200.0, $keranjang->totalSatuanDasar($pipa->id));
        $this->assertSame(10.0, $keranjang->totalSatuanDasar($baut->id));
        $this->assertSame(350000.0, $keranjang->subtotals[$pipa->id]);
        $this->assertSame(25000.0, $keranjang->subtotals[$baut->id]);
        $this->assertSame(375000.0, $keranjang->totalharga);
    }

    /**
     * Keranjang bahan keluar dimuat ulang dari sesi: baris batangan harus tetap
     * punya panjang standar dan satuannya, dan angka yang muncul di layar harus
     * angka yang tadi diketik - bukan hasil konversinya.
     */
    public function test_keranjang_keluar_pulih_utuh_dari_sesi(): void
    {
        $pipa = $this->bahan('Pipa Galvanis 1"', 'Batang', self::PANJANG);
        $baut = $this->bahan('Baut M8', 'Pcs', null);

        session()->put('cartItems', [
            ['id' => $pipa->id, 'qty' => 1200, 'qty_input' => 2, 'satuan_input' => 'batang', 'details' => [], 'sub_total' => 350000],
            ['id' => $baut->id, 'qty' => 10, 'qty_input' => 10, 'satuan_input' => null, 'details' => [], 'sub_total' => 25000],
        ]);

        $keranjang = new BahanKeluarCart();
        $keranjang->mount();

        $this->assertSame(self::PANJANG, $keranjang->panjangStandarUntuk($pipa->id));
        $this->assertSame('batang', $keranjang->satuanUntuk($pipa->id));
        $this->assertSame(2, $keranjang->qty[$pipa->id], 'yang tampil angka yang diketik, bukan 1.200');
        $this->assertSame(1200.0, $keranjang->qtyDasar($pipa->id), 'konversinya tetap menghasilkan angka semula');

        $this->assertNull($keranjang->panjangStandarUntuk($baut->id));
        $this->assertSame('cm', $keranjang->satuanUntuk($baut->id));
        $this->assertSame(10, $keranjang->qty[$baut->id]);
        $this->assertSame(10.0, $keranjang->qtyDasar($baut->id));
    }

    /**
     * Sesi dari versi lama belum punya `qty_input`/`satuan_input`. Angkanya
     * dipakai apa adanya, sama seperti perilaku sebelum fitur ini ada.
     */
    public function test_sesi_versi_lama_tetap_terbaca(): void
    {
        $baut = $this->bahan('Baut M8', 'Pcs', null);

        session()->put('cartItems', [
            ['id' => $baut->id, 'qty' => 7, 'details' => [], 'sub_total' => 17500],
        ]);

        $keranjang = new BahanKeluarCart();
        $keranjang->mount();

        $this->assertSame(7, $keranjang->qty[$baut->id]);
        $this->assertSame('cm', $keranjang->satuanUntuk($baut->id));
        $this->assertSame(7.0, $keranjang->qtyDasar($baut->id));
    }

    /**
     * Bahan yang sudah dihapus tidak boleh membuat halaman mati. Sebelumnya
     * baris ini memanggil `Bahan::find(...)->nama_bahan` tanpa penjagaan.
     */
    public function test_bahan_terhapus_tidak_bikin_error(): void
    {
        session()->put('cartItems', [
            ['id' => 999999, 'qty' => 5, 'qty_input' => 5, 'satuan_input' => null, 'details' => [], 'sub_total' => 0],
        ]);

        $keranjang = new BahanKeluarCart();
        $keranjang->mount();

        $this->assertNull($keranjang->panjangStandarUntuk(999999));
        $this->assertSame(5.0, $keranjang->qtyDasar(999999));
    }
}
