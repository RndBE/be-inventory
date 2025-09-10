<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QcProdukJadiList extends Model
{
    use HasFactory;

    protected $table = 'qc_produk_jadi_list';
    protected $casts = [
        'tanggal_masuk_gudang' => 'datetime',
        // 'created_at' => 'datetime',
        // 'updated_at' => 'datetime',
    ];

    protected $guarded = [];

    // Relasi ke Produksi produk jadi
    public function produksiProdukJadi()
    {
        return $this->belongsTo(ProduksiProdukJadi::class, 'produksi_produk_jadi_id');
    }

    public function produkJadi()
    {
        return $this->belongsTo(ProdukJadi::class, 'produk_jadi_id');
    }

    // Relasi ke QC tahap 1
    public function qc1()
    {
        return $this->hasOne(Qc1ProdukJadi::class, 'id_produk_jadi_list');
    }

    // Relasi ke QC tahap 2
    public function qc2()
    {
        return $this->hasOne(Qc2ProdukJadi::class, 'id_produk_jadi_list');
    }
}
