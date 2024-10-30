<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduksiDetails extends Model
{
    use HasFactory;

    protected $table = 'produksi_details';
    protected $guarded = [];


    public function produksis()
    {
        return $this->belongsTo(Produksi::class, 'produksi_id');
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function bahanKeluar()
    {
        return $this->belongsTo(BahanKeluar::class, 'produksi_id', 'id');
    }
}
