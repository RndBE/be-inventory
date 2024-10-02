<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanRusak extends Model
{
    use HasFactory;

    protected $table = 'bahan_rusaks';
    protected $guarded = [];

    public function bahanRusakDetails()
    {
        return $this->hasMany(BahanRusakDetails::class);
    }
}
