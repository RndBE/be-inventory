<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QcDokumentasiProdukSetengahJadi extends Model
{
    protected $table = 'qc_dokumentasi_produk_setengah_jadi';

    protected $guarded = [];

    public function qc1()
    {
        return $this->belongsTo(Qc1ProdukSetengahJadi::class, 'qc1_id');
    }

    public function qc2()
    {
        return $this->belongsTo(Qc2ProdukSetengahJadi::class, 'qc2_id');
    }
}
