<?php

namespace Tests\Unit;

use App\Models\BahanKeluar;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BahanKeluarProjekRndApprovalTest extends TestCase
{
    #[Test]
    public function projek_rnd_diputus_manager_meski_pengaju_punya_leader(): void
    {
        $this->assertSame(3, BahanKeluar::approverLeaderId(true, 7, 3));
        $this->assertSame('Belum disetujui', BahanKeluar::statusLeaderAwal(true, 7, 3));

        $bahanKeluar = new BahanKeluar(['projek_rnd_id' => 10]);

        $this->assertTrue($bahanKeluar->leaderDiputusManager());
        $this->assertSame('Manager', $bahanKeluar->approvalAwalRole());
    }

    #[Test]
    public function transaksi_non_rnd_tetap_diputus_leader(): void
    {
        $this->assertSame(7, BahanKeluar::approverLeaderId(false, 7, 3));

        $bahanKeluar = new BahanKeluar;

        $this->assertFalse($bahanKeluar->leaderDiputusManager());
        $this->assertSame('Leader', $bahanKeluar->approvalAwalRole());
    }

    #[Test]
    public function transaksi_non_rnd_tanpa_leader_jatuh_ke_manager(): void
    {
        $this->assertSame(3, BahanKeluar::approverLeaderId(false, null, 3));
    }

    #[Test]
    public function view_approval_bahan_keluar_dapat_dikompilasi(): void
    {
        $views = [
            resource_path('views/livewire/bahan-keluar-table.blade.php'),
            resource_path('views/pages/bahan-keluars/approval-leader.blade.php'),
            resource_path('views/pages/bahan-keluars/preview.blade.php'),
        ];

        foreach ($views as $view) {
            $compiled = Blade::compileString((string) file_get_contents($view));

            $this->assertNotSame('', $compiled);
        }
    }
}
