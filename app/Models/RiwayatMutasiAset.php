<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RiwayatMutasiAset extends Model
{
    use HasFactory;

    protected $table = 'riwayat_mutasi_aset';
    protected $guarded = [];

    protected $casts = [
        'tgl_kejadian' => 'date',
    ];

    public function dataAset()
    {
        return $this->belongsTo(RekapAset::class, 'rekap_aset_id');
    }

    public function pencatat()
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    /**
     * Pencatatan serah terima ke manajemen yang melahirkan baris ini, kalau ada.
     * Dari sini bukti foto & catatannya bisa diambil.
     */
    public function pengembalianManajemen()
    {
        return $this->belongsTo(PengembalianManajemen::class, 'pengembalian_manajemen_id');
    }

    /**
     * Tanggal yang dipakai menampilkan dan menyaring: tanggal kejadian kalau
     * pencatatnya menyebutkannya, kalau tidak ya waktu pencatatan.
     *
     * Perpindahan otomatis (peminjaman, edit manual) memang terjadi saat dicatat,
     * jadi created_at sudah benar untuk baris-baris itu.
     */
    public function getTanggalEfektifAttribute()
    {
        return $this->tgl_kejadian ?? $this->created_at;
    }

    /**
     * Tanggal kejadiannya berbeda dari waktu pencatatan — dipakai UI untuk
     * menerangkan bahwa catatannya dibuat belakangan, bukan menyembunyikannya.
     */
    public function getDicatatBelakanganAttribute(): bool
    {
        return $this->tgl_kejadian
            && $this->created_at
            && $this->tgl_kejadian->toDateString() !== $this->created_at->toDateString();
    }

    /**
     * Kalimat siap tampil, mis. "Dipindah dari Ruang Direksi ke Ruang Software".
     */
    public function getRingkasanAttribute(): string
    {
        $dari = $this->dari_nama ?: '(kosong)';
        $ke = $this->ke_nama ?: '(kosong)';

        if (!$this->dari_nama) {
            return $this->jenis === 'PIC'
                ? "Ditetapkan sebagai pemegang: {$ke}"
                : "Ditempatkan di {$ke}";
        }

        if (!$this->ke_nama) {
            return $this->jenis === 'PIC'
                ? "Pemegang dikosongkan (sebelumnya {$dari})"
                : "Dikeluarkan dari {$dari}";
        }

        return $this->jenis === 'PIC'
            ? "Pemegang berpindah dari {$dari} ke {$ke}"
            : "Dipindah dari {$dari} ke {$ke}";
    }
}
