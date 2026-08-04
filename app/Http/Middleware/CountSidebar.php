<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use Illuminate\Http\Request;
use App\Models\PembelianBahan;
use App\Models\PeminjamanAset;
use App\Helpers\DivisiHelper;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CountSidebar
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        // Default jumlah jika tidak ada pengguna yang login
        $jumlahBahanKeluar = 0;
        $jumlahPembelianBahan = 0;

        if ($user) {
            $bahanKeluarQuery = BahanKeluar::query();
            $bahanPembelianBahanQuery = PembelianBahan::query();

            // Cakupan divisi diambil dari DivisiHelper supaya aturannya cuma ada di satu tempat.
            $divisi = DivisiHelper::divisiUntuk($user);

            if ($divisi !== null) {
                $bahanKeluarQuery->whereIn('divisi', $divisi);
                $bahanPembelianBahanQuery->whereIn('divisi', $divisi);
            }

            $jumlahBahanKeluar = $bahanKeluarQuery->where('status', 'Belum disetujui')->count();
            $jumlahPembelianBahan = $bahanPembelianBahanQuery->where('status', 'Belum disetujui')->count();
        }



        $jumlahBahanRusak = BahanRusak::where('status', 'Belum disetujui')->count();
        $jumlahBahanRetur = BahanRetur::where('status', 'Belum disetujui')->count();
        $jumlahPeminjamanAset = $this->hitungPeminjamanMenungguSaya($user);
        // $jumlahBahanKeluar = BahanKeluar::where('status', 'Belum disetujui')->count();


        // Simpan jumlah ke dalam session atau view composer
        view()->share('jumlahBahanRusak', $jumlahBahanRusak);
        view()->share('jumlahBahanRetur', $jumlahBahanRetur);
        view()->share('jumlahBahanKeluar', $jumlahBahanKeluar);
        view()->share('jumlahPembelianBahan', $jumlahPembelianBahan);
        view()->share('jumlahPeminjamanAset', $jumlahPeminjamanAset);

        return $next($request);
    }

    /**
     * Jumlah pengajuan peminjaman aset yang menunggu persetujuan user ini.
     * Leader & Manager dihitung dari hierarki atasan pengaju, General Affair dari role.
     */
    private function hitungPeminjamanMenungguSaya($user): int
    {
        if (!$user) {
            return 0;
        }

        $query = PeminjamanAset::where('status', '!=', 'Ditolak')->where('status_hrd', '!=', 'Ditolak');

        // HRD: yang sudah lolos GA dan menunggu diketahui
        if ($user->can('approve-hrd-peminjaman-aset') && !$user->hasRole('superadmin')) {
            return (int) $query->where('status', 'Disetujui')->where('status_hrd', 'Belum disetujui')->count();
        }

        if ($user->hasRole(['superadmin', 'general_affair'])) {
            return (int) $query->where(function ($q) {
                $q->where('status', 'Belum disetujui')
                    ->orWhere(function ($sub) {
                        $sub->where('status', 'Disetujui')->where('status_hrd', 'Belum disetujui');
                    });
            })->count();
        }

        return (int) $query->where(function ($outer) use ($user) {
            // Menunggu approval saya sebagai Leader
            $outer->where(function ($q) use ($user) {
                $q->where('status_leader', 'Belum disetujui')
                    ->whereHas('dataUser', function ($sub) use ($user) {
                        $sub->where('atasan_level3_id', $user->id);
                    });
            })
            // Menunggu approval saya sebagai Manager
            ->orWhere(function ($q) use ($user) {
                $q->where('status_leader', 'Disetujui')
                    ->where('status_manager', 'Belum disetujui')
                    ->whereHas('dataUser', function ($sub) use ($user) {
                        $sub->where('atasan_level2_id', $user->id);
                    });
            });
        })->count();
    }
}
