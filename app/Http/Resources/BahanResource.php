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
            'jenis_bahan' => [
                'id' => $this->jenisBahan->id ?? null,
                'nama' => $this->jenisBahan->nama ?? null,
            ],
            'data_unit' => [
                'id' => $this->dataUnit->id ?? null,
                'nama' => $this->dataUnit->nama ?? null,
            ],
            'data_supplier' => [
                'id' => $this->dataSupplier->id ?? null,
                'nama' => $this->dataSupplier->nama ?? null,
            ]
        ];
    }

}
