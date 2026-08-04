<?php

namespace App\Http\Controllers;

use App\Models\PeminjamanAset;
use App\Models\PeminjamanAsetBukti;
use App\Models\PengembalianManajemenBukti;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penyaji bukti foto serah terima aset.
 *
 * Sebelum ini foto disimpan ke disk 'public' dan dirujuk lewat asset('storage/…'),
 * sehingga bisa dibuka siapa pun tanpa login. Yang membuatnya bukan sekadar
 * teoretis: nama berkasnya deterministik — 'pengembalian_<id>_<unix>_<urutan>.jpg' —
 * jadi seluruh arsipnya bisa dienumerasi dari luar dengan mencoba rentang id dan
 * timestamp.
 *
 * Foto sekarang disimpan di disk 'local' (di luar public/) dan hanya keluar
 * lewat kedua aksi di sini, yang memeriksa hak akses per-record. Bukan sekadar
 * "sudah login": yang dipakai aturan yang sama dengan halaman yang menampilkan
 * fotonya, supaya tidak ada jalan pintas melihat pengajuan divisi lain.
 */
class BuktiAsetController extends Controller
{
    /**
     * Bukti pengembalian aset milik satu pengajuan peminjaman.
     *
     * Haknya mengikuti scope terlihatOleh() yang juga dipakai daftar pengajuan,
     * jadi siapa pun yang tidak boleh melihat pengajuannya juga tidak bisa
     * melihat fotonya.
     */
    public function peminjaman(Request $request, int $id): StreamedResponse
    {
        $bukti = PeminjamanAsetBukti::with('detailPeminjaman')->findOrFail($id);

        $peminjamanId = $bukti->detailPeminjaman?->peminjaman_aset_id;

        if (!$peminjamanId) {
            abort(404);
        }

        $bolehLihat = PeminjamanAset::query()
            ->terlihatOleh(Auth::user())
            ->whereKey($peminjamanId)
            ->exists();

        abort_unless($bolehLihat, 403);

        return $this->sajikan($bukti->path);
    }

    /**
     * Bukti serah terima aset ke manajemen.
     *
     * Tidak terikat pengajuan, jadi haknya mengikuti kedua halaman yang
     * menampilkannya: riwayat aset di Rekapitulasi Aset, dan Pergerakan Aset.
     */
    public function manajemen(Request $request, int $id): StreamedResponse
    {
        $bukti = PengembalianManajemenBukti::findOrFail($id);

        $user = Auth::user();

        abort_unless(
            $user && ($user->can('lihat-rekap-aset') || $user->can('lihat-pergerakan-aset')),
            403
        );

        return $this->sajikan($bukti->path);
    }

    /**
     * Mengalirkan berkasnya dari disk 'local'.
     *
     * Disk 'public' ikut dicoba sebagai cadangan untuk berkas yang diunggah
     * sebelum perubahan ini — path di database tetap sama, hanya disk-nya yang
     * berbeda, jadi foto lama tidak perlu dipindahkan agar tetap terbuka.
     */
    private function sajikan(?string $path): StreamedResponse
    {
        if (!$path) {
            abort(404);
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                // inline, bukan attachment: fotonya ditampilkan di tab baru dan
                // dipakai sebagai <img src>, bukan diunduh.
                return Storage::disk($disk)->response($path, basename($path), [
                    'Cache-Control' => 'private, max-age=3600',
                ]);
            }
        }

        abort(404);
    }
}
