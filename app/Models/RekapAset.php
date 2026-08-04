<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RekapAset extends Model
{
    use HasFactory;
    protected $table = 'rekap_aset';

    protected $guarded = [];

    /**
     * Pergantian PIC dan perpindahan ruangan dicatat otomatis di level model,
     * supaya semua jalur ikut tercatat: form edit, import Excel, maupun
     * perubahan lewat kode. Tidak ada yang perlu diingat untuk dipanggil manual.
     */
    protected static function booted(): void
    {
        static::created(function (RekapAset $aset) {
            $aset->catatMutasi('PIC', null, $aset->pic_id);
            $aset->catatMutasi('Ruangan', null, $aset->ruangan_id);
        });

        static::updated(function (RekapAset $aset) {
            if ($aset->wasChanged('pic_id')) {
                $aset->catatMutasi('PIC', $aset->getOriginal('pic_id'), $aset->pic_id);
            }

            if ($aset->wasChanged('ruangan_id')) {
                $aset->catatMutasi('Ruangan', $aset->getOriginal('ruangan_id'), $aset->ruangan_id);
            }
        });
    }

    /**
     * Konteks mutasi yang sedang berlaku, diisi pemanggil lewat denganAlasan()
     * atau denganKonteks().
     *
     * Pencatatan riwayat terjadi di observer model, yang tidak tahu konteks
     * kenapa PIC atau ruangan berubah. Tanpa penanda ini, log hanya berisi
     * perpindahan bolak-balik tanpa keterangan — tidak bisa dibedakan mana yang
     * karena peminjaman, pengembalian, offboarding, atau diedit manual.
     *
     * Kunci yang dikenali: keterangan, tgl_kejadian, pengembalian_manajemen_id.
     */
    protected static array $konteksMutasi = [];

    /**
     * Jalankan sebuah operasi dengan alasan mutasi tertentu.
     */
    public static function denganAlasan(string $alasan, callable $aksi)
    {
        return static::denganKonteks(['keterangan' => $alasan], $aksi);
    }

    /**
     * Bentuk lengkapnya: selain alasan, bisa menyertakan tanggal kejadian dan
     * rujukan ke pencatatan serah terima ke manajemen.
     *
     * Konteks lama dipulihkan setelahnya supaya operasi bersarang tidak saling
     * menimpa — mis. pelepasan aset di dalam proses BAST.
     */
    public static function denganKonteks(array $konteks, callable $aksi)
    {
        $sebelumnya = static::$konteksMutasi;
        static::$konteksMutasi = $konteks;

        try {
            return $aksi();
        } finally {
            static::$konteksMutasi = $sebelumnya;
        }
    }

    /**
     * Simpan satu baris riwayat. Nama disimpan sebagai snapshot teks supaya
     * riwayat tetap terbaca walau user dihapus atau ruangan diganti nama.
     */
    public function catatMutasi(string $jenis, $dariId, $keId): void
    {
        // Tidak ada yang berubah, dan bukan penetapan awal.
        if ($dariId == $keId) {
            return;
        }

        RiwayatMutasiAset::create([
            'rekap_aset_id' => $this->id,
            'jenis' => $jenis,
            'dari_id' => $dariId,
            'dari_nama' => $this->namaMutasi($jenis, $dariId),
            'ke_id' => $keId,
            'ke_nama' => $this->namaMutasi($jenis, $keId),
            'dicatat_oleh' => auth()->id(),
            'keterangan' => static::$konteksMutasi['keterangan'] ?? null,
            // Kosong berarti kejadiannya bersamaan dengan pencatatannya, dan
            // created_at sudah mewakili. Lihat RiwayatMutasiAset::tanggal_efektif.
            'tgl_kejadian' => static::$konteksMutasi['tgl_kejadian'] ?? null,
            'pengembalian_manajemen_id' => static::$konteksMutasi['pengembalian_manajemen_id'] ?? null,
        ]);
    }

    private function namaMutasi(string $jenis, $id): ?string
    {
        if (!$id) {
            return null;
        }

        return $jenis === 'PIC'
            ? User::find($id)?->name
            : Ruangan::find($id)?->nama_ruangan;
    }

    public function jenisBahan()
    {
        return $this->belongsTo(JenisBahan::class, 'jenis_bahan_id');
    }

    public function dataUnit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function dataUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dataPic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function dataRuangan()
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_id');
    }

    public function barangAset()
    {
        return $this->belongsTo(BarangAset::class, 'barang_aset_id');
    }

    public function dataDivisi()
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function peminjamanDetails()
    {
        return $this->hasMany(PeminjamanAsetDetails::class, 'rekap_aset_id');
    }

    /**
     * Riwayat pergantian PIC & perpindahan ruangan, terbaru di atas.
     */
    public function riwayatMutasi()
    {
        return $this->hasMany(RiwayatMutasiAset::class, 'rekap_aset_id')->latest('id');
    }

    /**
     * Riwayat aset ini pernah dipinjam siapa saja, terbaru di atas.
     *
     * Hanya peminjaman yang asetnya benar-benar keluar (sudah lolos GA dan
     * diketahui HRD) yang dihitung. Pengajuan yang ditolak atau masih
     * menggantung tidak masuk, karena asetnya tidak pernah berpindah tangan.
     */
    public function riwayatPeminjaman()
    {
        return $this->hasMany(PeminjamanAsetDetails::class, 'rekap_aset_id')
            ->whereHas('peminjamanAset', function ($query) {
                $query->bolehDikeluarkan();
            })
            ->latest('id');
    }

    /**
     * Peminjaman yang sedang berjalan untuk aset ini: sudah disetujui GA,
     * sudah diketahui HRD (jadi asetnya boleh keluar), dan belum dikembalikan.
     * Null berarti aset masih tersedia — termasuk saat pengajuan sudah disetujui GA
     * tapi HRD belum mengetahui, karena asetnya belum boleh keluar.
     */
    /**
     * Aset punya PIC tetap yang bukan berasal dari peminjaman.
     *
     * pic_id ditulis dua jalur: penugasan tetap lewat rekap aset, dan peminjaman
     * yang disetujui. Keduanya tidak bisa dibedakan dari kolomnya saja, jadi
     * pembedanya adalah ada-tidaknya peminjaman aktif — kalau tidak ada, PIC-nya
     * pasti hasil penugasan tetap.
     *
     * Ini BUKAN penanda aset tidak tersedia. Ketersediaan tetap ditentukan
     * peminjaman, dan penugasan tetap sengaja tidak memblokir peminjaman —
     * aset milik seseorang tetap boleh dipinjam sementara oleh orang lain.
     * Penanda ini hanya untuk memberi tahu bahwa aset ini sudah ada pemiliknya.
     */
    public function getDitugaskanTetapAttribute(): bool
    {
        return $this->pic_id !== null && $this->peminjamanAktif === null;
    }

    public function peminjamanAktif()
    {
        return $this->hasOne(PeminjamanAsetDetails::class, 'rekap_aset_id')
            ->where('peminjaman_aset_details.status_pengembalian', 'Belum dikembalikan')
            ->whereHas('peminjamanAset', function ($query) {
                $query->bolehDikeluarkan();
            })
            ->latest('peminjaman_aset_details.id');
    }
}
