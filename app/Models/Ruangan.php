<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ruangan extends Model
{
    use HasFactory;
    protected $table = 'ruangan';

    protected $guarded = [];

    public function rekapAsets()
    {
        return $this->hasMany(RekapAset::class, 'ruangan_id');
    }
}
