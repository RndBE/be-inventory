<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SerahTerimaAsetDetails extends Model
{
    use HasFactory;

    protected $table = 'serah_terima_aset_details';
    protected $guarded = [];

    public function serahTerimaAset()
    {
        return $this->belongsTo(SerahTerimaAset::class, 'serah_terima_aset_id');
    }

    public function dataAset()
    {
        return $this->belongsTo(RekapAset::class, 'rekap_aset_id');
    }

    /**
     * Baris peminjaman asalnya, kalau aset ini datang dari pinjaman aktif.
     * Null untuk aset yang jadi tanggung jawab tetap karyawan (PIC).
     */
    public function detailPeminjaman()
    {
        return $this->belongsTo(PeminjamanAsetDetails::class, 'peminjaman_aset_detail_id');
    }
}
