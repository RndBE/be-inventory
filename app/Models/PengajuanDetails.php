<?php

namespace App\Models;

use App\Models\Pengajuan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PengajuanDetails extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_details';
    protected $guarded = [];

    protected $casts = [
        'mulai_pengajuan' => 'datetime',
        'selesai_pengajuan' => 'datetime',
    ];

    public function pengajuan()
    {
        return $this->belongsTo(Pengajuan::class, 'pengajuan_id');
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }
}
