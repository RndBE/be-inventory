<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Qc2ProdukSetengahJadi extends Model
{
    use HasFactory;

    protected $table = 'qc_2_produk_setengah_jadi';

    protected $guarded = [];

    // Relasi balik ke list produk
    public function list()
    {
        return $this->belongsTo(QcProdukSetengahJadiList::class, 'id_produk_setengah_jadi_list');
    }
    public function dokumentasi()
    {
        return $this->hasMany(QcDokumentasiProdukSetengahJadi::class, 'qc2_id');
    }

}
