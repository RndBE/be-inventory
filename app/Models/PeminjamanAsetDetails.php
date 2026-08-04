<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PeminjamanAsetDetails extends Model
{
    use HasFactory;

    protected $table = 'peminjaman_aset_details';
    protected $guarded = [];

    public function peminjamanAset()
    {
        return $this->belongsTo(PeminjamanAset::class, 'peminjaman_aset_id');
    }

    public function dataAset()
    {
        return $this->belongsTo(RekapAset::class, 'rekap_aset_id');
    }

    /**
     * Bukti foto pengembalian. Bisa lebih dari satu karena sekali serah terima
     * sering melibatkan banyak aset yang tidak cukup didokumentasikan satu foto.
     */
    public function buktiFoto()
    {
        return $this->hasMany(PeminjamanAsetBukti::class, 'peminjaman_aset_detail_id');
    }
}
