<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembelianBahan extends Model
{
    use HasFactory;

    protected $table = 'pembelian_bahan';
    protected $guarded = [];

    public function dataUser()
    {
        return $this->belongsTo(User::class, 'pengaju');
    }

    public function pembelianBahanDetails()
    {
        return $this->hasMany(PembelianBahanDetails::class, 'pembelian_bahan_id');
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
