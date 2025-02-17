<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RekapAset extends Model
{
    use HasFactory;
    protected $table = 'rekap_aset';

    protected $guarded = [];

    public function jenisBahan()
    {
        return $this->belongsTo(JenisBahan::class, 'jenis_bahan_id');
    }

    public function dataUnit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function dataUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function barangAset()
    {
        return $this->belongsTo(BarangAset::class, 'barang_aset_id');
    }

    public function dataDivisi()
    {
        return $this->belongsTo(JobPosition::class);
    }
}
