<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanKeluarDetails extends Model
{
    use HasFactory;

    protected $table = 'bahan_keluar_details';
    protected $guarded = [];


    public function bahanKeluar()
    {
        return $this->belongsTo(BahanKeluar::class);
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }
    public function dataProduk()
    {
        return $this->belongsTo(BahanSetengahjadiDetails::class, 'produk_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'kode_transaksi', 'kode_transaksi');
    }

    public function bahanSetengahJadi()
    {
        return $this->belongsTo(BahanSetengahjadi::class, 'kode_transaksi', 'kode_transaksi');
    }

    public function dataProdukJadi()
    {
        return $this->belongsTo(ProdukJadiDetails::class, 'produk_jadis_id');
    }

}
