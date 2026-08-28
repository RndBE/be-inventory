<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class QcBahanMasukPetugasSelectionTest extends TestCase
{
    public function test_petugas_qc_can_come_from_hardware_organization_or_petugas_qc_role(): void
    {
        $source = file_get_contents(__DIR__.'/../../app/Livewire/Quality/QcWizard.php');

        $this->assertStringContainsString("where('nama', 'Hardware')", $source);
        $this->assertStringContainsString("orWhereHas('roles'", $source);
        $this->assertStringContainsString("whereIn('name', ['Petugas QC', 'Petugas_QC'])", $source);
    }
}
