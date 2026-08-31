<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanKeluar extends Model
{
    use HasFactory;

    protected $table = 'bahan_keluars';
    protected $guarded = [];

    /**
     * Pemilik slot approval awal Bahan Keluar dalam bentuk aturan ketat:
     * Proyek RnD tidak mempunyai tahap Leader, sehingga atasan level 2
     * (Manager) langsung memutus slot yang secara historis disimpan pada
     * kolom status_leader. Transaksi lain tetap memakai Leader dan jatuh ke
     * Manager hanya ketika atasan level 3 tidak tersedia.
     *
     * Produk Sample kategori RnD memakai aturan yang lebih longgar — lihat
     * approverLeader(), yang menurunkan slotnya ke Leader saat Manager belum
     * ada.
     */
    public static function approverLeaderId(bool $diputusManager, ?int $atasanLevel3Id, ?int $atasanLevel2Id): ?int
    {
        if ($diputusManager) {
            return $atasanLevel2Id;
        }

        return $atasanLevel3Id ?? $atasanLevel2Id;
    }

    public static function statusLeaderAwal(bool $diputusManager, ?int $atasanLevel3Id, ?int $atasanLevel2Id): string
    {
        return self::approverLeaderId($diputusManager, $atasanLevel3Id, $atasanLevel2Id) === null
            ? 'Disetujui'
            : 'Belum disetujui';
    }

    /**
     * Kategori dibekukan di baris Bahan Keluar, bukan dibaca ulang dari produk
     * sample-nya. Nama dan kategori produk sample masih bisa diedit setelah
     * pengajuan jalan, dan approver satu transaksi tidak boleh ikut berpindah
     * karena itu.
     */
    public function leaderDiputusManager(): bool
    {
        return $this->projek_rnd_id !== null
            || $this->kategori_pengajuan === ProdukSample::KATEGORI_RND;
    }

    /**
     * Produk Sample kategori RnD yang pengajunya belum punya atasan level 2
     * turun ke Leader, jadi labelnya ikut turun. Proyek RnD tidak ikut aturan
     * ini karena controller-nya menolak simpan saat Manager belum ada.
     */
    public function approvalAwalRole(): string
    {
        if ($this->projek_rnd_id !== null) {
            return 'Manager';
        }

        if ($this->kategori_pengajuan === ProdukSample::KATEGORI_RND) {
            return $this->dataUser && $this->dataUser->atasan_level2_id === null
                ? 'Leader'
                : 'Manager';
        }

        return 'Leader';
    }

    public function approverLeader(): ?User
    {
        $pengaju = $this->dataUser;

        if (!$pengaju) {
            return null;
        }

        if ($this->projek_rnd_id !== null) {
            return $pengaju->atasanLevel2;
        }

        if ($this->kategori_pengajuan === ProdukSample::KATEGORI_RND) {
            // Kategori RnD diputus Manager. Tanpa atasan level 2, slot ini
            // jatuh ke Leader supaya pengajuan tetap diperiksa atasan.
            return $pengaju->atasanLevel2 ?? $pengaju->atasanLevel3;
        }

        return $pengaju->atasanLevel3 ?? $pengaju->atasanLevel2;
    }

    public function dataUser()
    {
        return $this->belongsTo(User::class, 'pengaju', 'id');
    }

    public function bahanKeluarDetails()
    {
        return $this->hasMany(BahanKeluarDetails::class, 'bahan_keluar_id');
    }

    public function approvalKendalas()
    {
        return $this->hasMany(ApprovalKendala::class, 'module_id')
            ->where('module', 'bahan_keluar');
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

    public function projek()
    {
        return $this->hasOne(Projek::class, 'bahan_keluar_id');
    }
    public function produkJadiDetails()
    {
        return $this->hasOne(ProdukJadiDetails::class, 'id');
    }
}
