<?php

namespace Tests\Unit;

use App\Livewire\Concerns\MemilihSatuanBahan;
use PHPUnit\Framework\TestCase;

/**
 * Keranjang tiruan yang cuma memakai trait pilihan satuan.
 *
 * Trait ini tidak menyentuh Livewire maupun database, jadi bisa diuji lewat
 * kelas biasa. `batasiQtyInput` dan `setelSatuanAwal` protected, dibuka di sini
 * supaya perilaku pembatasan angkanya bisa diperiksa langsung.
 */
class KeranjangTiruan
{
    use MemilihSatuanBahan;

    public $cart = [];

    public $qty = [];

    public $subtotals = [];

    public function batasi($itemId, $qtyInput, $stokDasar)
    {
        return $this->batasiQtyInput($itemId, $qtyInput, $stokDasar);
    }

    public function setelAwal($itemId, ?int $panjangStandar): void
    {
        $this->setelSatuanAwal($itemId, $panjangStandar);
    }
}

class MemilihSatuanBahanTest extends TestCase
{
    private const PANJANG = 600;

    private function keranjang(array $item = ['bahan_id' => 7, 'panjang_standar' => 600, 'unit' => 'Lonjor']): KeranjangTiruan
    {
        $keranjang = new KeranjangTiruan();
        $keranjang->cart = [(object) $item];

        return $keranjang;
    }

    public function test_panjang_standar_dibaca_dari_item_keranjang(): void
    {
        $this->assertSame(self::PANJANG, $this->keranjang()->panjangStandarUntuk(7));
    }

    public function test_item_dicari_lewat_id_bahan_id_atau_cart_key(): void
    {
        $lewatId = $this->keranjang(['id' => 9, 'panjang_standar' => 400]);
        $lewatCartKey = $this->keranjang(['cart_key' => '9-SN123', 'panjang_standar' => 300]);

        $this->assertSame(400, $lewatId->panjangStandarUntuk(9));
        $this->assertSame(300, $lewatCartKey->panjangStandarUntuk('9-SN123'));
        $this->assertNull($lewatId->panjangStandarUntuk(999));
    }

    public function test_satuan_awal_batang_untuk_bahan_batangan(): void
    {
        $keranjang = $this->keranjang();

        $keranjang->setelAwal(7, self::PANJANG);
        $this->assertSame('batang', $keranjang->satuanUntuk(7));

        $keranjang->setelAwal(8, null);
        $this->assertSame('cm', $keranjang->satuanUntuk(8));
    }

    public function test_qty_dasar_mengonversi_angka_yang_diketik(): void
    {
        $keranjang = $this->keranjang();
        $keranjang->satuan[7] = 'batang';
        $keranjang->qty[7] = 5;

        $this->assertSame(3000.0, $keranjang->qtyDasar(7));

        $keranjang->satuan[7] = 'cm';
        $this->assertSame(5.0, $keranjang->qtyDasar(7));
    }

    public function test_label_satuan_memakai_nama_unit_bahan(): void
    {
        $keranjang = $this->keranjang();

        $keranjang->satuan[7] = 'batang';
        $this->assertSame('Lonjor', $keranjang->labelSatuanUntuk(7));

        $keranjang->satuan[7] = 'cm';
        $this->assertSame('cm', $keranjang->labelSatuanUntuk(7));
    }

    /**
     * Nama unit dipakai untuk menamai opsi batang di dropdown, jadi tidak boleh
     * ikut berubah saat satuan aktifnya cm. Kalau ikut berubah, kedua opsinya
     * bernama "cm" dan user tidak punya jalan kembali ke batang.
     */
    public function test_nama_unit_tidak_ikut_satuan_yang_sedang_aktif(): void
    {
        $keranjang = $this->keranjang();

        $keranjang->satuan[7] = 'batang';
        $this->assertSame('Lonjor', $keranjang->namaUnitUntuk(7));

        $keranjang->satuan[7] = 'cm';
        $this->assertSame('Lonjor', $keranjang->namaUnitUntuk(7));
    }

    /**
     * Baris yang tidak ada di keranjang - di halaman edit itu baris yang sudah
     * tersimpan - tetap dapat nama generik, bukan string kosong.
     */
    public function test_nama_unit_jatuh_ke_batang_kalau_item_tidak_ketemu(): void
    {
        $this->assertSame('Batang', $this->keranjang()->namaUnitUntuk(999));
        $this->assertSame('Batang', $this->keranjang(['bahan_id' => 7, 'panjang_standar' => 600])->namaUnitUntuk(7));
    }

    /**
     * Sisa 2040 cm cuma bisa diambil 3 batang utuh; 240 cm sisanya harus
     * diambil dengan memilih satuan cm.
     */
    public function test_maks_input_batang_dibulatkan_ke_bawah(): void
    {
        $keranjang = $this->keranjang();
        $keranjang->satuan[7] = 'batang';

        $this->assertSame(3.0, $keranjang->maksInput(7, 2040));

        $keranjang->satuan[7] = 'cm';
        $this->assertSame(2040.0, $keranjang->maksInput(7, 2040));
    }

    public function test_qty_di_bawah_stok_diteruskan_apa_adanya(): void
    {
        $keranjang = $this->keranjang();
        $keranjang->satuan[7] = 'batang';

        $this->assertSame(3, $keranjang->batasi(7, 3, 2040));
    }

    /**
     * Perbandingan wajib terjadi di satuan dasar: 4 batang = 2400 cm, lebih
     * besar dari sisa 2040 cm, walau angka "4" sendiri terlihat kecil.
     */
    public function test_qty_melebihi_stok_dipotong_ke_batas(): void
    {
        $keranjang = $this->keranjang();
        $keranjang->satuan[7] = 'batang';

        $this->assertSame(3.0, $keranjang->batasi(7, 4, 2040));
    }

    public function test_qty_kosong_atau_negatif_jadi_null(): void
    {
        $keranjang = $this->keranjang();
        $keranjang->satuan[7] = 'batang';

        $this->assertNull($keranjang->batasi(7, null, 2040));
        $this->assertNull($keranjang->batasi(7, '', 2040));
        $this->assertNull($keranjang->batasi(7, -1, 2040));
    }

    public function test_bahan_biasa_tidak_ikut_dikonversi(): void
    {
        $keranjang = $this->keranjang(['bahan_id' => 7, 'panjang_standar' => null]);
        $keranjang->satuan[7] = 'batang';
        $keranjang->qty[7] = 5;

        $this->assertSame(5.0, $keranjang->qtyDasar(7));
        $this->assertSame(12.0, $keranjang->maksInput(7, 12));
        $this->assertSame(12.0, $keranjang->batasi(7, 20, 12));
    }

    public function test_ganti_satuan_mengosongkan_angka(): void
    {
        $keranjang = $this->keranjang();
        $keranjang->satuan[7] = 'batang';
        $keranjang->qty[7] = 5;
        $keranjang->subtotals[7] = 875000;

        $keranjang->updateSatuan(7);

        $this->assertNull($keranjang->qty[7]);
        $this->assertSame(0, $keranjang->subtotals[7]);
    }
}
