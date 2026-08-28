<?php

namespace Tests\Unit;

use App\Livewire\EditBahanProjekRndCart;
use App\Livewire\EditPembelianBahanCart;
use App\Models\Bahan;
use Tests\TestCase;

/**
 * Pilihan satuan di halaman edit.
 *
 * Halaman edit berbeda dari halaman create: barisnya datang dari database,
 * bukan dari keranjang sesi ini. Test ini menjaga supaya baris lama itu tetap
 * dapat satuan yang benar.
 *
 * Tidak ada query database di sini — model Bahan dibuat tanpa disimpan, dan
 * relasi unit dibiarkan null supaya Eloquent tidak perlu mencarinya.
 */
class SatuanBahanEditCartTest extends TestCase
{
    private const PANJANG = 600;

    /**
     * Baris yang sudah tersimpan tidak ada di `$cart`, jadi panjang standarnya
     * harus datang dari peta yang diisi saat halaman dimuat.
     */
    public function test_baris_tersimpan_dapat_panjang_standar_dari_peta(): void
    {
        $keranjang = new EditBahanProjekRndCart();
        $keranjang->panjangStandarItem = [7 => self::PANJANG];

        $this->assertSame(self::PANJANG, $keranjang->panjangStandarUntuk(7));
    }

    /**
     * Baris yang baru ditambahkan pada sesi ini tetap dibaca dari keranjang.
     */
    public function test_baris_baru_masih_dibaca_dari_keranjang(): void
    {
        $keranjang = new EditBahanProjekRndCart();
        $keranjang->cart = [(object) ['id' => 9, 'panjang_standar' => 400]];

        $this->assertSame(400, $keranjang->panjangStandarUntuk(9));
        $this->assertNull($keranjang->panjangStandarUntuk(99));
    }

    public function test_qty_dasar_memakai_panjang_dari_peta(): void
    {
        $keranjang = new EditBahanProjekRndCart();
        $keranjang->panjangStandarItem = [7 => self::PANJANG];
        $keranjang->satuan[7] = 'batang';
        $keranjang->qty[7] = 5;

        $this->assertSame(3000.0, $keranjang->qtyDasar(7));

        $keranjang->satuan[7] = 'cm';
        $this->assertSame(5.0, $keranjang->qtyDasar(7));
    }

    /**
     * Produk setengah jadi tidak punya panjang standar, jadi angkanya tidak
     * boleh ikut dikali walau satuannya kebetulan tersetel batang.
     */
    public function test_produk_setengah_jadi_tidak_dikonversi(): void
    {
        $keranjang = new EditBahanProjekRndCart();
        $keranjang->panjangStandarItem = [12 => null];
        $keranjang->satuan[12] = 'batang';
        $keranjang->qty[12] = 3;

        $this->assertNull($keranjang->panjangStandarUntuk(12));
        $this->assertSame(3.0, $keranjang->qtyDasar(12));
    }

    public function test_ganti_satuan_mengosongkan_qty(): void
    {
        $keranjang = new EditBahanProjekRndCart();
        $keranjang->panjangStandarItem = [7 => self::PANJANG];
        $keranjang->satuan[7] = 'batang';
        $keranjang->qty[7] = 5;
        $keranjang->subtotals[7] = 875000;

        $keranjang->updateSatuan(7);

        $this->assertNull($keranjang->qty[7]);
        $this->assertSame(0, $keranjang->subtotals[7]);
    }

    /**
     * Baris pengajuan lama tidak punya kolom satuan. Fallback-nya batang, sama
     * seperti QC bahan masuk, supaya angka yang sama tidak dibaca berbeda di
     * dua halaman.
     */
    public function test_label_pengajuan_lama_jatuh_ke_batang(): void
    {
        $keranjang = new EditPembelianBahanCart();
        $baris = [
            'bahan' => new Bahan(['panjang_standar' => self::PANJANG]),
            'satuan_input' => null,
        ];

        $this->assertSame('Batang', $keranjang->labelSatuanPengajuan($baris));
    }

    public function test_label_pengajuan_dalam_cm(): void
    {
        $keranjang = new EditPembelianBahanCart();
        $baris = [
            'bahan' => new Bahan(['panjang_standar' => self::PANJANG]),
            'satuan_input' => 'cm',
        ];

        $this->assertSame('cm', $keranjang->labelSatuanPengajuan($baris));
    }

    /**
     * Bahan biasa dan baris aset tampil seperti sebelumnya, tanpa tambahan teks.
     */
    public function test_label_kosong_untuk_bahan_biasa_dan_baris_aset(): void
    {
        $keranjang = new EditPembelianBahanCart();

        $this->assertSame('', $keranjang->labelSatuanPengajuan([
            'bahan' => new Bahan(['panjang_standar' => null]),
            'satuan_input' => 'batang',
        ]));

        $this->assertSame('', $keranjang->labelSatuanPengajuan([
            'nama_bahan' => 'Kursi Kantor',
        ]));
    }

    /**
     * Setiap keranjang edit yang qty-nya bisa diketik ulang harus memakai peta
     * panjang standar yang sama, bukan hanya yang kebetulan dites lebih dulu.
     *
     * Kalau salah satu terlewat, halamannya tetap jalan tapi angka "5" yang
     * dimaksud 5 batang akan tercatat 5 cm — tidak ada error, cuma stok yang
     * hampir tidak berkurang.
     *
     * @dataProvider keranjangEditProvider
     */
    public function test_semua_keranjang_edit_memakai_peta_panjang_standar(string $kelas, $kunci): void
    {
        $keranjang = new $kelas();
        $keranjang->panjangStandarItem = [$kunci => self::PANJANG];
        $keranjang->satuan[$kunci] = 'batang';
        $keranjang->qty[$kunci] = 5;

        $this->assertSame(self::PANJANG, $keranjang->panjangStandarUntuk($kunci));
        $this->assertSame(3000.0, $keranjang->qtyDasar($kunci));

        $keranjang->satuan[$kunci] = 'cm';
        $this->assertSame(5.0, $keranjang->qtyDasar($kunci));
    }

    /**
     * Kunci `$qty` tidak seragam antar keranjang: sebagian memakai id bahan,
     * keranjang komponen projek memakai `cart_key`, dan produksi produk jadi
     * memakai awalan `b_`/`p_` supaya id bahan dan id produk tidak bertabrakan.
     */
    public static function keranjangEditProvider(): array
    {
        return [
            'projek' => [\App\Livewire\EditBahanProjekCart::class, 7],
            'garansi projek' => [\App\Livewire\EditBahanGaransiProjekCart::class, 7],
            'produk sample' => [\App\Livewire\EditBahanProdukSampleCart::class, 7],
            'pengambilan bahan' => [\App\Livewire\EditBahanPengambilanBahanCart::class, 7],
            'komponen projek' => [\App\Livewire\EditKomponenProjekCart::class, 'bahan-7'],
            'produksi produk jadi' => [\App\Livewire\EditBahanProduksiProdukJadiCart::class, 'b_7'],
            'projek rnd' => [EditBahanProjekRndCart::class, 7],
        ];
    }

    /**
     * Bahan biasa tidak menampilkan pilihan satuan, dan angkanya lewat apa
     * adanya walau `$satuan` kebetulan tersetel batang.
     *
     * @dataProvider keranjangEditProvider
     */
    public function test_bahan_biasa_tidak_dikonversi_di_semua_keranjang(string $kelas, $kunci): void
    {
        $keranjang = new $kelas();
        $keranjang->panjangStandarItem = [$kunci => null];
        $keranjang->satuan[$kunci] = 'batang';
        $keranjang->qty[$kunci] = 5;

        $this->assertNull($keranjang->panjangStandarUntuk($kunci));
        $this->assertSame(5.0, $keranjang->qtyDasar($kunci));
    }
}
