<?php

namespace Tests\Unit;

use App\Models\ProdukJadis;
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
}
