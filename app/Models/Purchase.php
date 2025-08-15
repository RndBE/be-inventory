<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $table = 'purchases';
    protected $guarded = [];

    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class);
    }

    public function qcBahanMasuk()
    {
        return $this->belongsTo(QcBahanMasuk::class, 'id_qc_bahan_masuk', 'id_qc_bahan_masuk');
    }

}
