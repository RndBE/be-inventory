<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerbaikanData extends Model
{
    use HasFactory;

    protected $table = 'perbaikan_data';
    protected $guarded = [];

    protected $casts = [
        'tgl_pengajuan' => 'datetime',
    ];

    public function lampiran()
    {
        return $this->hasMany(LampiranPerbaikanData::class, 'perbaikan_data_id');
    }

    /**
     * Baris perubahan yang diminta pengajuan ini.
     */
    public function target()
    {
        return $this->hasMany(PerbaikanDataTarget::class, 'perbaikan_data_id');
    }

    /**
     * Surat penunjukan pelaksananya, kalau sudah diterbitkan.
     *
     * hasOne, bukan hasMany: satu pengajuan hanya boleh punya satu penunjukan,
     * dan kolomnya unique di database. Dua surat atas satu pengajuan berarti
     * dua orang mengaku ditunjuk untuk pekerjaan yang sama.
     */
    public function penunjukan()
    {
        return $this->hasOne(PenunjukanPerbaikanData::class, 'perbaikan_data_id');
    }

    /**
     * Boleh dieksekusi atau tidak.
     *
     * 'Disetujui' adalah syarat utamanya: persetujuan approver harus benar-benar
     * jadi gerbang, bukan sekadar salah satu status yang kebetulan lewat.
     *
     * 'Sedang Diperbaiki' ikut diterima karena itu status yang wajar dipilih
     * approver saat mulai mengerjakan koreksinya. Tanpa ini, memindahkan tiket
     * ke sana justru menghilangkan tombol Eksekusi dan membuatnya mandek.
     *
     * 'Selesai' sengaja tidak termasuk — status itu disetel oleh eksekusi yang
     * berhasil, jadi menerimanya di sini berarti membuka eksekusi kedua atas
     * tiket yang sudah tuntas.
     *
     * Tiket yang sudah dibatalkan tidak bisa dieksekusi walau pernah disetujui.
     */
    public function bolehDieksekusi(): bool
    {
        // 'Selesai' ikut, dan itu bukan kelonggaran yang kebetulan. Status
        // boleh disetel manual, jadi tiket bisa ditandai selesai sebelum
        // barisnya sempat dicatat ke audit. Kalau tombolnya hilang di keadaan
        // itu, baris-baris tadi tidak akan pernah punya jejak — dan jejak
        // itulah satu-satunya alasan modul ini ada.
        return in_array($this->status, ['Disetujui', 'Sedang Diperbaiki', 'Selesai'], true)
            && $this->dibatalkan_pada === null;
    }

    /**
     * Apakah daftar baris perubahan masih boleh disunting lewat form edit.
     *
     * Dibatasi ke tiket yang belum masuk tahap persetujuan dan belum ada
     * barisnya yang diterapkan. Approver menyetujui satu daftar tertentu;
     * kalau daftarnya masih bisa berubah sesudah itu, yang dieksekusi bisa
     * bukan yang disetujui, dan halaman audit akan mencatat perubahan yang
     * tidak pernah ada di tiket yang dibaca approver.
     *
     * Barisnya tetap DITAMPILKAN setelah tahap itu, hanya tidak bisa diubah —
     * menyembunyikannya membuat pengaju tidak bisa memastikan apa yang
     * diajukannya.
     */
    public function targetMasihBisaDiubah(): bool
    {
        if ($this->dibatalkan_pada !== null) {
            return false;
        }

        if (in_array($this->status, ['Disetujui', 'Sedang Diperbaiki', 'Selesai', 'Ditolak'], true)) {
            return false;
        }

        $target = $this->relationLoaded('target') ? $this->target : $this->target()->get();

        return $target->every(fn ($baris) => $baris->status !== 'dieksekusi');
    }

    public function approvalKendalas()
    {
        return $this->hasMany(ApprovalKendala::class, 'module_id')
            ->where('module', 'perbaikan_data');
    }

    public function kendalaApproval(string $role): ?string
    {
        $notes = $this->relationLoaded('approvalKendalas')
            ? $this->approvalKendalas
            : $this->approvalKendalas()->get();

        return optional($notes->firstWhere('approval_role', $role))->kendala;
    }

}
