<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjekDetails extends Model
{
    use HasFactory;

    protected $table = 'projek_details';
    protected $guarded = [];


    public function projek()
    {
        return $this->belongsTo(Projek::class, 'projek_id');
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }
}
