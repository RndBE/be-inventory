<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanKeluarDetails extends Model
{
    use HasFactory;

    protected $table = 'bahan_keluar_details';
    // protected $guarded = [];

    protected $fillable = [
        'bahan_keluar_id',
        'bahan_id',
        'produk_id',
        'serial_number',
        'qty',
        'jml_bahan',
        'used_materials',
        'details',
        'unit_price',
        'sub_total'
    ];

    protected $casts = [
        'details' => 'array',
        'used_materials' => 'array',
    ];

    public function bahanKeluar()
    {
        return $this->belongsTo(BahanKeluar::class, 'bahan_keluar_id');
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

    public function projekRndDetails()
    {
        return $this->hasOne(ProjekRndDetails::class, 'bahan_id', 'bahan_id');
    }

    // public function projekRndDetailsAktif()
    // {
    //     return $this->hasOne(ProjekRndDetails::class, 'bahan_id', 'bahan_id')
    //         ->where('projek_rnd_details.projek_rnd_id', function ($q) {
    //             $q->select('projek_rnd_id')
    //                 ->from('bahan_keluars')
    //                 ->whereColumn('bahan_keluars.id', 'bahan_keluar_details.bahan_keluar_id')
    //                 ->limit(1);
    //         });
    // }
}
