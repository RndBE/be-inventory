<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdukSample extends Model
{
    use HasFactory;

    protected $table = 'produk_sample';
    protected $guarded = [];

    public const KATEGORI_NON_RND = 'Non-RnD';
    public const KATEGORI_RND = 'RnD';

    public const KATEGORI_PENGAJUAN = [
        self::KATEGORI_NON_RND,
        self::KATEGORI_RND,
    ];

    protected $casts = [
        'mulai_produk_sample' => 'datetime',
        'selesai_produk_sample' => 'datetime',
    ];

    /**
     * Kategori efektif produk sample. Baris lama bernilai null dan tetap
     * diperlakukan sebagai Non-RnD supaya rute approval-nya tidak berubah.
     */
    public function kategoriPengajuan(): string
    {
        return in_array($this->kategori_pengajuan, self::KATEGORI_PENGAJUAN, true)
            ? $this->kategori_pengajuan
            : self::KATEGORI_NON_RND;
    }

    /**
     * Kategori di sini berlaku untuk pengajuan Bahan Keluar berikutnya, jadi
     * boleh diganti kapan saja selama produk sample belum selesai. Pengajuan
     * yang sudah dibuat tidak ikut bergeser: tiap baris bahan_keluars membawa
     * salinan kategorinya sendiri dan approver-nya dibaca dari situ.
     *
     * Satu produk sample karena itu bisa berisi campuran — misalnya pengajuan
     * lama lewat Leader dan pengajuan baru lewat Manager.
     */
    public function kategoriMasihBisaDiubah(): bool
    {
        return $this->status !== 'Selesai';
    }

    public function produkSampleDetails()
    {
        return $this->hasMany(ProdukSampleDetails::class, 'produk_sample_id', 'id');
    }

    public function bahanKeluar()
    {
        return $this->hasMany(BahanKeluar::class, 'produk_sample_id', 'id');
    }

    public function dataProdukProduksi()
    {
        return $this->belongsTo(ProdukProduksi::class, 'produk_id');
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function dataUnit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function produkJadi()
    {
        return $this->belongsTo(ProdukJadi::class, 'produk_jadi_id');
    }

    public function produksiProdukJadi()
    {
        return $this->hasMany(ProduksiProdukJadi::class, 'produk_sample_id');
    }

    public function dataBahanRusak()
    {
        return $this->hasMany(BahanRusak::class, 'produk_sample_id', 'id');
    }

    public function dataBahanRetur()
    {
        return $this->hasMany(BahanRetur::class, 'produk_sample_id', 'id');
    }

    public function laporanProyek()
    {
        return $this->hasMany(LaporanProyek::class, 'produk_sample_id');
    }

    public function qcProdukSetengahJadi()
    {
        return $this->hasMany(QcProdukSetengahJadiList::class, 'produk_sample_id');
    }

}
