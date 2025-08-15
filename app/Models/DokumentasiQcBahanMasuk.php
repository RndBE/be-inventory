<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DokumentasiQcBahanMasuk extends Model
{
    use HasFactory;

    protected $table = 'dokumentasi_qc_bahan_masuk';

    protected $guarded = [];

    public function detailQc()
    {
        return $this->belongsTo(QcBahanMasukDetails::class, 'qc_bahan_masuk_detail_id');
    }

    // Relasi ke bahan
    public function bahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }
}
