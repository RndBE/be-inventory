<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LampiranPerbaikanData extends Model
{
    use HasFactory;

    protected $table = 'lampiran_perbaikan_data';
    protected $guarded = [];

    public function perbaikanData()
    {
        return $this->belongsTo(PerbaikanData::class, 'perbaikan_data_id');
    }

}
