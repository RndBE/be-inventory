<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengambilanBahan extends Model
{
    use HasFactory;

    protected $table = 'pengambilan_bahan';
    protected $guarded = [];

    public function pengambilanBahanDetails()
    {
        return $this->hasMany(PengambilanBahanDetails::class, 'pengambilan_bahan_id', 'id');
    }

    public function bahanKeluar()
    {
        return $this->belongsTo(BahanKeluar::class, 'bahan_keluar_id');
    }

    public function dataProdukProduksi()
    {
        return $this->belongsTo(ProdukProduksi::class, 'produk_id');
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function dataUnit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
