<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdukJadiDetails extends Model
{
    use HasFactory;

    protected $table = 'produk_jadi_details';
    protected $guarded = [];

    public function ProdukJadis()
    {
        return $this->belongsTo(ProdukJadis::class);
    }

    public function dataProduk()
    {
        return $this->belongsTo(ProdukJadi::class, 'produk_jadi_id');
    }

    public function dataUnit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    // public function dataProdukProduksi()
    // {
    //     return $this->belongsTo(ProdukProduksi::class, 'produk_id');
    // }
}
