<?php

namespace App\Models;

use App\Helpers\DivisiHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PeminjamanAset extends Model
{
    use HasFactory;

    protected $table = 'peminjaman_aset';
    protected $guarded = [];

    public function peminjamanAsetDetails()
    {
        return $this->hasMany(PeminjamanAsetDetails::class, 'peminjaman_aset_id');
    }

    public function dataUser()
    {
        return $this->belongsTo(User::class, 'pengaju');
    }

    public function dataRuangan()
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_id');
    }

    /**
     * Kendala approval, memakai mekanisme ApprovalKendala yang dipakai bersama
     * modul lain (bahan keluar & pembelian bahan). Satu catatan per tahap.
     */
    public function approvalKendalas()
    {
        return $this->hasMany(ApprovalKendala::class, 'module_id')
            ->where('module', 'peminjaman_aset');
    }

    /**
     * Kendala yang dicatat approver pada tahap tertentu, mis. 'General Affair'.
     */
    public function kendalaApproval(string $role): ?string
    {
        $notes = $this->relationLoaded('approvalKendalas')
            ? $this->approvalKendalas
            : $this->approvalKendalas()->get();

        return optional($notes->firstWhere('approval_role', $role))->kendala;
    }

    /**
     * Aset baru boleh dikeluarkan/dipindahkan setelah General Affair menyetujui
     * DAN HRD mengetahui. Kalau HRD tidak setuju, aset tetap tidak boleh keluar
     * meskipun GA sudah menyetujui peminjamannya.
     */
    public function getBolehDikeluarkanAttribute(): bool
    {
        return $this->status === 'Disetujui' && $this->status_hrd === 'Disetujui';
    }

    /**
     * Pengajuan yang asetnya sudah boleh keluar.
     */
    public function scopeBolehDikeluarkan($query)
    {
        return $query->where('status', 'Disetujui')->where('status_hrd', 'Disetujui');
    }

    /**
     * Peminjaman yang masih berjalan: aset sudah keluar tapi belum kembali semua.
     */
    public function scopeAktif($query)
    {
        return $query->bolehDikeluarkan()->where('status_pengembalian', '!=', 'Selesai');
    }

    /**
     * Isi pengajuan masih boleh diubah selama belum ada approver yang memutuskan.
     *
     * Patokannya kolom tgl_approve_*, bukan kolom status. store() menandai tahap
     * yang tidak punya penanggung jawab sebagai "Disetujui" tanpa ada manusia yang
     * menyetujui — hampir separuh user tidak punya atasan_level3_id — sehingga
     * status_leader tidak bisa dipakai membedakan "sudah diputus" dari "lewat
     * otomatis". Kolom tanggal hanya ditulis di updateApproval(), jadi masih null
     * berarti benar-benar belum ada keputusan.
     *
     * Penolakan ikut menstempel tanggal, jadi pengajuan yang sudah ditolak
     * otomatis terkunci: pengaju membuat pengajuan baru, arsip penolakan utuh.
     */
    public function getBolehDiubahAttribute(): bool
    {
        return $this->tgl_approve_leader === null
            && $this->tgl_approve_manager === null
            && $this->tgl_approve_ga === null
            && $this->tgl_approve_hrd === null;
    }

    /**
     * Hanya pemilik pengajuan yang boleh mengubah isinya, dan hanya selama
     * belum ada approval. Tidak ada jalur admin — supaya isi pengajuan selalu
     * bisa dipertanggungjawabkan ke satu orang.
     */
    public function bolehDiubahOleh($user): bool
    {
        return $user
            && (int) $this->pengaju === (int) $user->id
            && $this->boleh_diubah;
    }

    /**
     * Kolom status milik tiap tahap approval.
     *
     * Sengaja di model, bukan di controller: aturan siapa boleh memutus apa
     * dipakai bersama oleh controller (menegakkan) dan Blade (menyembunyikan
     * tombol). Selama keduanya menyusun aturannya sendiri-sendiri, keduanya
     * bisa melenceng — dan yang melenceng adalah sisi yang menegakkan.
     */
    public const KOLOM_STATUS_TAHAP = [
        'leader' => 'status_leader',
        'manager' => 'status_manager',
        'ga' => 'status',
        'hrd' => 'status_hrd',
    ];

    /**
     * Tahap ini belum diputus, jadi masih boleh diputus sekarang.
     *
     * "Belum disetujui" bukan keputusan: itu keadaan awal, dan juga dipakai
     * approver yang hanya mencatat kendala tanpa memutuskan.
     */
    public function tahapBelumDiputus(string $tahap): bool
    {
        if (!array_key_exists($tahap, self::KOLOM_STATUS_TAHAP)) {
            return false;
        }

        $status = $this->{self::KOLOM_STATUS_TAHAP[$tahap]};

        return $status !== 'Disetujui' && $status !== 'Ditolak';
    }

    /**
     * Tahap sebelum $tahap sudah disetujui, sehingga urutannya tidak dilangkahi.
     */
    public function tahapSebelumnyaSudahDisetujui(string $tahap): bool
    {
        return match ($tahap) {
            'manager' => $this->status_leader === 'Disetujui',
            'ga' => $this->status_leader === 'Disetujui'
                && $this->status_manager === 'Disetujui',
            'hrd' => $this->status_leader === 'Disetujui'
                && $this->status_manager === 'Disetujui'
                && $this->status === 'Disetujui',
            // Leader tahap pertama — tidak ada yang mendahuluinya.
            default => true,
        };
    }

    /**
     * $user berada di garis komando yang berhak memutus $tahap.
     *
     * Inilah bagian yang dulu hanya hidup di Blade: controller cuma memeriksa
     * permission, sehingga pemegang approve-leader bisa memutus pengajuan siapa
     * pun — lintas divisi, bahkan pengajuannya sendiri. Permission menyatakan
     * "boleh jadi approver", bukan "approver untuk orang ini".
     *
     * General Affair & HRD memang tidak punya batas garis komando: mereka
     * penanggung jawab aset perusahaan, jadi memutus untuk semua divisi.
     * Gerbang mereka permission + urutan tahap, yang diperiksa terpisah.
     */
    public function beradaDiGarisKomando($user, string $tahap): bool
    {
        if (!$user) {
            return false;
        }

        // Superadmin boleh menggantikan approver yang tidak tersedia.
        if ($user->hasRole('superadmin')) {
            return true;
        }

        $pengaju = $this->dataUser;

        if (!$pengaju) {
            return false;
        }

        $atasanLevel3 = (int) ($pengaju->atasan_level3_id ?? 0) === (int) $user->id;
        $atasanLevel2 = (int) ($pengaju->atasan_level2_id ?? 0) === (int) $user->id;

        return match ($tahap) {
            // Tanpa atasan level 3, level 2 yang merangkap tahap Leader.
            'leader' => $atasanLevel3 || (!$pengaju->atasan_level3_id && $atasanLevel2),
            // Pengaju tanpa atasan level 2 tidak punya Manager yang spesifik,
            // jadi tahap itu terbuka bagi pemegang permission-nya.
            'manager' => $atasanLevel2 || !$pengaju->atasan_level2_id,
            'ga', 'hrd' => true,
            default => false,
        };
    }

    /**
     * User yang tidak dibatasi sama sekali dalam melihat pengajuan.
     *
     * Penanggung jawab aset perusahaan (GA & HRD) memang harus melihat seluruh
     * divisi untuk bisa memutuskan dan mencatat pengembalian. Direksi ikut di
     * sini karena berada di atas semua divisi, sehingga tidak ada batas divisi
     * yang masuk akal untuk mereka.
     */
    public static function bolehLihatSemuaPengajuan($user): bool
    {
        if (!$user) {
            return false;
        }

        if ((int) $user->job_level === 1) {
            return true;
        }

        return $user->hasAnyRole(['superadmin', 'general_affair'])
            || $user->can('approve-ga-peminjaman-aset')
            || $user->can('approve-hrd-peminjaman-aset')
            || $user->can('pengembalian-peminjaman-aset');
    }

    /**
     * Boleh membuka layar approval sama sekali.
     *
     * Permission saja tidak cukup. Permission dipegang per-role, sementara
     * jenjang tersimpan per-user di job_level — sehingga staf level 4 yang
     * sedivisi dengan leader-nya ikut kebagian permission role yang sama dan
     * melihat menu Approval yang, bagi mereka, selalu kosong: scopeTerlihatOleh
     * tidak memberi level 4 jenjang di bawah, dan beradaDiGarisKomando() selalu
     * menolak mereka karena tidak ada yang menjadikan mereka atasan.
     *
     * Batas job_level TIDAK boleh berdiri sendiri: akun Super Admin justru
     * ber-job_level 4, begitu juga sebagian pemegang GA/HRD. Karena itu
     * pemegang izin lintas-divisi diperiksa lebih dulu dan dilepas dari batas
     * jenjang — kalau dibalik urutannya, superadmin kehilangan menunya sendiri.
     */
    public static function bolehBukaLayarApproval($user): bool
    {
        if (!$user || !$user->can('lihat-approval-peminjaman-aset')) {
            return false;
        }

        if (static::bolehLihatSemuaPengajuan($user)) {
            return true;
        }

        // Manager (2) dan leader (3) memutus; staf (4) tidak pernah.
        return (int) $user->job_level <= 3;
    }

    /**
     * Batasi ke pengajuan yang boleh dilihat user ini.
     *
     * Berjenjang mengikuti kolom job_level, sama seperti alur pembelian bahan:
     *   - level 4 (staf)    : pengajuannya sendiri saja
     *   - level 3 (leader)  : miliknya + staf level 4 di divisinya
     *   - level 2 (manager) : miliknya + leader level 3 dan staf level 4 di divisinya
     *
     * Dipakai bersama oleh layar pemohon dan layar approval supaya aturannya
     * tidak bercabang. Cakupan divisi diambil dari DivisiHelper, satu-satunya
     * tempat pemetaan role -> divisi didefinisikan.
     */
    public function scopeTerlihatOleh($query, $user)
    {
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if (static::bolehLihatSemuaPengajuan($user)) {
            return $query;
        }

        $divisi = DivisiHelper::divisiUntuk($user);

        $levelDiBawah = match ((int) $user->job_level) {
            2 => [3, 4],
            3 => [4],
            default => [],
        };

        return $query->where(function ($q) use ($user, $divisi, $levelDiBawah) {
            // Pengajuan sendiri selalu terlihat, apa pun levelnya.
            $q->where('pengaju', $user->id);

            // Jenjang di bawahnya, dibatasi ke divisi yang jadi tanggung jawabnya.
            if ($levelDiBawah && $divisi !== null) {
                $q->orWhere(function ($sub) use ($divisi, $levelDiBawah) {
                    $sub->whereIn('divisi', $divisi)
                        ->whereHas('dataUser', function ($u) use ($levelDiBawah) {
                            $u->whereIn('job_level', $levelDiBawah);
                        });
                });
            }

            // Anak buah langsung tetap terlihat walau divisinya berbeda. Tanpa ini
            // pengajuan bisa mandek: approver-nya wajib memutuskan tapi tidak
            // pernah melihat pengajuannya di layar.
            $q->orWhereHas('dataUser', function ($u) use ($user) {
                $u->where('atasan_level3_id', $user->id)
                    ->orWhere('atasan_level2_id', $user->id);
            });
        });
    }

    /**
     * Ditolak di tahap mana pun, termasuk ditahan HRD.
     */
    public function scopeDitolak($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'Ditolak')->orWhere('status_hrd', 'Ditolak');
        });
    }

    /**
     * Tahap yang sedang ditunggu. Dipakai untuk kolom status di tabel.
     */
    public function getTahapBerjalanAttribute()
    {
        if ($this->status === 'Ditolak') {
            return 'Ditolak';
        }

        if ($this->status_hrd === 'Ditolak') {
            return 'Ditahan HRD';
        }

        if ($this->status_leader !== 'Disetujui') {
            return 'Menunggu Leader';
        }

        if ($this->status_manager !== 'Disetujui') {
            return 'Menunggu Manager';
        }

        if ($this->status !== 'Disetujui') {
            return 'Menunggu General Affair';
        }

        if ($this->status_hrd !== 'Disetujui') {
            return 'Menunggu HRD';
        }

        return $this->status_pengembalian === 'Selesai' ? 'Selesai' : 'Sedang Dipinjam';
    }

    /**
     * Sudah berapa hari aset ini keluar dan belum kembali.
     * Null kalau asetnya belum boleh keluar atau semuanya sudah dikembalikan.
     */
    public function getLamaDipinjamAttribute()
    {
        if (!$this->boleh_dikeluarkan || $this->status_pengembalian === 'Selesai' || !$this->tgl_pinjam) {
            return null;
        }

        return \Carbon\Carbon::parse($this->tgl_pinjam)->startOfDay()->diffInDays(now()->startOfDay());
    }
}
