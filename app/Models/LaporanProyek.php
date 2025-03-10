<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanProyek extends Model
{
    protected $table = 'laporan_proyek';
    protected $guarded = [];

    protected $casts = [
        'tanggal' => 'datetime',
    ];


    public function dataProyek()
    {
        return $this->belongsTo(Projek::class, 'projek_id');
    }
}
