<?php

namespace Tests\Unit;

use App\Models\BahanKeluar;
use App\Models\BahanKeluarDetails;
use App\Models\BahanSetengahjadi;
use App\Models\BahanSetengahjadiDetails;
use App\Models\ProdukJadiDetails;
use App\Models\ProdukJadis;
use App\Services\ProductFlowService;
use PHPUnit\Framework\TestCase;

class ProductFlowServiceTest extends TestCase
{
    public function test_bahan_keluar_detail_from_produk_setengah_jadi_describes_source_and_destination(): void
    {
        $bahanKeluar = new BahanKeluar([
            'kode_transaksi' => 'BK-001',
            'projek_rnd_id' => 10,
            'status' => 'Disetujui',
            'keterangan' => 'Riset logger',
        ]);

        $produkHeader = new BahanSetengahjadi([
            'kode_transaksi' => 'PSJ-001',
        ]);

        $produkDetail = new BahanSetengahjadiDetails([
            'serial_number' => 'SN-PSJ-001',
        ]);
        $produkDetail->setRelation('bahanSetengahjadi', $produkHeader);

        $detail = new BahanKeluarDetails([
            'produk_id' => 5,
            'serial_number' => 'SN-PSJ-001',
        ]);
        $detail->setRelation('bahanKeluar', $bahanKeluar);
        $detail->setRelation('dataProduk', $produkDetail);

        $flow = (new ProductFlowService())->forBahanKeluarDetail($detail);

        $this->assertSame('Produk Setengah Jadi', $flow['jenis_item']);
        $this->assertSame('PSJ-001', $flow['kode_sumber']);
        $this->assertSame('Stok Produk Setengah Jadi', $flow['asal_flow']);
        $this->assertSame('SN-PSJ-001', $flow['serial_number_flow']);
        $this->assertSame('Proyek RnD', $flow['tujuan_flow']);
        $this->assertSame('BK-001', $flow['kode_tujuan']);
        $this->assertSame('Disetujui', $flow['status_flow']);
    }

    public function test_bahan_keluar_detail_from_produk_jadi_describes_source_and_sample_destination(): void
    {
        $bahanKeluar = new BahanKeluar([
            'kode_transaksi' => 'BK-002',
            'produk_sample_id' => 7,
            'status' => 'Disetujui',
        ]);

        $produkHeader = new ProdukJadis([
            'kode_transaksi' => 'PJ-001',
        ]);

        $produkDetail = new ProdukJadiDetails([
            'serial_number' => 'SN-PJ-001',
        ]);
        $produkDetail->setRelation('ProdukJadis', $produkHeader);

        $detail = new BahanKeluarDetails([
            'produk_jadis_id' => 9,
            'serial_number' => 'SN-PJ-001',
        ]);
        $detail->setRelation('bahanKeluar', $bahanKeluar);
        $detail->setRelation('dataProdukJadi', $produkDetail);

        $flow = (new ProductFlowService())->forBahanKeluarDetail($detail);

        $this->assertSame('Produk Jadi', $flow['jenis_item']);
        $this->assertSame('PJ-001', $flow['kode_sumber']);
        $this->assertSame('Stok Produk Jadi', $flow['asal_flow']);
        $this->assertSame('SN-PJ-001', $flow['serial_number_flow']);
        $this->assertSame('Produk Sample', $flow['tujuan_flow']);
        $this->assertSame('BK-002', $flow['kode_tujuan']);
        $this->assertSame('Disetujui', $flow['status_flow']);
    }
}
