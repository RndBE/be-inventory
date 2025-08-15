<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilQcBahanMasuk extends Model
{
    use HasFactory;

    protected $table = 'hasil_qc_bahan_masuk';
    protected $primaryKey = 'id_qc_bahan_masuk';

    protected $casts = [
        'tanggal_qc' => 'datetime', // Ini yang penting!
        // Jika ada kolom tanggal lain, bisa ditambahkan di sini juga
        // 'created_at' => 'datetime',
        // 'updated_at' => 'datetime',
    ];

    protected $guarded = [];

    // Relasi ke Detail
    public function details()
    {
        return $this->hasMany(HasilQcBahanMasukDetails::class, 'id_qc_bahan_masuk', 'id_qc_bahan_masuk');
    }

    // Relasi ke Pembelian Bahan
    public function pembelianBahan()
    {
        return $this->belongsTo(PembelianBahan::class, 'id_pembelian_bahan');
    }

    // Relasi ke User sebagai petugas QC
    public function petugasQc()
    {
        return $this->belongsTo(User::class, 'id_petugas_qc');
    }

    // Relasi ke User sebagai input data QC
    public function petugasInputQc()
    {
        return $this->belongsTo(User::class, 'id_petugas_input_qc');
    }
}
