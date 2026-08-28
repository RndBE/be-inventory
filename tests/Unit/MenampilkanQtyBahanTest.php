<?php

namespace Tests\Unit;

use App\Models\Bahan;
use App\Models\BahanKeluarDetails;
use App\Models\BahanReturDetails;
use App\Models\BahanRusakDetails;
use App\Models\PengambilanBahanDetails;
use App\Models\ProjekDetails;
use Tests\TestCase;

/**
 * Angka qty riwayat dan cetakan.
 *
 * Kolom `qty` bahan batangan menyimpan panjang total dalam cm. Di halaman edit
 * angka itu masih bisa dikoreksi orangnya; di riwayat dan di PDF tidak — apa
 * yang tercetak itulah yang dibaca. Jadi 3.000 harus muncul sebagai "5 Batang",
 * bukan sebagai angka yang mudah dikira jumlah barang.
 *
 * Tidak ada query database di sini: model dibuat tanpa disimpan dan relasi
 * `dataBahan` diisi langsung lewat setRelation.
 */
class MenampilkanQtyBahanTest extends TestCase
{
    private const PANJANG = 600;

    /**
     * Semua tabel detail transaksi bahan memakai trait yang sama, jadi
     * perilakunya harus identik. Kalau satu model terlewat memasangnya,
     * memanggil qtyTampil() di Blade akan melempar BadMethodCallException dan
     * halamannya mati — bukan sekadar tampil salah.
     */
    public static function modelDetailProvider(): array
    {
        return [
            'bahan keluar' => [BahanKeluarDetails::class],
            'bahan retur' => [BahanReturDetails::class],
            'bahan rusak' => [BahanRusakDetails::class],
            'pengambilan bahan' => [PengambilanBahanDetails::class],
            'projek' => [ProjekDetails::class],
        ];
    }

    private function baris(string $kelas, $qty, ?int $panjangStandar, ?Bahan $bahan = null)
    {
        $baris = new $kelas(['qty' => $qty]);
        $baris->qty = $qty;

        if ($panjangStandar !== null || $bahan !== null) {
            $baris->setRelation('dataBahan', $bahan ?? new Bahan(['panjang_standar' => $panjangStandar]));
        } else {
            $baris->setRelation('dataBahan', null);
        }

        return $baris;
    }

    /**
     * Satu batang utuh dan 600 cm potongan menghasilkan qty ledger yang sama,
     * jadi angka ledger saja tidak bisa membedakan keduanya. Yang membedakan
     * cuma jejak satuan yang direkam saat pengajuan dibuat.
     *
     * @dataProvider modelDetailProvider
     */
    public function test_jejak_satuan_membedakan_batang_utuh_dari_potongan(string $kelas): void
    {
        $batangUtuh = $this->baris($kelas, 600, self::PANJANG);
        $batangUtuh->satuan_input = 'batang';
        $batangUtuh->qty_input = 1;

        $potongan = $this->baris($kelas, 600, self::PANJANG);
        $potongan->satuan_input = 'cm';
        $potongan->qty_input = 600;

        $this->assertSame('1 Batang', $batangUtuh->qtyTampil(), 'Angka ledger keduanya memang sama.');
        $this->assertSame('1 Batang', $potongan->qtyTampil());

        $this->assertSame('1 Batang', $batangUtuh->qtyInputTampil());
        $this->assertSame('600 cm', $potongan->qtyInputTampil());
        $this->assertSame('Batang', $batangUtuh->satuanInputTampil());
        $this->assertSame('cm', $potongan->satuanInputTampil());
    }

    /**
     * Untuk tabel yang punya kolom satuan sendiri, angkanya harus bisa diambil
     * tanpa satuannya supaya tidak tertulis dua kali dalam satu baris.
     *
     * @dataProvider modelDetailProvider
     */
    public function test_angka_input_bisa_diambil_tanpa_satuan(string $kelas): void
    {
        $baris = $this->baris($kelas, 1200, self::PANJANG);
        $baris->satuan_input = 'batang';
        $baris->qty_input = 2;

        $this->assertSame('2', $baris->qtyInputAngka());
    }

    /**
     * Baris yang dibuat sebelum kolom jejak satuan ada tidak boleh menebak:
     * pemanggilnya yang memutuskan mau jatuh ke apa.
     *
     * @dataProvider modelDetailProvider
     */
    public function test_baris_tanpa_jejak_satuan_mengembalikan_null(string $kelas): void
    {
        $baris = $this->baris($kelas, 600, self::PANJANG);

        $this->assertNull($baris->satuanInputTampil());
        $this->assertNull($baris->qtyInputTampil());
        $this->assertNull($baris->qtyInputAngka());
    }

    /**
     * Bahan biasa tidak punya dua satuan, jadi tidak ada yang perlu dibedakan.
     *
     * @dataProvider modelDetailProvider
     */
    public function test_bahan_biasa_tidak_punya_jejak_satuan(string $kelas): void
    {
        $baris = $this->baris($kelas, 12, null, new Bahan(['panjang_standar' => null]));
        $baris->satuan_input = 'cm';
        $baris->qty_input = 12;

        $this->assertNull($baris->satuanInputTampil());
        $this->assertNull($baris->qtyInputTampil());
    }

    /**
     * @dataProvider modelDetailProvider
     */
    public function test_bahan_batangan_tampil_sebagai_batang(string $kelas): void
    {
        $baris = $this->baris($kelas, 3000, self::PANJANG);

        $this->assertSame('5 Batang', $baris->qtyTampil());
    }

    /**
     * @dataProvider modelDetailProvider
     */
    public function test_sisa_potongan_ikut_ditulis(string $kelas): void
    {
        $baris = $this->baris($kelas, 3640, self::PANJANG);

        $this->assertSame('6 Batang + 40 cm', $baris->qtyTampil());
    }

    /**
     * Potongan yang kurang dari satu batang tampil dalam cm, bukan "0 Batang".
     *
     * @dataProvider modelDetailProvider
     */
    public function test_potongan_di_bawah_satu_batang_tampil_dalam_cm(string $kelas): void
    {
        $baris = $this->baris($kelas, 40, self::PANJANG);

        $this->assertSame('40 cm', $baris->qtyTampil());
    }

    /**
     * @dataProvider modelDetailProvider
     */
    public function test_bahan_biasa_tampil_apa_adanya(string $kelas): void
    {
        $baris = $this->baris($kelas, 12, null, new Bahan(['panjang_standar' => null]));

        $this->assertSame('12', $baris->qtyTampil());
    }

    /**
     * Baris produk setengah jadi dan baris yang bahannya sudah terhapus tidak
     * punya relasi `dataBahan`. Keduanya harus lewat, bukan melempar error.
     *
     * @dataProvider modelDetailProvider
     */
    public function test_baris_tanpa_bahan_tidak_error(string $kelas): void
    {
        $baris = $this->baris($kelas, 7, null);

        $this->assertSame('7', $baris->qtyTampil());
    }

    /**
     * Argumennya dipakai untuk menampilkan angka lain dalam satuan yang sama,
     * mis. qty satu potongan di dalam `details`.
     *
     * @dataProvider modelDetailProvider
     */
    public function test_angka_lain_bisa_dilewatkan(string $kelas): void
    {
        $baris = $this->baris($kelas, 3000, self::PANJANG);

        $this->assertSame('2 Batang', $baris->qtyTampil(1200));
    }

    /**
     * Nama unit bahan dipakai kalau ada, supaya labelnya ikut istilah gudang
     * ("Lonjor", "Batang") dan bukan selalu kata bawaan.
     */
    public function test_nama_unit_bahan_dipakai_sebagai_label(): void
    {
        $bahan = new Bahan(['panjang_standar' => self::PANJANG]);
        $bahan->setRelation('dataUnit', (object) ['nama' => 'Lonjor']);

        $baris = $this->baris(BahanKeluarDetails::class, 1200, null, $bahan);

        $this->assertSame('2 Lonjor', $baris->qtyTampil());
    }
}
