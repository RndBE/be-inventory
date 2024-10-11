<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdukProduksiDetail extends Model
{
    use HasFactory;
    protected $table = 'produk_produksi_details';
    protected $guarded = [];

    public function produkProduksi()
    {
        return $this->belongsTo(ProdukProduksi::class, 'produk_produksis_id');
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }
}
