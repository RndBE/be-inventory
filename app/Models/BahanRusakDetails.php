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
}
