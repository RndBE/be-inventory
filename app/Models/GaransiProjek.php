<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GaransiProjek extends Model
{
    use HasFactory;

    protected $table = 'garansi_projek';
    protected $guarded = [];

    protected $casts = [
        'mulai_garansi' => 'datetime',
        'selesai_garansi' => 'datetime',
    ];

    public function dataKontrak()
    {
        return $this->belongsTo(Kontrak::class, 'kontrak_id');
    }

    public function garansiProjekDetails()
    {
        return $this->hasMany(GaransiProjekDetails::class, 'garansi_projek_id', 'id');
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
