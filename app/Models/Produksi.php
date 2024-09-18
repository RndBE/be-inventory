<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produksi extends Model
{
    use HasFactory;

    protected $table = 'produksis';
    protected $guarded = [];

    public function produksisDetails()
    {
        return $this->hasMany(DetailProduksi::class);
    }
}
