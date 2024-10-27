<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanJadi extends Model
{
    use HasFactory;
    protected $table = 'bahan_jadis';
    protected $guarded = [];

    public function bahanJadiDetails()
    {
        return $this->hasMany(BahanJadiDetails::class);
    }
}
