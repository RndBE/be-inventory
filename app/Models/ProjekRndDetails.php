<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjekRndDetails extends Model
{
    use HasFactory;

    protected $table = 'projek_rnd_details';
    protected $guarded = [];

    protected $fillable = [
        'projek_rnd_id',
        'bahan_id',
        'produk_id',
        'qty',
        'used_materials',
        'keterangan_penanggungjawab',
        'details',
        'sub_total',
        'serial_number',
    ];


    public function projekRnd()
    {
        return $this->belongsTo(ProjekRnd::class, 'projek_rnd_id');
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
