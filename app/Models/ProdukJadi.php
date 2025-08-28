<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProdukJadi extends Model
{
    use HasFactory;
    protected $table = 'produk_jadi';
    protected $guarded = [];
}
