<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembelianBahan extends Model
{
    use HasFactory;

    protected $table = 'pembelian_bahan';
    protected $guarded = [];

    public const KATEGORI_PRODUKSI = 'Produksi';
    public const KATEGORI_RISET = 'Riset';

    /**
     * Jenis pengajuan yang memakai toggle kategori Produksi/Riset. Jenis Aset
     * punya alur sendiri (General Affair), jadi tidak ikut.
     */
    public const JENIS_PAKAI_KATEGORI = [
        'Pembelian Bahan/Barang/Alat Lokal',
        'Pembelian Bahan/Barang/Alat Impor',
    ];

    /**
     * Siapa pemilik id approval Leader menurut kategori.
     *
     * Riset tidak melewati tahap Leader: yang memutus adalah Manager (atasan
     * level 2), berdiri di slot Leader. Produksi tetap ke atasan level 3, dengan
     * Manager sebagai pengganti bila level 3 kosong — pola lama.
     *
     * Sengaja menerima id atasan, bukan model User, supaya bisa diuji tanpa
     * database.
     */
    public static function approverLeaderId(?string $kategori, ?int $atasanLevel3Id, ?int $atasanLevel2Id): ?int
    {
        if ($kategori === self::KATEGORI_RISET) {
            return $atasanLevel2Id;
        }

        return $atasanLevel3Id ?? $atasanLevel2Id;
    }

    /**
     * Status awal slot Leader. Tanpa approver sama sekali, tahap ini otomatis
     * 'Disetujui' — mengikuti pola alur pengajuan lama.
     */
    public static function statusLeaderAwal(?string $kategori, ?int $atasanLevel3Id, ?int $atasanLevel2Id): string
    {
        return self::approverLeaderId($kategori, $atasanLevel3Id, $atasanLevel2Id) === null
            ? 'Disetujui'
            : 'Belum disetujui';
    }

    public function pakaiKategoriPengajuan(): bool
    {
        return in_array($this->base_jenis_pengajuan, self::JENIS_PAKAI_KATEGORI, true);
    }

    /**
     * Pada kategori Riset, slot Leader diputus Manager (atasan level 2), bukan
     * atasan level 3.
     */
    public function leaderDiputusManager(): bool
    {
        return $this->pakaiKategoriPengajuan() && $this->kategori_pengajuan === self::KATEGORI_RISET;
    }

    /**
     * User yang berhak memutus slot Leader — dipakai untuk notifikasi, nama, dan
     * tanda tangan di PDF.
     */
    public function approverLeader(): ?User
    {
        $pengaju = $this->dataUser;

        if (! $pengaju) {
            return null;
        }

        return $this->leaderDiputusManager()
            ? $pengaju->atasanLevel2
            : ($pengaju->atasanLevel3 ?? $pengaju->atasanLevel2);
    }

    /**
     * Kategori masih boleh diubah selama Purchasing belum memutus dan belum ada
     * penolakan. Mengubah kategori memindah approver slot Leader, jadi batasnya
     * dipasang sebelum Purchasing supaya harga belum ikut diproses.
     */
    public function kategoriMasihBisaDiubah(): bool
    {
        return $this->pakaiKategoriPengajuan()
            && ($this->status_purchasing ?? 'Belum disetujui') === 'Belum disetujui'
            && $this->status_leader !== 'Ditolak'
            && ($this->status ?? 'Belum disetujui') !== 'Ditolak';
    }

    public function dataUser()
    {
        return $this->belongsTo(User::class, 'pengaju');
    }

    public function pembelianBahanDetails()
    {
        return $this->hasMany(PembelianBahanDetails::class, 'pembelian_bahan_id');
    }

    public function approvalKendalas()
    {
        return $this->hasMany(ApprovalKendala::class, 'module_id')
            ->where('module', 'pembelian_bahan');
    }

    public function kendalaApproval(string $role): ?string
    {
        $notes = $this->relationLoaded('approvalKendalas')
            ? $this->approvalKendalas
            : $this->approvalKendalas()->get();

        return optional($notes->firstWhere('approval_role', $role))->kendala;
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
