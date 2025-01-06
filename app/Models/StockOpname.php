<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    use HasFactory;
    protected $table = 'stock_opname';
    protected $guarded = [];

    public function stockOpnameDetails()
    {
        return $this->hasMany(StockOpnameDetails::class, 'stock_opname_id');
    }

    public function pengajuUser()
    {
        return $this->belongsTo(User::class, 'pengaju');
    }

}
