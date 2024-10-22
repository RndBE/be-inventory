<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanSetengahjadiDetails extends Model
{
    use HasFactory;

    protected $table = 'bahan_setengahjadi_details';
    protected $guarded = [];

    public function bahanSetengahjadi()
    {
        return $this->belongsTo(BahanSetengahjadi::class);
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function dataUnit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
