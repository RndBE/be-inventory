<?php

namespace Tests\Unit;

use App\Http\Controllers\DashboardController;
use App\Models\PembelianBahan;
use App\Models\PembelianBahanDetails;
use PHPUnit\Framework\TestCase;

class DashboardPembelianSummaryTest extends TestCase
{
    public function test_import_purchase_currency_suffix_is_grouped_by_base_submission_type(): void
    {
        $pembelian = new PembelianBahan([
            'jenis_pengajuan' => 'Pembelian Bahan/Barang/Alat Impor|USD',
        ]);
        $pembelian->setRelation('pembelianBahanDetails', collect([
            new PembelianBahanDetails(['sub_total' => 150000]),
        ]));

        $totals = (new DashboardController())->summarizePembelianTotals(collect([$pembelian]));

        $this->assertSame(150000, $totals['Pembelian Bahan/Barang/Alat Impor']);
        $this->assertArrayNotHasKey('Pembelian Bahan/Barang/Alat Impor|USD', $totals);
    }
}
