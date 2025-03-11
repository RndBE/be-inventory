<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GaransiProjekDetails extends Model
{
    use HasFactory;

    protected $table = 'garansi_projek_details';
    protected $guarded = [];


    public function garansiProjek()
    {
        return $this->belongsTo(GaransiProjek::class, 'garansi_projek_id');
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
