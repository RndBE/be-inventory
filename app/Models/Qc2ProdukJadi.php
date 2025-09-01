<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Qc2ProdukJadi extends Model
{
    use HasFactory;

    protected $table = 'qc_2_produk_jadi';

    protected $guarded = [];

    // Relasi balik ke list produk
    public function list()
    {
        return $this->belongsTo(QcProdukJadiList::class, 'id_produk_jadi_list');
    }
    public function dokumentasi()
    {
        return $this->hasMany(QcDokumentasiProdukJadi::class, 'qc2_id');
    }

}
