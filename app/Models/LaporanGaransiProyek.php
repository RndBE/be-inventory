<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanGaransiProyek extends Model
{
    protected $table = 'laporan_garansi_proyek';
    protected $guarded = [];

    protected $casts = [
        'tanggal' => 'datetime',
    ];


    public function dataProyek()
    {
        return $this->belongsTo(GaransiProjek::class, 'garansi_projek_id');
    }
}
