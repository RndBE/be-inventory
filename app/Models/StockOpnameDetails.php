<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpnameDetails extends Model
{
    use HasFactory;
    protected $table = 'stock_opname_details';
    protected $guarded = [];

    public function stockOpname()
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function dataProduk()
    {
        return $this->belongsTo(BahanSetengahjadiDetails::class, 'produk_id');
    }

}
