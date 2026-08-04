<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu kali serah terima aset ber-PIC kembali ke manajemen.
 *
 * Menyimpan bagian yang dibagi bersama seluruh aset dalam satu kali pencatatan:
 * tanggal, kondisi, catatan, dan bukti fotonya. Daftar asetnya tidak disimpan di
 * sini — baris RiwayatMutasiAset yang merujuk ke sini sudah menjadi rinciannya
 * sekaligus menyimpan PIC & ruangan asal masing-masing aset.
 */
class PengembalianManajemen extends Model
{
    use HasFactory;

    protected $table = 'pengembalian_manajemen';
    protected $guarded = [];

    protected $casts = [
        'tgl_kembali' => 'date',
    ];

    public function buktiFoto()
    {
        return $this->hasMany(PengembalianManajemenBukti::class, 'pengembalian_manajemen_id');
    }

    /**
     * Baris perpindahan yang terjadi karena pencatatan ini — dua per aset,
     * masing-masing untuk PIC dan ruangan.
     */
    public function riwayatMutasi()
    {
        return $this->hasMany(RiwayatMutasiAset::class, 'pengembalian_manajemen_id');
    }

    public function dataPicSebelum()
    {
        return $this->belongsTo(User::class, 'pic_sebelum_id');
    }

    public function pencatat()
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    /**
     * Aset yang tercakup, tanpa duplikasi. Satu aset menghasilkan dua baris
     * mutasi (PIC dan Ruangan), jadi dihitung dari rekap_aset_id yang unik.
     */
    public function getJumlahAsetAttribute(): int
    {
        return $this->riwayatMutasi->pluck('rekap_aset_id')->unique()->count();
    }
}
