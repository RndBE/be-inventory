<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjekRnd extends Model
{
    use HasFactory;

    protected $table = 'projek_rnd';
    protected $guarded = [];

    public function projekRndDetails()
    {
        return $this->hasMany(ProjekRndDetails::class, 'projek_rnd_id', 'id');
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
