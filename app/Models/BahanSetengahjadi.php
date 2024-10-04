<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanSetengahjadi extends Model
{
    use HasFactory;

    protected $table = 'bahan_setengahjadis';
    protected $guarded = [];

    public function bahanSetengahjadiDetails()
    {
        return $this->hasMany(BahanSetengahjadiDetails::class);
    }
}
