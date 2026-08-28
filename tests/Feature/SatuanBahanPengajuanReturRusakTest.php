<?php

namespace Tests\Feature;

use App\Helpers\SatuanBahanHelper;
use App\Livewire\BahanKeluarCart;
use App\Livewire\EditBahanProjekRndCart;
use App\Models\Bahan;
use App\Models\BahanReturDetails;
use App\Models\BahanRusakDetails;
use App\Models\PurchaseDetail;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tiga alur yang belum tersentuh: pengajuan bahan keluar, pengembalian, rusak.
 *
 * Pipa 600 cm dengan dua lot harga berbeda, supaya alokasi lintas lot ikut
 * teruji — bukan cuma perkalian satuannya.
 *
 * Tabelnya dibuat sendiri di SQLite in-memory. Database di .env tidak
 * tersentuh.
 */
class SatuanBahanPengajuanReturRusakTest extends TestCase
{
    private const PANJANG = 600;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.pengajuan_uji' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'database.default' => 'pengajuan_uji',
        ]);

        DB::purge('pengajuan_uji');

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

        Schema::create('purchases', function ($tabel) {
            $tabel->id();
            $tabel->string('kode_transaksi')->nullable();
            $tabel->dateTime('tgl_masuk')->nullable();
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
            $tabel->unsignedBigInteger('produk_jadis_id')->nullable();
            $tabel->string('serial_number')->nullable();
            $tabel->decimal('qty', 15, 2)->default(0);
            $tabel->decimal('qty_input', 15, 2)->nullable();
            $tabel->string('satuan_input', 20)->nullable();
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
     * Dua lot dengan harga berbeda, urut tanggal masuk.
     */
    private function duaLotPipa(Bahan $pipa): void
    {
        foreach ([['P-001', '2026-01-01', 800, 300], ['P-002', '2026-02-01', 400, 400]] as [$kode, $tgl, $cm, $harga]) {
            $purchaseId = DB::table('purchases')->insertGetId([
                'kode_transaksi' => $kode,
                'tgl_masuk' => $tgl . ' 08:00:00',
            ]);

            PurchaseDetail::create([
                'purchase_id' => $purchaseId,
                'bahan_id' => $pipa->id,
                'panjang_standar' => self::PANJANG,
                'qty' => $cm,
                'sisa' => $cm,
                'unit_price' => $harga,
                'sub_total' => $cm * $harga,
            ]);
        }
    }

    private function keranjangKeluar(Bahan $bahan): BahanKeluarCart
    {
        $keranjang = new BahanKeluarCart();
        $keranjang->mount();
        $keranjang->addToCart((object) ['id' => $bahan->id, 'nama_bahan' => $bahan->nama_bahan]);

        return $keranjang;
    }

    // =====================================================================
    // 1. Pengajuan bahan keluar
    // =====================================================================

    /**
     * Pengajuan 2 batang dari stok yang tersebar di dua lot berbeda harga.
     */
    public function test_pengajuan_batang_dialokasikan_lintas_lot(): void
    {
        $pipa = $this->bahan('Pipa Galvanis 1"', 'Batang', self::PANJANG);
        $this->duaLotPipa($pipa);

        $keranjang = $this->keranjangKeluar($pipa);
        $this->assertSame('batang', $keranjang->satuanUntuk($pipa->id));

        $keranjang->qty[$pipa->id] = 2;
        $keranjang->updateQuantity($pipa->id);

        $this->assertEquals(2, $keranjang->qty[$pipa->id], 'angka yang diketik tetap 2');
        $this->assertSame(1200.0, $keranjang->qtyDasar($pipa->id));

        // 800 cm dari lot pertama, 400 cm dari lot kedua.
        $rincian = $keranjang->details[$pipa->id];
        $this->assertCount(2, $rincian);
        $this->assertSame(800.0, (float) $rincian[0]['qty']);
        $this->assertSame(300.0, (float) $rincian[0]['unit_price']);
        $this->assertSame(400.0, (float) $rincian[1]['qty']);
        $this->assertSame(400.0, (float) $rincian[1]['unit_price']);

        // 800 x 300 + 400 x 400
        $this->assertSame(400000.0, (float) $keranjang->subtotals[$pipa->id]);
    }

    /**
     * Batas atas dihitung di satuan dasar. Stok 1200 cm cuma cukup 2 batang,
     * jadi permintaan 3 batang dipotong - bukan diterima lalu gagal di gudang.
     */
    public function test_pengajuan_melebihi_stok_dipotong_ke_batang_utuh(): void
    {
        $pipa = $this->bahan('Pipa Galvanis 1"', 'Batang', self::PANJANG);
        $this->duaLotPipa($pipa);

        $keranjang = $this->keranjangKeluar($pipa);
        $keranjang->qty[$pipa->id] = 3;
        $keranjang->updateQuantity($pipa->id);

        $this->assertEquals(2, $keranjang->qty[$pipa->id]);
        $this->assertSame(1200.0, $keranjang->qtyDasar($pipa->id));
    }

    /**
     * Sisa yang bukan kelipatan batang tetap bisa diminta lewat satuan cm.
     */
    public function test_pengajuan_dalam_cm_untuk_potongan(): void
    {
        $pipa = $this->bahan('Pipa Galvanis 1"', 'Batang', self::PANJANG);
        $this->duaLotPipa($pipa);

        $keranjang = $this->keranjangKeluar($pipa);
        $keranjang->satuan[$pipa->id] = SatuanBahanHelper::SATUAN_DASAR;

        $keranjang->qty[$pipa->id] = 950;
        $keranjang->updateQuantity($pipa->id);
        $this->assertEquals(950, $keranjang->qty[$pipa->id]);
        $this->assertSame(950.0, $keranjang->qtyDasar($pipa->id));

        // Melebihi stok: dipotong ke 1200 cm, bukan ke jumlah batang.
        $keranjang->qty[$pipa->id] = 1500;
        $keranjang->updateQuantity($pipa->id);
        $this->assertEquals(1200, $keranjang->qty[$pipa->id]);
    }

    /**
     * Payload pengajuan: `qty` satuan dasar untuk dipotong dari stok, plus
     * jejak angka yang diketik.
     */
    public function test_payload_pengajuan_membawa_jejak_satuan(): void
    {
        $pipa = $this->bahan('Pipa Galvanis 1"', 'Batang', self::PANJANG);
        $this->duaLotPipa($pipa);

        $keranjang = $this->keranjangKeluar($pipa);
        $keranjang->qty[$pipa->id] = 2;
        $keranjang->updateQuantity($pipa->id);

        $item = collect($keranjang->getCartItemsForStorage())->firstWhere('id', $pipa->id);

        $this->assertSame(1200.0, $item['qty']);
        $this->assertEquals(2, $item['qty_input']);
        $this->assertSame('batang', $item['satuan_input']);
    }

    /**
     * Bahan biasa lewat pengajuan yang sama: tidak ada yang berubah.
     */
    public function test_pengajuan_bahan_biasa_tidak_berubah(): void
    {
        $baut = $this->bahan('Baut M8', 'Pcs', null);
        $purchaseId = DB::table('purchases')->insertGetId([
            'kode_transaksi' => 'B-001',
            'tgl_masuk' => '2026-01-01 08:00:00',
        ]);
        PurchaseDetail::create([
            'purchase_id' => $purchaseId,
            'bahan_id' => $baut->id,
            'qty' => 50,
            'sisa' => 50,
            'unit_price' => 2500,
            'sub_total' => 125000,
        ]);

        $keranjang = $this->keranjangKeluar($baut);
        $keranjang->qty[$baut->id] = 60;
        $keranjang->updateQuantity($baut->id);

        $this->assertEquals(50, $keranjang->qty[$baut->id], 'dipotong ke stok, tanpa konversi');

        $item = collect($keranjang->getCartItemsForStorage())->firstWhere('id', $baut->id);
        $this->assertSame(50.0, $item['qty']);
        $this->assertNull($item['satuan_input']);
    }

    // =====================================================================
    // 2. Pengembalian (retur)
    // =====================================================================

    /**
     * Keranjang edit projek RnD dengan satu pengambilan 600 cm dari lot @300.
     */
    private function keranjangEditDenganPengambilan(Bahan $pipa): EditBahanProjekRndCart
    {
        $keranjang = new EditBahanProjekRndCart();
        $keranjang->panjangStandarItem = [$pipa->id => self::PANJANG];
        $keranjang->projekRndDetails = [[
            'bahan_id' => $pipa->id,
            'produk_id' => null,
            'qty' => 600,
            'jml_bahan' => 0,
            'sub_total' => 180000,
            'details' => [
                ['kode_transaksi' => 'P-001', 'qty' => 600, 'unit_price' => 300],
            ],
        ]];

        return $keranjang;
    }

    /**
     * Angka retur dibatasi jumlah yang tadi diambil, dan batasnya dalam cm.
     */
    public function test_retur_dibatasi_jumlah_pengambilan(): void
    {
        $pipa = $this->bahan('Pipa Galvanis 1"', 'Batang', self::PANJANG);
        $keranjang = $this->keranjangEditDenganPengambilan($pipa);
        $keranjang->bahanRetur = [
            ['bahan_id' => $pipa->id, 'produk_id' => null, 'unit_price' => 300, 'qty' => 0],
        ];

        $keranjang->updateReturQty($pipa->id, 300, 700);
        $this->assertEquals(600, $keranjang->bahanRetur[0]['qty'], 'dipotong ke 600 cm');

        $keranjang->updateReturQty($pipa->id, 300, 150);
        $this->assertEquals(150, $keranjang->bahanRetur[0]['qty']);

        $keranjang->updateReturQty($pipa->id, 300, -5);
        $this->assertEquals(0, $keranjang->bahanRetur[0]['qty'], 'negatif jadi nol');
    }

    /**
     * Payload retur, lalu lot barunya di gudang.
     */
    public function test_retur_potongan_masuk_jadi_lot_baru(): void
    {
        $pipa = $this->bahan('Pipa Galvanis 1"', 'Batang', self::PANJANG);
        $keranjang = $this->keranjangEditDenganPengambilan($pipa);
        $keranjang->bahanRetur = [
            ['bahan_id' => $pipa->id, 'produk_id' => null, 'unit_price' => 300, 'qty' => 0],
        ];

        $keranjang->updateReturQty($pipa->id, 300, 150);
        $payload = $keranjang->getCartItemsForBahanRetur();

        $this->assertSame(45000.0, (float) $payload[0]['sub_total']);

        $retur = BahanReturDetails::catatRetur([
            'bahan_retur_id' => 1,
            'bahan_id' => $payload[0]['bahan_id'],
            'qty' => $payload[0]['qty'],
            'unit_price' => $payload[0]['unit_price'],
            'sub_total' => $payload[0]['sub_total'],
        ]);

        $this->assertSame('cm', $retur->satuan_input);
        $this->assertEquals(150, $retur->qty_input);

        $lot = PurchaseDetail::catatLot([
            'purchase_id' => 99,
            'bahan_id' => $retur->bahan_id,
            'qty' => $retur->qty,
            'unit_price' => $retur->unit_price,
            'sub_total' => $retur->sub_total,
        ], SatuanBahanHelper::SATUAN_DASAR)->refresh();

        $this->assertEquals(150, $lot->sisa);
        $this->assertEquals(self::PANJANG, $lot->panjang_standar);
        $this->assertSame('150 cm', $lot->formatSisa());
    }

    // =====================================================================
    // 3. Bahan rusak
    // =====================================================================

    public function test_rusak_ditandai_lalu_qty_dibatasi_pengambilan(): void
    {
        $pipa = $this->bahan('Pipa Galvanis 1"', 'Batang', self::PANJANG);
        $keranjang = $this->keranjangEditDenganPengambilan($pipa);

        $keranjang->decreaseQuantityPerPrice('bahan', $pipa->id, 300);
        $this->assertCount(1, $keranjang->bahanRusak);
        $this->assertSame($pipa->id, $keranjang->bahanRusak[0]['bahan_id']);

        $keranjang->updateRusakQty($pipa->id, 300, 700);
        $this->assertEquals(600, $keranjang->bahanRusak[0]['qty'], 'dipotong ke 600 cm');

        $keranjang->updateRusakQty($pipa->id, 300, 50);
        $this->assertEquals(50, $keranjang->bahanRusak[0]['qty']);
    }

    /**
     * Satu bahan tidak boleh masuk daftar retur dan rusak sekaligus.
     */
    public function test_bahan_yang_sudah_diretur_tidak_bisa_ditandai_rusak(): void
    {
        $pipa = $this->bahan('Pipa Galvanis 1"', 'Batang', self::PANJANG);
        $keranjang = $this->keranjangEditDenganPengambilan($pipa);
        $keranjang->bahanRetur = [
            ['bahan_id' => $pipa->id, 'produk_id' => null, 'unit_price' => 300, 'qty' => 150],
        ];

        $keranjang->decreaseQuantityPerPrice('bahan', $pipa->id, 300);

        $this->assertSame([], $keranjang->bahanRusak);
    }

    /**
     * Bahan rusak tidak mengembalikan apa pun ke stok.
     */
    public function test_rusak_tidak_mengubah_stok(): void
    {
        $pipa = $this->bahan('Pipa Galvanis 1"', 'Batang', self::PANJANG);
        $this->duaLotPipa($pipa);
        $stokAwal = (float) PurchaseDetail::where('bahan_id', $pipa->id)->sum('sisa');

        $keranjang = $this->keranjangEditDenganPengambilan($pipa);
        $keranjang->decreaseQuantityPerPrice('bahan', $pipa->id, 300);
        $keranjang->updateRusakQty($pipa->id, 300, 50);

        $payload = $keranjang->getCartItemsForBahanRusak();
        $rusak = BahanRusakDetails::create([
            'bahan_rusak_id' => 1,
            'bahan_id' => $payload[0]['bahan_id'],
            'qty' => $payload[0]['qty'],
            'sisa' => $payload[0]['qty'],
            'unit_price' => $payload[0]['unit_price'],
            'sub_total' => $payload[0]['sub_total'],
        ]);

        $this->assertEquals(50, $rusak->qty);
        $this->assertEquals(15000, $rusak->sub_total);
        $this->assertSame($stokAwal, (float) PurchaseDetail::where('bahan_id', $pipa->id)->sum('sisa'));

        // Kolom jejak satuan ada di tabelnya tapi tidak ada kode yang mengisinya.
        $this->assertNull($rusak->satuan_input);
    }

    // =====================================================================
    // Cacat yang ditemukan, dicatat apa adanya
    // =====================================================================

    /**
     * MENDOKUMENTASIKAN CACAT, bukan perilaku yang benar.
     *
     * Retur dan rusak masing-masing dibatasi jumlah pengambilan, tapi tidak
     * saling tahu. Dari pengambilan 600 cm, retur 600 cm dan rusak 600 cm
     * dua-duanya diterima - jadi 1.200 cm dipertanggungjawabkan dari barang
     * yang cuma 600 cm.
     *
     * Kalau nanti dibetulkan, test ini yang gagal lebih dulu. Ganti harapannya,
     * jangan hapus testnya.
     */
    public function test_cacat_retur_dan_rusak_tidak_saling_membatasi(): void
    {
        $pipa = $this->bahan('Pipa Galvanis 1"', 'Batang', self::PANJANG);
        $keranjang = $this->keranjangEditDenganPengambilan($pipa);

        // Rusak ditandai lebih dulu, lalu returnya diisi langsung ke array
        // supaya penjagaan "sudah diretur" tidak ikut menghalangi.
        $keranjang->decreaseQuantityPerPrice('bahan', $pipa->id, 300);
        $keranjang->updateRusakQty($pipa->id, 300, 600);

        $keranjang->bahanRetur = [
            ['bahan_id' => $pipa->id, 'produk_id' => null, 'unit_price' => 300, 'qty' => 0],
        ];
        $keranjang->updateReturQty($pipa->id, 300, 600);

        $totalDipertanggungjawabkan = $keranjang->bahanRusak[0]['qty'] + $keranjang->bahanRetur[0]['qty'];

        $this->assertSame(600.0, (float) $keranjang->bahanRusak[0]['qty']);
        $this->assertSame(600.0, (float) $keranjang->bahanRetur[0]['qty']);
        $this->assertSame(1200.0, (float) $totalDipertanggungjawabkan, 'dua kali lipat dari yang diambil');
    }

    // =====================================================================
    // Harga pada retur dan rusak
    // =====================================================================

    /**
     * Harga tidak pernah diketik ulang saat retur atau rusak.
     *
     * Pipa Rp 300.000 per batang 600 cm tersimpan Rp 500 per cm. Retur 300 cm
     * jadi Rp 150.000 dengan sendirinya, karena harganya diwarisi dari lot
     * pengambilannya dan cuma dikalikan panjang yang dikembalikan.
     */
    public function test_harga_retur_dan_rusak_ikut_panjangnya(): void
    {
        $hargaPerCm = SatuanBahanHelper::keHargaSatuanDasar(300000, SatuanBahanHelper::SATUAN_BATANG, self::PANJANG);
        $this->assertSame(500.0, $hargaPerCm);

        $pipa = $this->bahan('Pipa Galvanis 1"', 'Batang', self::PANJANG);

        $keranjang = new EditBahanProjekRndCart();
        $keranjang->panjangStandarItem = [$pipa->id => self::PANJANG];
        $keranjang->projekRndDetails = [[
            'bahan_id' => $pipa->id,
            'produk_id' => null,
            'qty' => 600,
            'jml_bahan' => 0,
            'sub_total' => 300000,
            'details' => [
                ['kode_transaksi' => 'P-001', 'qty' => 600, 'unit_price' => $hargaPerCm],
            ],
        ]];

        // Retur setengah batang.
        $keranjang->bahanRetur = [
            ['bahan_id' => $pipa->id, 'produk_id' => null, 'unit_price' => $hargaPerCm, 'qty' => 0],
        ];
        $keranjang->updateReturQty($pipa->id, $hargaPerCm, 300);
        $payloadRetur = $keranjang->getCartItemsForBahanRetur();

        $this->assertEquals(300, $payloadRetur[0]['qty']);
        $this->assertEquals(500, $payloadRetur[0]['unit_price'], 'yang tersimpan harga per cm');
        $this->assertSame(150000.0, (float) $payloadRetur[0]['sub_total'], 'nilainya setengah batang');

        // Rusak 100 cm dari batang yang sama.
        $keranjang->bahanRetur = [];
        $keranjang->decreaseQuantityPerPrice('bahan', $pipa->id, $hargaPerCm);
        $keranjang->updateRusakQty($pipa->id, $hargaPerCm, 100);
        $payloadRusak = $keranjang->getCartItemsForBahanRusak();

        $this->assertEquals(500, $payloadRusak[0]['unit_price']);
        $this->assertSame(50000.0, (float) $payloadRusak[0]['sub_total']);

        // Lot hasil retur juga mewarisi harga per cm yang sama.
        $lot = PurchaseDetail::catatLot([
            'purchase_id' => 99,
            'bahan_id' => $pipa->id,
            'qty' => $payloadRetur[0]['qty'],
            'unit_price' => $payloadRetur[0]['unit_price'],
            'sub_total' => $payloadRetur[0]['sub_total'],
        ], SatuanBahanHelper::SATUAN_DASAR)->refresh();

        $this->assertEquals(500, $lot->unit_price);
        $this->assertEquals(150000, $lot->sub_total);
        $this->assertSame(300000.0, $lot->hargaPerBatang(), 'dinyatakan kembali per batang, utuh');
    }

    /**
     * Harga yang tidak habis dibagi panjang meninggalkan selisih sen.
     *
     * Rp 175.000 per batang 600 cm jadi Rp 291,6667 per cm. Setengah batang
     * bernilai Rp 87.500,01 - bukan Rp 87.500. Selisihnya satu sen, dan itu
     * konsekuensi pembulatan empat desimal yang dipilih di kolom harganya.
     */
    public function test_harga_yang_tidak_habis_dibagi_menyisakan_selisih_sen(): void
    {
        $hargaPerCm = SatuanBahanHelper::keHargaSatuanDasar(175000, SatuanBahanHelper::SATUAN_BATANG, self::PANJANG);
        $this->assertSame(291.6667, $hargaPerCm);

        $this->assertSame(87500.01, SatuanBahanHelper::nilaiSatuanDasar(300, $hargaPerCm));
        $this->assertSame(175000.02, SatuanBahanHelper::nilaiSatuanDasar(self::PANJANG, $hargaPerCm));
    }
}
