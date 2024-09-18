<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailProduksi extends Model
{
    use HasFactory;

    protected $table = 'detail_produksis';
    protected $guarded = [];


    public function produksi()
    {
        return $this->belongsTo(Produksi::class);
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }
}
