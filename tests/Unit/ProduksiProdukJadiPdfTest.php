<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProduksiProdukJadiPdfTest extends TestCase
{
    #[Test]
    public function route_pdf_diarahkan_ke_controller_produksi_produk_jadi(): void
    {
        $routes = $this->contents('routes/web.php');

        $this->assertStringContainsString(
            "ProduksiProdukJadiController::class, 'downloadPdf'",
            $routes
        );
        $this->assertStringContainsString("name('produksi-produk-jadi.downloadPdf')", $routes);
    }

    #[Test]
    public function tombol_pdf_hanya_tampil_untuk_status_selesai(): void
    {
        $table = $this->contents('resources/views/livewire/produksi-produk-jadi-table.blade.php');

        $this->assertStringContainsString("@if(\$produksi->status === 'Selesai')", $table);
        $this->assertStringContainsString("route('produksi-produk-jadi.downloadPdf'", $table);
    }

    #[Test]
    public function endpoint_pdf_juga_menolak_produksi_yang_belum_selesai(): void
    {
        $controller = $this->contents('app/Http/Controllers/ProduksiProdukJadiController.php');

        $this->assertStringContainsString("if (\$produksiProdukJadi->status !== 'Selesai')", $controller);
        $this->assertStringContainsString('PDF hanya tersedia untuk produksi produk jadi yang sudah selesai.', $controller);
    }

    #[Test]
    public function pdf_memuat_identitas_produk_dan_jumlah_produksi(): void
    {
        $view = $this->contents('resources/views/pages/produksi-produk-jadi/pdf.blade.php');

        $this->assertStringContainsString('FORM BAHAN PRODUK PRODUKSI JADI PT. ARTA TEKNOLOGI COMUNINDO', $view);
        $this->assertStringContainsString('$produksiProdukJadi->dataProdukJadi->nama_produk', $view);
        $this->assertStringContainsString('$produksiProdukJadi->keterangan', $view);
        $this->assertStringContainsString('Kode Bahan', $view);
        $this->assertStringContainsString('Unit/Produksi', $view);
        $this->assertStringContainsString('$produksiProdukJadi->jml_produksi ?? 0', $view);
        $this->assertStringNotContainsString('$detail->qty / $jumlahProduksi', $view);
    }

    #[Test]
    public function template_pdf_dapat_dikompilasi(): void
    {
        $compiler = new BladeCompiler(new Filesystem(), sys_get_temp_dir());

        $compiled = $compiler->compileString(
            $this->contents('resources/views/pages/produksi-produk-jadi/pdf.blade.php')
        );

        $this->assertNotEmpty($compiled);
    }

    private function contents(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$relativePath);

        $this->assertNotFalse($contents, "Gagal membaca {$relativePath}");

        return $contents;
    }
}
