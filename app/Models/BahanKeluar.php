<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanKeluar extends Model
{
    use HasFactory;

    protected $table = 'bahan_keluars';
    protected $guarded = [];

    public function bahanKeluarDetails()
    {
        return $this->hasMany(BahanKeluarDetails::class);
    }
}
