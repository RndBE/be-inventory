<?php

namespace Tests\Unit;

use App\Models\PerbaikanData;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class PerbaikanDataTest extends TestCase
{
    public function test_nama_pengaju_diambil_dari_relasi_user_bukan_kolom_teksnya(): void
    {
        $perbaikan = new PerbaikanData(['pengaju' => 'Penyunting Terakhir']);
        $perbaikan->setRelation('user', new User(['name' => 'Pengaju Asli']));

        $this->assertSame('Pengaju Asli', $perbaikan->namaPengaju());
    }

    public function test_tiket_lama_tanpa_user_id_masih_memakai_kolom_teksnya(): void
    {
        $perbaikan = new PerbaikanData(['pengaju' => 'Nama Lama']);
        $perbaikan->setRelation('user', null);

        $this->assertSame('Nama Lama', $perbaikan->namaPengaju());
    }

    public function test_tanpa_user_dan_tanpa_nama_ditampilkan_sebagai_strip(): void
    {
        $perbaikan = new PerbaikanData(['pengaju' => null]);
        $perbaikan->setRelation('user', null);

        $this->assertSame('-', $perbaikan->namaPengaju());
    }
}
