<?php

namespace Tests\Unit;

use App\Helpers\SatuanBahanHelper;
use App\Livewire\EditBahanGaransiProjekCart;
use App\Livewire\EditBahanPengambilanBahanCart;
use App\Livewire\EditBahanProdukSampleCart;
use App\Livewire\EditBahanProduksiCart;
use App\Livewire\EditBahanProduksiProdukJadiCart;
use App\Livewire\EditBahanProjekCart;
use App\Livewire\EditBahanProjekRndCart;
use App\Livewire\EditKomponenProjekCart;
use App\Livewire\EditKomponenSampleCart;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Pilihan satuan batang/cm pada baris retur dan bahan rusak.
 *
 * Baris retur dan rusak tidak punya id item sendiri — keduanya array berindeks
 * angka, dan satuan pilihannya disimpan di dalam barisnya. Test ini menjaga
 * kesepakatan itu, dan menjaga supaya sembilan keranjang edit yang memakai
 * trait-nya tidak ada satu pun yang ketinggalan saat aturannya berubah.
 *
 * Tidak ada query database: panjang standar disuntikkan langsung ke memo trait,
 * dan nama unit dipatok lewat subclass.
 */
class SatuanReturRusakTest extends TestCase
{
    private const PIPA = 7;

    private const BIASA = 9;

    private const PANJANG = 600;

    /**
     * Semua keranjang edit yang punya baris retur dan rusak.
     *
     * Kalau ada keranjang baru yang lupa memakai trait-nya, test terakhir di
     * kelas ini yang menangkapnya.
     */
    private const KERANJANG = [
        EditBahanGaransiProjekCart::class,
        EditBahanPengambilanBahanCart::class,
        EditBahanProdukSampleCart::class,
        EditBahanProduksiCart::class,
        EditBahanProduksiProdukJadiCart::class,
        EditBahanProjekCart::class,
        EditBahanProjekRndCart::class,
        EditKomponenProjekCart::class,
        EditKomponenSampleCart::class,
    ];

    /**
     * Keranjang dengan memo panjang standar terisi, tanpa menyentuh database.
     */
    private function keranjang(string $kelas = EditBahanPengambilanBahanCart::class)
    {
        $k = new $kelas();

        $memo = new ReflectionProperty($k, 'memoPanjangStandarBahan');
        $memo->setAccessible(true);
        $memo->setValue($k, [self::PIPA => self::PANJANG, self::BIASA => null]);

        return $k;
    }

    private function panggil($objek, string $metode, ...$argumen)
    {
        $m = new ReflectionMethod($objek, $metode);
        $m->setAccessible(true);

        return $m->invoke($objek, ...$argumen);
    }

    public function test_default_baris_retur_adalah_cm(): void
    {
        $k = $this->keranjang();
        $k->bahanRetur = [['bahan_id' => self::PIPA, 'unit_price' => 291.6667, 'qty' => 40]];

        $this->assertSame(self::PANJANG, $k->panjangStandarBarisRetur(0));
        $this->assertSame(SatuanBahanHelper::SATUAN_DASAR, $k->satuanBarisRetur(0));
        $this->assertSame(40.0, $k->qtyDasarBarisRetur(0), 'angka cm tidak boleh dikonversi');
    }

    public function test_satuan_batang_dikonversi_ke_cm(): void
    {
        $k = $this->keranjang();
        $k->bahanRetur = [[
            'bahan_id' => self::PIPA,
            'unit_price' => 291.6667,
            'satuan' => SatuanBahanHelper::SATUAN_BATANG,
            'qty' => 2,
        ]];

        $this->assertSame(1200.0, $k->qtyDasarBarisRetur(0));
    }

    /**
     * Baris produk setengah jadi tidak punya konsep batang, jadi pilihan
     * satuannya tidak boleh muncul walaupun panjang standarnya kebetulan ada.
     */
    public function test_baris_produk_tidak_dapat_pilihan_satuan(): void
    {
        $k = $this->keranjang();
        $k->bahanRetur = [['produk_id' => self::PIPA, 'unit_price' => 1000, 'qty' => 3]];

        $this->assertNull($k->panjangStandarBarisRetur(0));
        $this->assertSame(3.0, $k->qtyDasarBarisRetur(0));
    }

    public function test_bahan_biasa_tidak_dapat_pilihan_satuan(): void
    {
        $k = $this->keranjang();
        $k->bahanRetur = [['bahan_id' => self::BIASA, 'unit_price' => 5000, 'qty' => 3]];

        $this->assertNull($k->panjangStandarBarisRetur(0));
        $this->assertNull($this->panggil($k, 'satuanTersimpanRetur', 0));
    }

    public function test_ganti_satuan_mengosongkan_qty(): void
    {
        $k = $this->keranjang();
        $k->bahanRetur = [['bahan_id' => self::PIPA, 'unit_price' => 291.6667, 'qty' => 5]];

        $k->updateSatuanRetur(0);

        $this->assertNull($k->bahanRetur[0]['qty']);
    }

    /**
     * Batas atas dalam satuan batang dibulatkan ke bawah: sisa 2040 cm pada
     * batang 600 cm cuma bisa diretur 3 batang, sisanya harus lewat satuan cm.
     */
    public function test_batas_dalam_batang_dibulatkan_ke_bawah(): void
    {
        $k = $this->keranjang();
        $k->bahanRetur = [[
            'bahan_id' => self::PIPA,
            'unit_price' => 291.6667,
            'satuan' => SatuanBahanHelper::SATUAN_BATANG,
            'qty' => 0,
        ]];

        $this->assertSame(3.0, $k->maksInputRetur(0, 2040));

        $k->bahanRetur[0]['satuan'] = SatuanBahanHelper::SATUAN_DASAR;
        $this->assertSame(2040.0, $k->maksInputRetur(0, 2040));
    }

    public function test_qty_melebihi_batas_dipotong_dalam_satuan_input(): void
    {
        $k = $this->keranjang();
        $k->bahanRetur = [[
            'bahan_id' => self::PIPA,
            'unit_price' => 291.6667,
            'satuan' => SatuanBahanHelper::SATUAN_BATANG,
            'qty' => 0,
        ]];

        // 5 batang = 3000 cm, sedangkan batasnya 2040 cm.
        $this->assertSame(3.0, $this->panggil($k, 'batasiQtyRetur', 0, 5, 2040));

        // 2 batang = 1200 cm, masih di bawah batas, jadi diteruskan apa adanya.
        $this->assertSame(2, $this->panggil($k, 'batasiQtyRetur', 0, 2, 2040));
    }

    public function test_index_baris_dicari_dari_id_dan_harga(): void
    {
        $k = $this->keranjang();
        $k->bahanRetur = [
            ['bahan_id' => self::PIPA, 'unit_price' => 291.6667, 'qty' => 1],
            ['bahan_id' => self::PIPA, 'unit_price' => 250.0, 'qty' => 2],
        ];

        $this->assertSame(0, $this->panggil($k, 'indexRetur', self::PIPA, 291.6667));
        $this->assertSame(1, $this->panggil($k, 'indexRetur', self::PIPA, 250.0));
        $this->assertNull($this->panggil($k, 'indexRetur', 99, 250.0));
    }

    /**
     * Harga bisa berubah format setelah bolak-balik lewat Livewire, jadi baris
     * pertama yang id-nya cocok dipakai sebagai cadangan — pilihan satuannya
     * tidak boleh hilang hanya karena 250 vs "250.00".
     */
    public function test_index_jatuh_ke_baris_pertama_kalau_harga_tidak_cocok(): void
    {
        $k = $this->keranjang();
        $k->bahanRetur = [['bahan_id' => self::PIPA, 'unit_price' => 291.6667, 'qty' => 1]];

        $this->assertSame(0, $this->panggil($k, 'indexRetur', self::PIPA, 999.0));
    }

    public function test_baris_rusak_memakai_aturan_yang_sama(): void
    {
        $k = $this->keranjang();
        $k->bahanRusak = [[
            'bahan_id' => self::PIPA,
            'unit_price' => 291.6667,
            'satuan' => SatuanBahanHelper::SATUAN_BATANG,
            'qty' => 1,
        ]];

        $this->assertSame(self::PANJANG, $k->panjangStandarBarisRusak(0));
        $this->assertSame(600.0, $k->qtyDasarBarisRusak(0));
        $this->assertSame(
            SatuanBahanHelper::SATUAN_BATANG,
            $this->panggil($k, 'satuanTersimpanRusak', 0)
        );
    }

    public function test_indeks_yang_tidak_ada_tidak_meledak(): void
    {
        $k = $this->keranjang();
        $k->bahanRetur = [];

        $this->assertNull($k->panjangStandarBarisRetur(3));
        $this->assertSame(SatuanBahanHelper::SATUAN_DASAR, $k->satuanBarisRetur(3));
        $this->assertSame(0.0, $k->qtyDasarBarisRetur(3));
    }

    /**
     * Sembilan keranjang edit harus memakai trait-nya, tanpa kecuali.
     */
    public function test_semua_keranjang_edit_memakai_trait(): void
    {
        foreach (self::KERANJANG as $kelas) {
            $k = $this->keranjang($kelas);

            $this->assertTrue(
                method_exists($k, 'panjangStandarBarisRetur'),
                $kelas . ' belum memakai MemilihSatuanReturRusak'
            );

            $k->bahanRetur = [[
                'bahan_id' => self::PIPA,
                'unit_price' => 291.6667,
                'satuan' => SatuanBahanHelper::SATUAN_BATANG,
                'qty' => 2,
            ]];

            $this->assertSame(1200.0, $k->qtyDasarBarisRetur(0), $kelas . ': konversi retur salah');

            $k->bahanRusak = [[
                'bahan_id' => self::PIPA,
                'unit_price' => 291.6667,
                'satuan' => SatuanBahanHelper::SATUAN_BATANG,
                'qty' => 1,
            ]];

            $this->assertSame(600.0, $k->qtyDasarBarisRusak(0), $kelas . ': konversi rusak salah');
        }
    }
}
