<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Surat penunjukan pelaksana atas satu pengajuan perbaikan data.
 *
 * Isinya tidak menyalin data pengajuan. Kode, jenis, dan daftar perubahan
 * dibaca lewat relasi `perbaikanData` setiap kali dibutuhkan — termasuk saat
 * mencetak PDF. Menyalinnya ke sini akan membuat surat yang tercetak berbeda
 * dari pengajuan aslinya begitu pengajuannya dikoreksi, dan yang membaca surat
 * tidak punya cara tahu mana yang benar.
 *
 * Yang disimpan di sini hanya yang memang milik suratnya sendiri: nomor resmi,
 * siapa yang ditunjuk, tim pemohonnya, pokok perubahan yang ditulis penerbit,
 * dan jawaban pelaksanaannya.
 */
class PenunjukanPerbaikanData extends Model
{
    use HasFactory;

    /**
     * Status awal, sebelum pelaksananya menjawab.
     *
     * Bukan salah satu dari tiga kotak centang di suratnya: surat yang baru
     * terbit belum punya jawaban, dan menandainya "Selesai Sebagian" atau
     * apa pun dari daftar itu akan mencetak centang yang tidak pernah diisi
     * siapa pun.
     */
    public const STATUS_AWAL = 'Ditunjuk';

    protected $table = 'perbaikan_data_penunjukan';

    protected $guarded = [];

    protected $casts = [
        'tgl_penunjukan' => 'datetime',
        'tgl_pelaksanaan' => 'datetime',
        'nomor_urut' => 'integer',
        'tahun_surat' => 'integer',
    ];

    /**
     * Status pelaksanaan yang boleh dipilih.
     *
     * Diambil dari config, bukan ditulis ulang di sini, karena daftar yang sama
     * dicetak sebagai kotak centang di halaman konfirmasi PDF-nya. Kalau
     * keduanya ditulis di dua tempat, akan ada surat berstatus yang tidak punya
     * kotak untuk dicentang.
     *
     * @return array<int, string>
     */
    public static function pilihanStatus(): array
    {
        return array_values((array) config('surat_penunjukan.konfirmasi.status', []));
    }

    public function perbaikanData()
    {
        return $this->belongsTo(PerbaikanData::class, 'perbaikan_data_id');
    }

    /**
     * Yang ditunjuk mengubah datanya.
     */
    public function pelaksana()
    {
        return $this->belongsTo(User::class, 'ditunjuk_user_id');
    }

    /**
     * Pemegang pintu yang menerbitkan suratnya.
     */
    public function penunjuk()
    {
        return $this->belongsTo(User::class, 'ditunjuk_oleh_user_id');
    }

    /**
     * Yang mengisi bagian pelaksanaan.
     */
    public function pengisiPelaksanaan()
    {
        return $this->belongsTo(User::class, 'diisi_oleh_user_id');
    }

    /**
     * Bagian pelaksanaan sudah diisi atau belum.
     *
     * Diukur dari `tgl_pelaksanaan`, bukan dari status: status bisa dipindah
     * lebih dulu, dan yang menentukan surat ini sudah punya jawaban adalah
     * adanya tanggal pelaksanaan.
     */
    public function sudahDilaksanakan(): bool
    {
        return $this->tgl_pelaksanaan !== null;
    }

    /**
     * Nomor surat untuk dicetak, dengan kode penunjukan sebagai cadangan.
     *
     * Baris yang dibuat sebelum kolom nomor surat ada tidak punya nomor resmi,
     * dan mencetak "Nomor : -" pada surat dinas lebih buruk daripada mencetak
     * pengenal internalnya.
     */
    public function nomorSuratCetak(): string
    {
        return $this->nomor_surat ?: $this->kode_penunjukan;
    }

    /**
     * Nama berkas Word suratnya, aman dipakai sebagai nama file.
     *
     * Nomor suratnya berbentuk "001/ACC-PD/IX/2026" — garis miring adalah
     * bagian dari format resmi Accounting, bukan salah ketik. Tapi nama berkas
     * unduhan tidak boleh memuat "/" maupun "\": Symfony menolaknya dengan
     * "The filename and the fallback cannot contain the / and \
     * characters", dan cetak suratnya gagal sebelum satu halaman pun keluar.
     *
     * Yang diganti hanya nama berkasnya. Nomor di badan surat tetap utuh dengan
     * garis miringnya — itu yang dirujuk arsip, dan mengubahnya di sana
     * berarti mencetak nomor yang tidak cocok dengan pembukuan.
     */
    public function namaBerkasSurat(): string
    {
        $nomor = str_replace(['/', chr(92)], '-', $this->nomorSuratCetak());

        // Karakter lain yang ditolak sistem berkas Windows ikut dibersihkan:
        // suratnya diunduh ke komputer orang, bukan disimpan di server.
        $nomor = preg_replace('/[:*?"<>|]+/', '-', $nomor);

        return 'Penunjukan-' . trim((string) $nomor, '-') . '.docx';
    }

    /**
     * Nama berkas lembar konfirmasi pelaksanaan.
     *
     * Berawalan berbeda dari suratnya supaya keduanya tidak saling menimpa di
     * folder unduhan, dan supaya yang mengarsipkan tahu isi berkasnya dari
     * namanya — keduanya bernomor surat sama.
     */
    public function namaBerkasKonfirmasi(): string
    {
        return str_replace('Penunjukan-', 'Konfirmasi-', $this->namaBerkasSurat());
    }

    /**
     * Tim pemohon yang disebut di kalimat pembuka surat.
     */
    public function timPemohon(): string
    {
        return $this->tim_pemohon
            ?: (string) config('surat_penunjukan.tim_pemohon_default', 'pihak pemohon');
    }

    /**
     * Siapa yang boleh mengisi bagian pelaksanaan.
     *
     * Pelaksana yang namanya tertulis di surat selalu boleh — haknya berasal
     * dari penunjukan itu sendiri, bukan dari daftar permission. Di luar itu
     * butuh `isi-pelaksanaan-perbaikan-data`, untuk atasan yang mengisikan
     * atau software lain yang menggantikan.
     */
    public function bolehDiisiOleh(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (int) $this->ditunjuk_user_id === (int) $user->id
            || $user->can('isi-pelaksanaan-perbaikan-data');
    }

}
