<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduksiProdukJadi extends Model
{
    use HasFactory;

    protected $table = 'produksi_produk_jadi';
    protected $guarded = [];

    protected $casts = [
        'mulai_projek' => 'datetime',
        'selesai_projek' => 'datetime',
    ];

    public function dataProdukJadi()
    {
        return $this->belongsTo(ProdukJadi::class, 'produk_jadi_id');
    }

    public function produksiProdukJadiDetails()
    {
        return $this->hasMany(ProduksiProdukJadiDetails::class, 'produksi_produk_jadi_id', 'id');
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

    public function dataBahanRusak()
    {
        return $this->hasMany(BahanRusak::class, 'produksi_produk_jadi_id', 'id');
    }

}
