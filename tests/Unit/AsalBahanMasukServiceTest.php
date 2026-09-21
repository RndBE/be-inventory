<?php

namespace Tests\Unit;

use App\Models\BahanKeluarDetails;
use App\Services\AsalBahanMasukService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class AsalBahanMasukServiceTest extends TestCase
{
    public function test_detail_dengan_beberapa_lot_menghasilkan_satu_baris_per_lot(): void
    {
        $service = $this->service([
            'KBM-001' => (object) ['tgl_masuk' => '2025-03-04 08:00:00', 'no_invoice' => 'INV-11'],
            'KBM-002' => (object) ['tgl_masuk' => '2025-05-20 08:00:00', 'no_invoice' => null],
        ]);

        $detail = new BahanKeluarDetails(['bahan_id' => 7, 'qty' => 3, 'sub_total' => 30000]);
        $detail->details = json_encode([
            ['kode_transaksi' => 'KBM-001', 'qty' => '1.00', 'unit_price' => '10000.00'],
            ['kode_transaksi' => 'KBM-002', 'qty' => '2.00', 'unit_price' => '10000.00'],
        ]);

        $asal = $service->untukDetailBahanKeluar($detail);

        $this->assertCount(2, $asal);
        $this->assertSame(
            ['KBM-001', '04/03/2025', 'INV-11', 1.0, AsalBahanMasukService::TERCATAT],
            $service->values($asal[0])
        );
        $this->assertSame(
            ['KBM-002', '20/05/2025', '-', 2.0, AsalBahanMasukService::TERCATAT],
            $service->values($asal[1])
        );
    }

    public function test_kode_yang_pembeliannya_sudah_hilang_tetap_ditulis(): void
    {
        $service = $this->service([]);

        $detail = new BahanKeluarDetails(['bahan_id' => 7, 'qty' => 1, 'sub_total' => 100]);
        $detail->details = json_encode([['kode_transaksi' => 'KBM-LAMA', 'qty' => '1.00']]);

        $asal = $service->untukDetailBahanKeluar($detail);

        $this->assertSame(
            ['KBM-LAMA', '-', '-', 1.0, AsalBahanMasukService::TERCATAT],
            $service->values($asal[0])
        );
    }

    public function test_tanpa_kode_tersimpan_asal_dicocokkan_lewat_harga_satuan(): void
    {
        $service = $this->service([], [
            (object) ['kode_transaksi' => 'KBM-003', 'tgl_masuk' => '2025-01-09 07:00:00', 'no_invoice' => 'INV-9'],
        ]);

        // 24.000 / 4 = 6.000 per satuan; itu angka yang dicocokkan ke purchase_details.
        $detail = new BahanKeluarDetails(['bahan_id' => 7, 'qty' => 4, 'sub_total' => 24000]);
        $detail->details = json_encode([['qty' => '4.00', 'unit_price' => '6000.00']]);

        $asal = $service->untukDetailBahanKeluar($detail);

        $this->assertCount(1, $asal);
        $this->assertSame(
            ['KBM-003', '09/01/2025', 'INV-9', 4.0, AsalBahanMasukService::COCOK_HARGA],
            $service->values($asal[0])
        );
        $this->assertSame(6000.0, $service->hargaTerakhirDicari);
    }

    public function test_harga_yang_cocok_ke_banyak_pembelian_ditandai_perlu_dicek(): void
    {
        $service = $this->service([], [
            (object) ['kode_transaksi' => 'KBM-004', 'tgl_masuk' => '2025-01-09 07:00:00', 'no_invoice' => null],
            (object) ['kode_transaksi' => 'KBM-005', 'tgl_masuk' => '2025-02-09 07:00:00', 'no_invoice' => null],
        ]);

        $detail = new BahanKeluarDetails(['bahan_id' => 7, 'qty' => 2, 'sub_total' => 12000]);
        $detail->details = json_encode([]);

        $asal = $service->untukDetailBahanKeluar($detail);

        $this->assertSame(
            ['KBM-004, KBM-005', '-', '-', 2.0, AsalBahanMasukService::PERLU_DICEK],
            $service->values($asal[0])
        );
    }

    public function test_harga_tanpa_pembelian_yang_cocok_ditandai_tidak_tercatat(): void
    {
        $service = $this->service();

        $detail = new BahanKeluarDetails(['bahan_id' => 7, 'qty' => 2, 'sub_total' => 12000]);
        $detail->details = json_encode([]);

        $asal = $service->untukDetailBahanKeluar($detail);

        $this->assertSame(
            ['-', '-', '-', '', AsalBahanMasukService::TIDAK_TERCATAT],
            $service->values($asal[0])
        );
    }

    public function test_baris_penampung_tanpa_qty_dibedakan_dari_asal_yang_hilang(): void
    {
        $service = $this->service();

        $detail = new BahanKeluarDetails(['bahan_id' => 7, 'qty' => 0, 'sub_total' => 0]);
        $detail->details = json_encode([]);

        $asal = $service->untukDetailBahanKeluar($detail);

        $this->assertSame(AsalBahanMasukService::TANPA_QTY, $asal[0]['ketelusuran']);
    }

    public function test_produk_setengah_jadi_tidak_ditelusuri_ke_pembelian(): void
    {
        $service = $this->service();

        $detail = new BahanKeluarDetails(['produk_id' => 12]);
        $detail->details = json_encode([['kode_transaksi' => 'PSJ-001', 'qty' => '1.00']]);

        $asal = $service->untukDetailBahanKeluar($detail);

        $this->assertCount(1, $asal);
        $this->assertSame(AsalBahanMasukService::BUKAN_PEMBELIAN, $asal[0]['ketelusuran']);
    }

    public function test_jumlah_kolom_sama_dengan_jumlah_judulnya(): void
    {
        $service = $this->service();

        $this->assertCount(
            count($service->headings()),
            $service->values([])
        );
    }

    /**
     * @param array<string, object> $pembelian  kode_transaksi => baris purchases
     * @param array<int, object>    $kandidat   hasil pencocokan harga
     */
    private function service(array $pembelian = [], array $kandidat = []): AsalBahanMasukService
    {
        return new class($pembelian, $kandidat) extends AsalBahanMasukService {
            public ?float $hargaTerakhirDicari = null;

            public function __construct(private array $pembelian, private array $kandidat)
            {
            }

            protected function pembelian(string $kodeTransaksi)
            {
                return $this->pembelian[$kodeTransaksi] ?? null;
            }

            protected function kandidat(int $bahanId, float $harga)
            {
                $this->hargaTerakhirDicari = $harga;

                return new Collection($this->kandidat);
            }
        };
    }
}
