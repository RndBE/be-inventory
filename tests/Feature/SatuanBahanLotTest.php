<?php

namespace Tests\Feature;

use App\Helpers\SatuanBahanHelper;
use App\Models\Bahan;
use App\Models\PurchaseDetail;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Konversi satuan saat lot stok dicatat.
 *
 * Test ini memakai SQLite in-memory dengan tabel minimal yang dibuat sendiri,
 * bukan trait RefreshDatabase. Alasannya: phpunit.xml tidak mengunci koneksi
 * database, jadi RefreshDatabase akan mengosongkan database yang tertulis di
 * .env. Dengan koneksi sendiri, test ini tidak pernah menyentuh database itu.
 *
 * Yang diuji hanya PurchaseDetail::catatLot dan pembacaan lotnya, karena di
 * situlah satu-satunya tempat angka satuan dokumen jadi angka satuan ledger.
 */
class SatuanBahanLotTest extends TestCase
{
    private const PANJANG = 600;

    private const HARGA_PER_BATANG = 175000;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.satuan_uji' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'database.default' => 'satuan_uji',
        ]);

        DB::purge('satuan_uji');

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

    private function pipa(?int $panjang = self::PANJANG): Bahan
    {
        $unit = Unit::create(['nama' => 'Batang']);

        return Bahan::create([
            'nama_bahan' => 'Pipa Uji',
            'unit_id' => $unit->id,
            'panjang_standar' => $panjang,
        ]);
    }

    public function test_lot_dari_satuan_batang_tersimpan_dalam_cm(): void
    {
        $pipa = $this->pipa();

        $lot = PurchaseDetail::catatLot([
            'purchase_id' => 1,
            'bahan_id' => $pipa->id,
            'qty' => 5,
            'unit_price' => self::HARGA_PER_BATANG,
            'sub_total' => 875000,
        ], SatuanBahanHelper::SATUAN_BATANG);

        $lot->refresh();

        $this->assertEquals(3000, $lot->qty);
        $this->assertEquals(3000, $lot->sisa);
        $this->assertEquals(291.6667, $lot->unit_price);
        $this->assertEquals(875000, $lot->sub_total);
        $this->assertEquals(self::PANJANG, $lot->panjang_standar);
    }

    public function test_lot_potongan_dari_satuan_cm_tidak_dikali(): void
    {
        $pipa = $this->pipa();

        $lot = PurchaseDetail::catatLot([
            'purchase_id' => 1,
            'bahan_id' => $pipa->id,
            'qty' => 250,
            'unit_price' => 291.6667,
            'sub_total' => 72916.68,
        ], SatuanBahanHelper::SATUAN_DASAR);

        $lot->refresh();

        $this->assertEquals(250, $lot->qty);
        $this->assertEquals(291.6667, $lot->unit_price);
        $this->assertEquals(self::PANJANG, $lot->panjang_standar);
        $this->assertFalse(SatuanBahanHelper::kelipatanBatang($lot->qty, $lot->panjangStandarEfektif()));
    }

    /**
     * Regresi: klien API lama mengirim satuan 'batang' tanpa tahu apa-apa soal
     * fitur ini, dan bahan non-batangan tidak boleh ikut terkonversi.
     */
    public function test_bahan_biasa_tidak_terpengaruh_walau_satuan_batang(): void
    {
        $bahan = $this->pipa(null);

        $lot = PurchaseDetail::catatLot([
            'purchase_id' => 1,
            'bahan_id' => $bahan->id,
            'qty' => 5,
            'unit_price' => 175000,
            'sub_total' => 875000,
        ], SatuanBahanHelper::SATUAN_BATANG);

        $lot->refresh();

        $this->assertEquals(5, $lot->qty);
        $this->assertEquals(5, $lot->sisa);
        $this->assertEquals(175000, $lot->unit_price);
        $this->assertNull($lot->panjang_standar);
    }

    public function test_subtotal_dihitung_dari_angka_input_kalau_tidak_dikirim(): void
    {
        $pipa = $this->pipa();

        $lot = PurchaseDetail::catatLot([
            'purchase_id' => 1,
            'bahan_id' => $pipa->id,
            'qty' => 5,
            'unit_price' => self::HARGA_PER_BATANG,
        ], SatuanBahanHelper::SATUAN_BATANG);

        $this->assertEquals(875000, $lot->refresh()->sub_total);
    }

    public function test_menerima_model_bahan_langsung(): void
    {
        $pipa = $this->pipa();

        $lot = PurchaseDetail::catatLot([
            'purchase_id' => 1,
            'bahan_id' => $pipa,
            'qty' => 2,
            'unit_price' => self::HARGA_PER_BATANG,
        ], SatuanBahanHelper::SATUAN_BATANG);

        $this->assertEquals($pipa->id, $lot->bahan_id);
        $this->assertEquals(1200, $lot->refresh()->qty);
    }

    /**
     * Inti dari salinan panjang per lot: mengedit master bahan tidak boleh
     * mengubah arti angka cm yang sudah tercatat.
     */
    public function test_panjang_lot_dibekukan_saat_master_bahan_diubah(): void
    {
        $pipa = $this->pipa();

        $lot = PurchaseDetail::catatLot([
            'purchase_id' => 1,
            'bahan_id' => $pipa->id,
            'qty' => 5,
            'unit_price' => self::HARGA_PER_BATANG,
        ], SatuanBahanHelper::SATUAN_BATANG);

        $pipa->update(['panjang_standar' => 400]);
        $lot->refresh()->load('dataBahan');

        $this->assertSame(self::PANJANG, $lot->panjangStandarEfektif());
        $this->assertSame('5 Batang', $lot->formatSisa());
    }

    /**
     * Lot yang dibuat sebelum kolom salinan ada jatuh ke nilai master bahan.
     */
    public function test_lot_lama_tanpa_salinan_panjang_membaca_master(): void
    {
        $pipa = $this->pipa();

        $lot = PurchaseDetail::create([
            'purchase_id' => 1,
            'bahan_id' => $pipa->id,
            'panjang_standar' => null,
            'qty' => 3000,
            'sisa' => 3000,
            'unit_price' => 291.6667,
            'sub_total' => 875000,
        ]);

        $this->assertSame(self::PANJANG, $lot->panjangStandarEfektif());
    }

    public function test_sisa_ditampilkan_sebagai_batang_plus_cm(): void
    {
        $pipa = $this->pipa();

        $lot = PurchaseDetail::catatLot([
            'purchase_id' => 1,
            'bahan_id' => $pipa->id,
            'qty' => 5,
            'unit_price' => self::HARGA_PER_BATANG,
        ], SatuanBahanHelper::SATUAN_BATANG);

        $lot->sisa = 3000 - 40;
        $lot->save();

        $this->assertSame('4 Batang + 560 cm', $lot->fresh()->formatSisa());
    }

    public function test_nilai_pengambilan_sebagian_dan_harga_per_batang(): void
    {
        $pipa = $this->pipa();

        $lot = PurchaseDetail::catatLot([
            'purchase_id' => 1,
            'bahan_id' => $pipa->id,
            'qty' => 5,
            'unit_price' => self::HARGA_PER_BATANG,
        ], SatuanBahanHelper::SATUAN_BATANG);

        $lot->refresh();

        $this->assertSame(11666.67, $lot->nilaiUntuk(40));
        $this->assertSame(175000.02, $lot->hargaPerBatang());
    }

    /**
     * Jebakan yang belum kena karena tidak ada pemanggil yang memakainya:
     * `sisa` yang dikirim eksplisit tidak ikut dikonversi, sementara `qty`
     * dikonversi. Kalau nanti ada pemanggil yang mengirim sisa dalam batang,
     * lotnya langsung tidak konsisten dengan qty-nya sendiri.
     */
    public function test_sisa_eksplisit_tidak_ikut_dikonversi(): void
    {
        $pipa = $this->pipa();

        $lot = PurchaseDetail::catatLot([
            'purchase_id' => 1,
            'bahan_id' => $pipa->id,
            'qty' => 5,
            'sisa' => 5,
            'unit_price' => self::HARGA_PER_BATANG,
        ], SatuanBahanHelper::SATUAN_BATANG);

        $lot->refresh();

        $this->assertEquals(3000, $lot->qty);
        $this->assertEquals(5, $lot->sisa);
    }

    /**
     * Lot yang dibuat sebelum fitur ini ada menyimpan angka dalam batang.
     * Saat panjang standar akhirnya diisi, angkanya harus ikut dikonversi -
     * kalau tidak, "2 batang" akan terbaca "2 cm".
     */
    public function test_lot_lama_dikonversi_saat_panjang_standar_diisi(): void
    {
        $pipa = $this->pipa(null);

        $lot = PurchaseDetail::create([
            'purchase_id' => 1,
            'bahan_id' => $pipa->id,
            'panjang_standar' => null,
            'qty' => 2,
            'sisa' => 0,
            'unit_price' => 1441441.44,
            'sub_total' => 2882882.88,
        ]);

        $jumlah = PurchaseDetail::konversiLotLama($pipa, self::PANJANG);
        $lot->refresh();

        $this->assertSame(1, $jumlah);
        $this->assertEquals(1200, $lot->qty);
        $this->assertEquals(2402.4024, $lot->unit_price);
        $this->assertEquals(self::PANJANG, $lot->panjang_standar);
        // Nilai pembukuan tidak boleh bergeser sedikit pun.
        $this->assertEquals(2882882.88, $lot->sub_total);
    }

    /**
     * Lot yang sudah punya salinan panjang berarti sudah dicatat dalam cm.
     * Mengonversinya lagi akan menggandakan angkanya.
     */
    public function test_lot_yang_sudah_terkonversi_tidak_disentuh_lagi(): void
    {
        $pipa = $this->pipa();

        $lot = PurchaseDetail::catatLot([
            'purchase_id' => 1,
            'bahan_id' => $pipa->id,
            'qty' => 5,
            'unit_price' => self::HARGA_PER_BATANG,
        ], SatuanBahanHelper::SATUAN_BATANG);

        $jumlah = PurchaseDetail::konversiLotLama($pipa, self::PANJANG);
        $lot->refresh();

        $this->assertSame(0, $jumlah);
        $this->assertEquals(3000, $lot->qty);
        $this->assertEquals(291.6667, $lot->unit_price);
    }

    /**
     * Konversi dijalankan ulang tidak boleh mengubah apa pun lagi.
     */
    public function test_konversi_aman_dijalankan_dua_kali(): void
    {
        $pipa = $this->pipa(null);

        $lot = PurchaseDetail::create([
            'purchase_id' => 1,
            'bahan_id' => $pipa->id,
            'panjang_standar' => null,
            'qty' => 2,
            'sisa' => 1,
            'unit_price' => 120000,
            'sub_total' => 240000,
        ]);

        PurchaseDetail::konversiLotLama($pipa, self::PANJANG);
        $this->assertSame(0, PurchaseDetail::konversiLotLama($pipa, self::PANJANG));

        $lot->refresh();
        $this->assertEquals(1200, $lot->qty);
        $this->assertEquals(self::PANJANG, $lot->sisa);
        $this->assertEquals(200, $lot->unit_price);
    }

    /**
     * Panjang standar yang salah ketik diperbaiki: 10 batang tetap 10 batang,
     * angka cm-nya yang menyesuaikan.
     */
    public function test_setel_ulang_panjang_mempertahankan_jumlah_batang(): void
    {
        $pipa = $this->pipa();

        $lot = PurchaseDetail::catatLot([
            'purchase_id' => 1,
            'bahan_id' => $pipa->id,
            'qty' => 10,
            'unit_price' => self::HARGA_PER_BATANG,
            'sub_total' => 1750000,
        ], SatuanBahanHelper::SATUAN_BATANG);

        $jumlah = PurchaseDetail::setelUlangPanjangLot($pipa, self::PANJANG, 400);
        $lot->refresh();

        $this->assertSame(1, $jumlah);
        $this->assertEquals(4000, $lot->qty, '10 batang x 400 cm');
        $this->assertEquals(4000, $lot->sisa);
        $this->assertEquals(400, $lot->panjang_standar);
        // Harga per cm dibulatkan empat desimal, jadi hasilnya 437,5001 dan
        // bukan 437,5 persis - selisih Rp 0,0001 per cm yang ikut terbawa dari
        // pembulatan harga lama.
        $this->assertEqualsWithDelta(437.5, (float) $lot->unit_price, 0.001, 'Harga per batang harus tetap Rp 175.000.');
        $this->assertEquals(1750000, $lot->sub_total, 'Angka pembukuan tidak ikut berubah.');
        $this->assertEqualsWithDelta(1750000, $lot->sisa * $lot->unit_price, 1, 'Nilai rupiah sisa stok tidak boleh bergeser.');
    }

    /**
     * Sisa yang tinggal potongan ikut dihitung ulang dengan rasio yang sama,
     * jadi porsinya terhadap satu batang tetap.
     */
    public function test_setel_ulang_panjang_ikut_menggeser_sisa_potongan(): void
    {
        $pipa = $this->pipa();

        $lot = PurchaseDetail::create([
            'purchase_id' => 1,
            'bahan_id' => $pipa->id,
            'panjang_standar' => self::PANJANG,
            'qty' => 6000,
            'sisa' => 900,
            'unit_price' => 291.6667,
            'sub_total' => 1750000,
        ]);

        PurchaseDetail::setelUlangPanjangLot($pipa, self::PANJANG, 300);
        $lot->refresh();

        $this->assertEquals(3000, $lot->qty);
        $this->assertEquals(450, $lot->sisa, 'Sisa 1,5 batang tetap 1,5 batang.');
        $this->assertEqualsWithDelta(583.3334, (float) $lot->unit_price, 0.001);
    }

    /**
     * Lot yang dibekukan pada ukuran lain memang pernah dibeli dalam ukuran itu.
     * Angkanya benar apa adanya, jadi tidak boleh ikut digeser.
     */
    public function test_setel_ulang_panjang_tidak_menyentuh_lot_ukuran_lain(): void
    {
        $pipa = $this->pipa();

        $lotLain = PurchaseDetail::create([
            'purchase_id' => 1,
            'bahan_id' => $pipa->id,
            'panjang_standar' => 300,
            'qty' => 900,
            'sisa' => 900,
            'unit_price' => 500,
            'sub_total' => 450000,
        ]);

        $this->assertSame(0, PurchaseDetail::setelUlangPanjangLot($pipa, self::PANJANG, 400));

        $lotLain->refresh();
        $this->assertEquals(900, $lotLain->qty);
        $this->assertEquals(900, $lotLain->sisa);
        $this->assertEquals(300, $lotLain->panjang_standar);
        $this->assertEquals(500, $lotLain->unit_price);
    }

    /**
     * Panjang yang tidak berubah tidak boleh menyentuh apa pun - form selalu
     * mengirim ulang kolomnya walau isinya sama.
     */
    public function test_setel_ulang_panjang_yang_sama_tidak_mengubah_apa_pun(): void
    {
        $pipa = $this->pipa();

        $lot = PurchaseDetail::create([
            'purchase_id' => 1,
            'bahan_id' => $pipa->id,
            'panjang_standar' => self::PANJANG,
            'qty' => 6000,
            'sisa' => 6000,
            'unit_price' => 291.6667,
            'sub_total' => 1750000,
        ]);

        $this->assertSame(0, PurchaseDetail::setelUlangPanjangLot($pipa, self::PANJANG, self::PANJANG));

        $lot->refresh();
        $this->assertEquals(6000, $lot->qty);
        $this->assertEquals(291.6667, $lot->unit_price);
    }
}
