<?php

namespace App\Services;

use App\Models\ProduksiProdukJadi;
use App\Models\ProduksiProdukJadiDetails;
use App\Models\ProdukSample;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProdukSampleProductionDetailCopier
{
    public function copyMissingDetailsFromSample(ProduksiProdukJadi $produksiProdukJadi): void
    {
        if (!$produksiProdukJadi->produk_sample_id) {
            return;
        }

        if ($produksiProdukJadi->produksiProdukJadiDetails()->exists()) {
            return;
        }

        $produkSample = ProdukSample::with('produkSampleDetails')->find($produksiProdukJadi->produk_sample_id);
        if (!$produkSample) {
            return;
        }

        $hasProdukJadisId = $this->hasColumn('produksi_produk_jadi_details', 'produk_jadis_id');

        foreach ($produkSample->produkSampleDetails as $detail) {
            $attributes = [
                'produksi_produk_jadi_id' => $produksiProdukJadi->id,
                'bahan_id' => $detail->bahan_id,
                'produk_id' => $detail->produk_id,
                'serial_number' => $detail->serial_number,
                'qty' => $detail->qty,
                'used_materials' => $detail->used_materials,
                'details' => $detail->details,
                'sub_total' => $detail->sub_total,
            ];

            if ($hasProdukJadisId) {
                $attributes['produk_jadis_id'] = $detail->produk_jadis_id;
            }

            ProduksiProdukJadiDetails::create($attributes);
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return Schema::hasColumn($table, $column);
        }

        return !empty(DB::select(
            'select column_name from information_schema.columns where table_schema = database() and table_name = ? and column_name = ? limit 1',
            [$table, $column]
        ));
    }
}
