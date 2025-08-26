<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanSetengahjadi extends Model
{
    use HasFactory;

    protected $table = 'bahan_setengahjadis';
    protected $guarded = [];

    public function bahanSetengahjadiDetails()
    {
        return $this->hasMany(BahanSetengahjadiDetails::class);
    }

    public function produksiS()
    {
        return $this->hasOne(Produksi::class, 'id', 'produksi_id');
    }

    public function produksiDetails()
    {
        return $this->hasManyThrough(
            ProduksiDetails::class,   // Model tujuan
            Produksi::class,          // Model perantara
            'id',                     // Foreign key di Produksi (id produksi di bahan_setengahjadis)
            'produksi_id',            // Foreign key di ProduksiDetails
            'produksi_id',            // Local key di BahanSetengahjadi
            'id'                      // Local key di Produksi
        );
    }

    public function projekRndDetails()
    {
        return $this->hasManyThrough(
            ProjekRndDetails::class,  // Model tujuan
            ProjekRnd::class,         // Model perantara
            'id',                     // Foreign key di ProjekRnd (id projek di bahan_setengahjadis)
            'projek_rnd_id',          // Foreign key di ProjekRndDetails
            'projek_rnd_id',          // Local key di BahanSetengahjadi
            'id'                      // Local key di ProjekRnd
        );
    }

    public function qcProdukSetengaJadi()
    {
        return $this->belongsTo(QcProdukSetengahJadiList::class, 'id_qc_produk_setengahjadi', 'id');
    }

}
