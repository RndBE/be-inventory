<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produksi extends Model
{
    use HasFactory;

    protected $table = 'produksis';
    protected $guarded = [];

    public function produksiDetails()
    {
        return $this->hasMany(ProduksiDetails::class, 'produksi_id', 'id');
    }

    public function bahanKeluar()
    {
        return $this->hasMany(BahanKeluar::class, 'produksi_id', 'id');
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

    public function dataBahanRusak()
    {
        return $this->hasMany(BahanRusak::class, 'produksi_id', 'id');
    }

    public function dataBahanRetur()
    {
        return $this->hasMany(BahanRetur::class, 'produksi_id', 'id');
    }
}
