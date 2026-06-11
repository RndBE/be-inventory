<?php

namespace Tests\Feature;

use Tests\TestCase;

class PengajuanPembelianUsdDetailMarkupTest extends TestCase
{
    public function test_pengajuan_detail_component_exposes_usd_cost_fields(): void
    {
        $source = file_get_contents(app_path('Livewire/PengajuanPembelianTable.php'));

        foreach ([
            'shipping_cost_usd',
            'full_amount_fee_usd',
            'value_today_fee_usd',
            'new_shipping_cost_usd',
            'new_full_amount_fee_usd',
            'new_value_today_fee_usd',
        ] as $field) {
            $this->assertStringContainsString('$' . $field, $source);
            $this->assertStringContainsString('$this->' . $field . ' = $Data->' . $field, $source);
        }
    }

    public function test_pengajuan_detail_sidebar_renders_usd_totals_for_import_requests(): void
    {
        $view = file_get_contents(resource_path('views/pages/pengajuan-pembelian/sidebar-detail.blade.php'));

        $this->assertStringContainsString('Total Harga (USD)', $view);
        $this->assertStringContainsString('$finalTotalUSD', $view);
        $this->assertStringContainsString('unit_price_usd', $view);
        $this->assertStringContainsString('unit_price_usd_aset', $view);
        $this->assertStringContainsString('shipping_cost_usd', $view);
    }

    public function test_pengajuan_edit_form_supports_usd_import_prices(): void
    {
        $component = file_get_contents(app_path('Livewire/EditPengajuanPembelianCart.php'));
        $view = file_get_contents(resource_path('views/livewire/edit-pengajuan-pembelian-cart.blade.php'));

        $this->assertStringContainsString('$currency = \'USD\'', $component);
        $this->assertStringContainsString('explode(\'|\'', $component);
        $this->assertStringContainsString('$this->jenis_pengajuan = $parts[0]', $component);
        $this->assertStringContainsString('unit_price_usd_aset', $component);
        $this->assertStringContainsString('function formatToUSDPriceAset', $component);
        $this->assertStringContainsString('function editItemPriceImporAset', $component);
        $this->assertStringContainsString('function sanitizeKey', $component);
        $this->assertStringContainsString('details_usd', $component);
        $this->assertStringContainsString('\'currency\' => $this->currency', $component);

        $this->assertStringContainsString('$unitPriceUSD = $unit_price_usd[$idBahan]', $view);
        $this->assertStringContainsString('$unitPriceUSD = $unit_price_usd_aset[$safeKey]', $view);
        $this->assertStringContainsString('unit_price_usd_aset', $view);
        $this->assertStringContainsString('Total Anggaran (USD)', $view);
    }
}
