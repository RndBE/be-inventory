<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QcProdukSetengahJadiList extends Model
{
    use HasFactory;

    protected $table = 'qc_produk_setengah_jadi_list';
    protected $casts = [
        'tanggal_masuk_gudang' => 'datetime',
        // 'created_at' => 'datetime',
        // 'updated_at' => 'datetime',
    ];

    protected $guarded = [];

    // Relasi ke Produksi
    public function produksi()
    {
        return $this->belongsTo(Produksi::class, 'produksi_id');
    }

    // Relasi ke QC tahap 1
    public function qc1()
    {
        return $this->hasOne(Qc1ProdukSetengahJadi::class, 'id_produk_setengah_jadi_list');
    }

    // Relasi ke QC tahap 2
    public function qc2()
    {
        return $this->hasOne(Qc2ProdukSetengahJadi::class, 'id_produk_setengah_jadi_list');
    }
}
