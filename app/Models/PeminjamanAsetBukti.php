<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Satu berkas bukti foto pengembalian.
 *
 * Path yang sama bisa dirujuk beberapa detail sekaligus — satu foto yang memuat
 * banyak aset dipakai bersama oleh semua aset yang dicatat dalam sekali proses.
 * Karena itu penghapusan berkas fisiknya harus selalu mengecek apakah masih ada
 * baris lain yang merujuk path tersebut.
 */
class PeminjamanAsetBukti extends Model
{
    use HasFactory;

    protected $table = 'peminjaman_aset_bukti';
    protected $guarded = [];

    public function detailPeminjaman()
    {
        return $this->belongsTo(PeminjamanAsetDetails::class, 'peminjaman_aset_detail_id');
    }

    /**
     * Alamat untuk menampilkan fotonya.
     *
     * Lewat route ber-otorisasi, bukan asset('storage/…'): berkasnya tidak lagi
     * di dalam public/. Disediakan sebagai accessor supaya semua view merujuk
     * satu tempat — kalau nanti pindah ke S3 atau URL bertanda tangan, yang
     * berubah cuma method ini.
     */
    public function getUrlAttribute(): string
    {
        return route('bukti-aset.peminjaman', $this->id);
    }
}
