<?php

namespace Tests\Unit;

use App\Livewire\Quality\QcWizard;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Harga acuan yang mengisi kolom harga di QC Bahan Masuk.
 *
 * Kolom itu sebelumnya selalu dikosongkan, sehingga petugas mengetik ulang harga
 * yang sudah disetujui di pengajuan pembelian. Angka hasil ketikan itulah yang
 * disalin ke lot oleh prosesKeGudang dan jadi nilai persediaan, jadi salah ketik
 * di sini baru ketahuan jauh di hilir.
 *
 * Yang diperiksa di sini aturan pemilihan angkanya: harga revisi didahulukan
 * atas harga awal, dan pengajuan yang hanya berisi harga dolar tidak boleh
 * jatuh ke nol — nol akan terbaca sebagai harga yang memang sudah disetujui.
 *
 * Sengaja tanpa database: method-nya murni membaca dua kolom JSON, jadi cukup
 * objek tiruan yang membawa kedua kolom itu.
 */
class QcWizardHargaPengajuanTest extends TestCase
{
    private function harga(?string $details, ?string $newDetails): ?float
    {
        $wizard = (new ReflectionClass(QcWizard::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(QcWizard::class, 'hargaPembelianDisetujui');
        $method->setAccessible(true);

        $detail = new class($details, $newDetails)
        {
            public $details;

            public $new_details;

            public function __construct($details, $newDetails)
            {
                $this->details = $details;
                $this->new_details = $newDetails;
            }
        };

        return $method->invoke($wizard, $detail);
    }

    #[Test]
    public function harga_revisi_didahulukan_atas_harga_awal(): void
    {
        $this->assertSame(1500.0, $this->harga('{"unit_price":1000}', '{"new_unit_price":1500}'));
    }

    #[Test]
    public function harga_awal_dipakai_kalau_belum_pernah_direvisi(): void
    {
        $this->assertSame(1000.0, $this->harga('{"unit_price":1000}', null));
    }

    #[Test]
    public function revisi_nol_bukan_harga_yang_disetujui(): void
    {
        // Form Update Harga menulis 0 untuk baris yang tidak diisi, jadi angka
        // itu tidak boleh menggantikan harga awal yang sudah ada.
        $this->assertSame(1000.0, $this->harga('{"unit_price":1000}', '{"new_unit_price":0}'));
    }

    #[Test]
    public function pengajuan_hanya_dolar_tidak_punya_acuan_rupiah(): void
    {
        $this->assertNull($this->harga('{"unit_price_usd":50}', '{"new_unit_price_usd":60}'));
    }

    #[Test]
    public function kolom_kosong_atau_rusak_tidak_menghasilkan_angka(): void
    {
        $this->assertNull($this->harga(null, null));
        $this->assertNull($this->harga('bukan json', 'bukan json'));
    }

    #[Test]
    public function pecahan_harga_dipertahankan(): void
    {
        $this->assertSame(1250.75, $this->harga('{"unit_price":1250.75}', null));
    }
}
