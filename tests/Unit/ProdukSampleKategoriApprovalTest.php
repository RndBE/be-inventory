<?php

namespace Tests\Unit;

use App\Models\BahanKeluar;
use App\Models\ProdukSample;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProdukSampleKategoriApprovalTest extends TestCase
{
    /**
     * Pengaju beserta atasannya dirakit tanpa menyentuh database: relasinya
     * dipasang manual supaya test ini tidak bergantung pada migration.
     */
    private function bahanKeluarProdukSample(?int $atasanLevel2Id, ?int $atasanLevel3Id, string $kategori = ProdukSample::KATEGORI_RND): BahanKeluar
    {
        $pengaju = new User;
        $pengaju->atasan_level2_id = $atasanLevel2Id;
        $pengaju->atasan_level3_id = $atasanLevel3Id;

        foreach (['atasanLevel2' => $atasanLevel2Id, 'atasanLevel3' => $atasanLevel3Id] as $relasi => $id) {
            if ($id === null) {
                $pengaju->setRelation($relasi, null);
                continue;
            }

            $atasan = new User;
            $atasan->id = $id;
            $atasan->name = 'Atasan ' . $id;
            $pengaju->setRelation($relasi, $atasan);
        }

        $bahanKeluar = new BahanKeluar([
            'produk_sample_id' => 5,
            'kategori_pengajuan' => $kategori,
        ]);
        $bahanKeluar->setRelation('dataUser', $pengaju);

        return $bahanKeluar;
    }

    #[Test]
    public function produk_sample_kategori_rnd_diputus_manager(): void
    {
        $bahanKeluar = $this->bahanKeluarProdukSample(3, 7);

        $this->assertTrue($bahanKeluar->leaderDiputusManager());
        $this->assertSame(3, $bahanKeluar->approverLeader()?->id);
        $this->assertSame('Manager', $bahanKeluar->approvalAwalRole());
    }

    #[Test]
    public function rnd_tanpa_atasan_level_2_turun_ke_leader(): void
    {
        $bahanKeluar = $this->bahanKeluarProdukSample(null, 7);

        $this->assertSame(7, $bahanKeluar->approverLeader()?->id);
        $this->assertSame('Leader', $bahanKeluar->approvalAwalRole());
    }

    #[Test]
    public function rnd_tanpa_atasan_sama_sekali_tidak_punya_approver(): void
    {
        $bahanKeluar = $this->bahanKeluarProdukSample(null, null);

        $this->assertNull($bahanKeluar->approverLeader());
    }

    #[Test]
    public function produk_sample_kategori_non_rnd_tetap_diputus_leader(): void
    {
        $bahanKeluar = $this->bahanKeluarProdukSample(3, 7, ProdukSample::KATEGORI_NON_RND);

        $this->assertFalse($bahanKeluar->leaderDiputusManager());
        $this->assertSame(7, $bahanKeluar->approverLeader()?->id);
        $this->assertSame('Leader', $bahanKeluar->approvalAwalRole());
    }

    #[Test]
    public function produk_sample_lama_tanpa_kategori_tetap_diputus_leader(): void
    {
        $bahanKeluar = new BahanKeluar(['produk_sample_id' => 5]);

        $this->assertFalse($bahanKeluar->leaderDiputusManager());
        $this->assertSame('Leader', $bahanKeluar->approvalAwalRole());
    }

    #[Test]
    public function kategori_produk_sample_default_ke_produksi(): void
    {
        $this->assertSame(
            ProdukSample::KATEGORI_NON_RND,
            (new ProdukSample)->kategoriPengajuan()
        );

        $this->assertSame(
            ProdukSample::KATEGORI_NON_RND,
            (new ProdukSample(['kategori_pengajuan' => 'Entah']))->kategoriPengajuan()
        );

        $this->assertSame(
            ProdukSample::KATEGORI_RND,
            (new ProdukSample(['kategori_pengajuan' => ProdukSample::KATEGORI_RND]))->kategoriPengajuan()
        );
    }

    #[Test]
    public function view_form_produk_sample_dapat_dikompilasi(): void
    {
        $views = [
            resource_path('views/pages/produk-sample/create.blade.php'),
            resource_path('views/pages/produk-sample/edit.blade.php'),
        ];

        foreach ($views as $view) {
            $compiled = Blade::compileString((string) file_get_contents($view));

            $this->assertNotSame('', $compiled);
            $this->assertStringContainsString('kategori_pengajuan', $compiled);
        }
    }
}
