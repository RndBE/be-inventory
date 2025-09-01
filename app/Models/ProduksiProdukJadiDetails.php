<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduksiProdukJadiDetails extends Model
{
    use HasFactory;

    protected $table = 'produksi_produk_jadi_details';
    protected $guarded = [];


    public function produksiProdukJadi()
    {
        return $this->belongsTo(ProduksiProdukJadi::class, 'produksi_produk_jadi_id');
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function dataProduk()
    {
        return $this->belongsTo(BahanSetengahjadiDetails::class, 'produk_id');
    }
}
