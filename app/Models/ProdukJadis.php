<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdukJadis extends Model
{
    use HasFactory;

    protected $table = 'produk_jadis';
    protected $guarded = [];

    public function ProdukJadiDetails()
    {
        return $this->hasMany(ProdukJadiDetails::class);
    }

    public function produksiProdukJadi()
    {
        return $this->hasOne(ProduksiProdukjadi::class, 'id', 'produksi_produk_jadi_id');
    }

    public function produksiProdukJadiDetails()
    {
        return $this->hasManyThrough(
            ProduksiProdukJadiDetails::class,   // Model tujuan
            ProduksiProdukJadi::class,          // Model perantara
            'id',                     // Foreign key di Produksi (id produksi di produk_jadis)
            'produksi_produk_jadi_id',            // Foreign key di ProduksiProdukJadiDetails
            'produksi_produk_jadi_id',            // Local key di BahanSetengahjadi
            'id'                      // Local key di Produksi
        );
    }

    public function projekRndDetails()
    {
        return $this->hasManyThrough(
            ProjekRndDetails::class,  // Model tujuan
            ProjekRnd::class,         // Model perantara
            'id',                     // Foreign key di ProjekRnd (id projek di produk_jadis)
            'projek_rnd_id',          // Foreign key di ProjekRndDetails
            'projek_rnd_id',          // Local key di BahanSetengahjadi
            'id'                      // Local key di ProjekRnd
        );
    }

    public function qcProdukJadi()
    {
        return $this->belongsTo(QcProdukJadiList::class, 'id_qc_produk_jadi', 'id');
    }

}
