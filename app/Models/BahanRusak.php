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

    public function produksiS()
    {
        return $this->hasOne(Produksi::class, 'id', 'produksi_id');
    }

    public function projek()
    {
        return $this->hasOne(Projek::class, 'id', 'projek_id');
    }

    public function produkSample()
    {
        return $this->hasOne(ProdukSample::class, 'id', 'produk_sample_id');
    }

    public function projekRnd()
    {
        return $this->hasOne(ProjekRnd::class, 'id', 'projek_rnd_id');
    }
}
