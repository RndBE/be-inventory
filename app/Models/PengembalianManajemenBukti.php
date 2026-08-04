<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Bukti foto satu kali serah terima aset ke manajemen.
 *
 * Tabel tersendiri, bukan satu kolom teks: satu kali serah terima bisa mencakup
 * beberapa aset dan biasanya butuh lebih dari satu foto.
 */
class PengembalianManajemenBukti extends Model
{
    use HasFactory;

    protected $table = 'pengembalian_manajemen_bukti';
    protected $guarded = [];

    public function pengembalian()
    {
        return $this->belongsTo(PengembalianManajemen::class, 'pengembalian_manajemen_id');
    }

    /**
     * Alamat untuk menampilkan fotonya.
     *
     * Lewat route ber-otorisasi, bukan asset('storage/…'): berkasnya tidak lagi
     * di dalam public/. Lihat catatan yang sama di PeminjamanAsetBukti.
     */
    public function getUrlAttribute(): string
    {
        return route('bukti-aset.manajemen', $this->id);
    }
}
