<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanRetur extends Model
{
    use HasFactory;

    protected $table = 'bahan_retur';
    protected $guarded = [];

    public function bahanReturDetails()
    {
        return $this->hasMany(BahanReturDetails::class, 'bahan_retur_id');
    }

    public function produksiS()
    {
        return $this->hasOne(Produksi::class, 'bahan_retur_id');
    }

    public function projek()
    {
        return $this->hasOne(Projek::class, 'bahan_retur_id');
    }
}
