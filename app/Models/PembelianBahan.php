<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembelianBahan extends Model
{
    use HasFactory;

    protected $table = 'pembelian_bahan';
    protected $guarded = [];

    public function dataUser()
    {
        return $this->belongsTo(User::class, 'pengaju');
    }

    public function pembelianBahanDetails()
    {
        return $this->hasMany(PembelianBahanDetails::class, 'pembelian_bahan_id');
    }

    public function produksiS()
    {
        return $this->hasOne(Produksi::class, 'bahan_keluar_id');
    }

    public function dataPengajuan()
    {
        return $this->hasOne(Pengajuan::class, 'id', 'pengajuan_id');
    }

    public function projek()
    {
        return $this->hasOne(Projek::class, 'bahan_keluar_id');
    }

    public function scopeOfJenis($query, array $types)
    {
        return $query->where(function ($q) use ($types) {
            foreach ($types as $type) {
                if (str_ends_with($type, 'Impor')) {
                    $q->orWhere('jenis_pengajuan', 'LIKE', $type . '%');
                } else {
                    $q->orWhere('jenis_pengajuan', $type);
                }
            }
        });
    }

    public function getBaseJenisPengajuanAttribute(): string
    {
        return explode('|', $this->attributes['jenis_pengajuan'] ?? '')[0];
    }

    public function getCurrencyAttribute(): string
    {
        $parts = explode('|', $this->attributes['jenis_pengajuan'] ?? '');
        return $parts[1] ?? 'USD';
    }
}
