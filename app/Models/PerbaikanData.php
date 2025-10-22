<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerbaikanData extends Model
{
    use HasFactory;

    protected $table = 'perbaikan_data';
    protected $guarded = [];

    protected $casts = [
        'tgl_pengajuan' => 'datetime',
    ];

    public function lampiran()
    {
        return $this->hasMany(LampiranPerbaikanData::class, 'perbaikan_data_id');
    }

}
