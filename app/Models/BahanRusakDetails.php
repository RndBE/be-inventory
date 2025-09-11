<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanRusakDetails extends Model
{
    use HasFactory;

    protected $table = 'bahan_rusak_details';
    protected $guarded = [];

    public function bahanRusak()
    {
        return $this->belongsTo(BahanRusak::class);
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }
    public function dataProduk()
    {
        return $this->belongsTo(BahanSetengahjadiDetails::class, 'produk_id');
    }

    public function dataProdukJadi()
    {
        return $this->belongsTo(ProdukJadiDetails::class, 'produk_jadis_id');
    }
}
