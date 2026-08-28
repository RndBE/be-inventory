<?php

namespace App\Models;

use App\Models\Pengajuan;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\MenampilkanQtyBahan;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PengambilanBahanDetails extends Model
{
    use HasFactory;
    use MenampilkanQtyBahan;

    protected $table = 'pengambilan_bahan_details';
    protected $guarded = [];

    protected $casts = [
        'mulai_pengajuan' => 'datetime',
        'selesai_pengajuan' => 'datetime',
    ];

    public function pengambilanBahan()
    {
        return $this->belongsTo(PengambilanBahan::class, 'pengambilan_bahan_id');
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }
}
