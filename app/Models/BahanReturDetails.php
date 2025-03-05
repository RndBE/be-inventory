<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanReturDetails extends Model
{
    use HasFactory;

    protected $table = 'bahan_retur_details';
    protected $guarded = [];


    public function bahanRetur()
    {
        return $this->belongsTo(BahanRetur::class);
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function dataProduk()
    {
        return $this->belongsTo(BahanSetengahjadiDetails::class, 'produk_id');
    }
}
