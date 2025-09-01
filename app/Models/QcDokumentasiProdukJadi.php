<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QcDokumentasiProdukJadi extends Model
{
    protected $table = 'qc_dokumentasi_produk_jadi';

    protected $guarded = [];

    public function qc1()
    {
        return $this->belongsTo(Qc1ProdukJadi::class, 'qc1_id');
    }

    public function qc2()
    {
        return $this->belongsTo(Qc2ProdukJadi::class, 'qc2_id');
    }
}
