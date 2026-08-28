<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BahanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'kode_bahan' => $this->kode_bahan,
            'nama_bahan' => $this->nama_bahan,
            'stok_awal' => $this->stok_awal,
            'penempatan' => $this->penempatan,
            'gambar' => $this->gambar,
            'total_stok' => $this->purchaseDetails->sum('sisa'),
            // Untuk bahan batangan, `total_stok` di atas adalah panjang total
            // dalam cm — bukan jumlah barang. Dua field berikut ada supaya
            // klien bisa menerjemahkannya sendiri: `panjang_standar` null
            // berarti bahan biasa dan `total_stok` dibaca seperti sebelumnya.
            'panjang_standar' => $this->panjang_standar,
            'total_stok_label' => $this->resource->panjang_standar
                ? $this->resource->formatQty($this->purchaseDetails->sum('sisa'))
                : null,
            'jenis_bahan' => [
                'id' => $this->jenisBahan->id ?? null,
                'nama' => $this->jenisBahan->nama ?? null,
            ],
            'data_unit' => [
                'id' => $this->dataUnit->id ?? null,
                'nama' => $this->dataUnit->nama ?? null,
            ],
            'data_supplier' => $this->suppliers->map(function($supplier) {
                return [
                    'id' => $supplier->id,
                    'nama' => $supplier->nama,
                ];
            }),
        ];
    }

}
