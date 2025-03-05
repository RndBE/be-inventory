<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanKeluarDetails extends Model
{
    use HasFactory;

    protected $table = 'bahan_keluar_details';
    protected $guarded = [];


    public function bahanKeluar()
    {
        return $this->belongsTo(BahanKeluar::class);
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
