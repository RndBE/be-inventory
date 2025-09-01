<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Qc1ProdukJadi extends Model
{
    use HasFactory;

    protected $table = 'qc_1_produk_jadi';

    protected $guarded = [];

    // Relasi balik ke list produk
    public function list()
    {
        return $this->belongsTo(QcProdukJadiList::class, 'id_produk_jadi_list');
    }
    public function dokumentasi()
    {
        return $this->hasMany(QcDokumentasiProdukJadi::class, 'qc1_id');
    }

}
