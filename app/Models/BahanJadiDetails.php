<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanJadiDetails extends Model
{
    use HasFactory;

    protected $table = 'bahan_jadi_details';
    protected $guarded = [];

    public function bahanJadi()
    {
        return $this->belongsTo(BahanJadi::class);
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
