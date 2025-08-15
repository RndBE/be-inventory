<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilQcBahanMasukDetails extends Model
{
    use HasFactory;

    protected $table = 'hasil_qc_bahan_masuk_details';

    protected $guarded = [];

    // Relasi ke header QC
    public function qc()
    {
        return $this->belongsTo(HasilQcBahanMasuk::class, 'id_qc_bahan_masuk', 'id_qc_bahan_masuk');
    }

    // Relasi ke Bahan
    public function bahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    // Relasi ke Supplier
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
