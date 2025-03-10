<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Projek extends Model
{
    use HasFactory;

    protected $table = 'projek';
    protected $guarded = [];

    protected $casts = [
        'mulai_projek' => 'datetime',
        'selesai_projek' => 'datetime',
    ];

    public function dataKontrak()
    {
        return $this->belongsTo(Kontrak::class, 'kontrak_id');
    }

    public function projekDetails()
    {
        return $this->hasMany(ProjekDetails::class, 'projek_id', 'id');
    }

    public function bahanKeluar()
    {
        return $this->belongsTo(BahanKeluar::class, 'bahan_keluar_id');
    }

    public function dataProdukProduksi()
    {
        return $this->belongsTo(ProdukProduksi::class, 'produk_id');
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function dataUnit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function dataBahanRusak()
    {
        return $this->hasMany(BahanRusak::class, 'projek_id', 'id');
    }

    public function laporanProyek()
    {
        return $this->hasMany(LaporanProyek::class, 'projek_id');
    }

}
