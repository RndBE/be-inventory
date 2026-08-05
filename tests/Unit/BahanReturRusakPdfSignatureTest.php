<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BahanReturRusakPdfSignatureTest extends TestCase
{
    public static function pdfTypes(): array
    {
        return [
            'bahan retur' => [
                'app/Http/Controllers/BahanReturController.php',
                'resources/views/pages/bahan-returs/pdf.blade.php',
                '$bahanRetur',
            ],
            'bahan rusak' => [
                'app/Http/Controllers/BahanRusakController.php',
                'resources/views/pages/bahan-rusaks/pdf.blade.php',
                '$bahanRusak',
            ],
        ];
    }

    #[Test]
    #[DataProvider('pdfTypes')]
    public function controller_mengirim_tiga_tanda_tangan_otomatis_ke_pdf(
        string $controllerPath,
        string $viewPath,
        string $recordVariable
    ): void {
        $controller = $this->contents($controllerPath);

        $this->assertStringContainsString("'tandaTanganPengaju'", $controller);
        $this->assertStringContainsString("'tandaTanganPurchasing'", $controller);
        $this->assertStringContainsString("'tandaTanganManager'", $controller);
    }

    #[Test]
    #[DataProvider('pdfTypes')]
    public function pengaju_langsung_ditampilkan_tetapi_purchasing_dan_manager_menunggu_persetujuan(
        string $controllerPath,
        string $viewPath,
        string $recordVariable
    ): void {
        $view = $this->contents($viewPath);

        $this->assertStringContainsString('public_path(\'storage/\' . $tandaTanganPengaju)', $view);
        $this->assertStringContainsString(
            "@if ({$recordVariable}->status === 'Disetujui' && \$tandaTanganPurchasing)",
            $view
        );
        $this->assertStringContainsString(
            "@if ({$recordVariable}->status === 'Disetujui' && \$tandaTanganManager)",
            $view
        );
    }

    #[Test]
    #[DataProvider('pdfTypes')]
    public function tanggal_dokumen_hanya_diisi_dari_tanggal_diterima_setelah_disetujui(
        string $controllerPath,
        string $viewPath,
        string $recordVariable
    ): void {
        $view = $this->contents($viewPath);

        $this->assertStringContainsString(
            "@if ({$recordVariable}->status === 'Disetujui' && {$recordVariable}->tgl_diterima)",
            $view
        );
        $this->assertStringContainsString("Carbon::parse({$recordVariable}->tgl_diterima)", $view);
    }

    #[Test]
    #[DataProvider('pdfTypes')]
    public function tanda_tangan_manager_admin_tetap_manual(
        string $controllerPath,
        string $viewPath,
        string $recordVariable
    ): void {
        $view = $this->contents($viewPath);

        $this->assertStringContainsString('Manager Admin', $view);
        $this->assertStringNotContainsString('$tandaTanganAdminManager', $view);
    }

    #[Test]
    public function pdf_bahan_rusak_tidak_lagi_mengakses_relasi_data_user_yang_tidak_ada(): void
    {
        $controller = $this->contents('app/Http/Controllers/BahanRusakController.php');

        $this->assertStringNotContainsString('$bahanRusak->dataUser', $controller);
    }

    #[Test]
    #[DataProvider('pdfTypes')]
    public function template_pdf_dapat_dikompilasi(
        string $controllerPath,
        string $viewPath,
        string $recordVariable
    ): void {
        $compiler = new BladeCompiler(new Filesystem(), sys_get_temp_dir());

        $compiled = $compiler->compileString($this->contents($viewPath));

        $this->assertNotEmpty($compiled);
    }

    private function contents(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$relativePath);

        $this->assertNotFalse($contents, "Gagal membaca {$relativePath}");

        return $contents;
    }
}
