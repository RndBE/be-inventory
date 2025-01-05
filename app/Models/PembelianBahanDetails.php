<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembelianBahanDetails extends Model
{
    use HasFactory;

    protected $table = 'pembelian_bahan_details';
    protected $guarded = [];


    public function pembelianBahan()
    {
        return $this->belongsTo(PembelianBahan::class);
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }
}
