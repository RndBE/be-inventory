<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Berita Acara Serah Terima Aset — dokumen offboarding karyawan.
 *
 * Tidak memakai rantai persetujuan. Dokumennya dicetak dengan kotak tanda tangan
 * kosong lalu ditandatangani basah saat serah terima berlangsung. Status hanya
 * dua: Draft dan Selesai — dan "Selesai" itulah yang melepas aset dari karyawan
 * serta menonaktifkan akunnya, jadi harus ditekan sengaja, bukan otomatis.
 */
class SerahTerimaAset extends Model
{
    use HasFactory;

    protected $table = 'serah_terima_aset';
    protected $guarded = [];

    public function serahTerimaAsetDetails()
    {
        return $this->hasMany(SerahTerimaAsetDetails::class, 'serah_terima_aset_id');
    }

    public function dataKaryawan()
    {
        return $this->belongsTo(User::class, 'karyawan_id');
    }

    public function dataPengaju()
    {
        return $this->belongsTo(User::class, 'pengaju');
    }

    public function dataAtasan()
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    /**
     * Pejabat yang namanya dicetak di blok tanda tangan. Keduanya dikunci saat
     * BAST dibuat, bukan dicari ulang saat cetak — kalau diambil dari role saat
     * itu, cetak ulang dokumen lama bisa memunculkan nama pejabat pengganti,
     * bukan nama yang tanda tangan basah di kertasnya.
     */
    public function dataGa()
    {
        return $this->belongsTo(User::class, 'ga_id');
    }

    public function dataHrd()
    {
        return $this->belongsTo(User::class, 'hrd_id');
    }

    /**
     * Semua tahap sudah menyetujui. Ini gerbang tunggal untuk unduh PDF dan
     * untuk pembersihan data aset.
     */
    public function getSelesaiAttribute(): bool
    {
        return $this->status === 'Selesai';
    }

    public function scopeSelesai($query)
    {
        return $query->where('status', 'Selesai');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'Draft');
    }

    public function dataPenyelesai()
    {
        return $this->belongsTo(User::class, 'diselesaikan_oleh');
    }

    /**
     * Aset yang masih dipegang karyawan saat BAST dibuat — inilah yang benar-benar
     * diserahkan dan yang diproses saat dokumen tuntas.
     */
    public function getAsetDiserahkanAttribute()
    {
        return $this->serahTerimaAsetDetails->where('status_pegang', 'Dipegang');
    }

    /**
     * Aset yang sudah dikembalikan karyawan sebelum BAST dibuat. Dicantumkan
     * sebagai keterangan supaya dokumennya jadi rekening lengkap, bukan cuma sisa.
     */
    public function getAsetSudahKembaliAttribute()
    {
        return $this->serahTerimaAsetDetails->where('status_pegang', 'Sudah kembali');
    }

    /**
     * Tidak ada lagi yang perlu diserahkan — dipakai HR sebagai surat keterangan
     * bebas aset, syarat pencairan hak terakhir karyawan.
     *
     * Patokannya aset yang masih dipegang, bukan jumlah baris: karyawan yang sudah
     * mengembalikan semuanya lebih dulu tetap berhak atas surat ini.
     */
    public function getBebasAsetAttribute(): bool
    {
        return $this->aset_diserahkan->isEmpty();
    }

}
