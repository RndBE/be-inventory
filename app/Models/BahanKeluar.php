<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanKeluar extends Model
{
    use HasFactory;

    protected $table = 'bahan_keluars';
    protected $guarded = [];

    public function dataUser()
    {
        return $this->belongsTo(User::class, 'pengaju');
    }

    public function bahanKeluarDetails()
    {
        return $this->hasMany(BahanKeluarDetails::class, 'bahan_keluar_id');
    }

    public function produksiS()
    {
        return $this->hasOne(Produksi::class, 'bahan_keluar_id');
    }

    public function projek()
    {
        return $this->hasOne(Projek::class, 'bahan_keluar_id');
    }
}
