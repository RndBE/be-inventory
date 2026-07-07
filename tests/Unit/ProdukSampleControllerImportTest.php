<?php

namespace Tests\Unit;

use App\Models\ProdukJadis;
use App\Exports\ProdukSampleExport;
use PHPUnit\Framework\TestCase;

class ProdukSampleControllerImportTest extends TestCase
{
    public function test_produk_sample_info_uses_produk_jadis_model_import(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Http/Controllers/ProdukSampleController.php');

        $this->assertStringContainsString(
            'use ' . ProdukJadis::class . ';',
            $source,
            'ProdukSampleController::info must import ProdukJadis from App\Models so PHP does not resolve it as App\Http\Controllers\ProdukJadis.'
        );
    }

    public function test_produk_sample_export_uses_produk_sample_export(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/Http/Controllers/ProdukSampleController.php');

        $this->assertStringContainsString(
            'use ' . ProdukSampleExport::class . ';',
            $source,
            'ProdukSampleController::export must use the ProdukSample export, not the generic project export.'
        );

        $this->assertStringContainsString(
            'new ProdukSampleExport($produkSample_id)',
            $source,
            'ProdukSampleController::export must instantiate ProdukSampleExport with the selected sample id.'
        );
    }
}
