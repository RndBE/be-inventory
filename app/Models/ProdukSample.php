<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdukSample extends Model
{
    use HasFactory;

    protected $table = 'produk_sample';
    protected $guarded = [];

    protected $casts = [
        'mulai_produk_sample' => 'datetime',
        'selesai_produk_sample' => 'datetime',
    ];

    public function produkSampleDetails()
    {
        return $this->hasMany(ProdukSampleDetails::class, 'produk_sample_id', 'id');
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
        return $this->hasMany(BahanRusak::class, 'produk_sample_id', 'id');
    }

    public function laporanProyek()
    {
        return $this->hasMany(LaporanProyek::class, 'produk_sample_id');
    }

}
