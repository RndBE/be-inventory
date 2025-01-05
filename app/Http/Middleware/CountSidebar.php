<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use Illuminate\Http\Request;
use App\Models\PembelianBahan;
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

            if ($user->hasRole(['superadmin', 'administrasi', 'purchasing'])) {
                // Akses semua data
            } elseif ($user->hasRole(['hardware manager'])) {
                $bahanKeluarQuery->whereIn('divisi', ['RnD', 'Purchasing', 'Helper', 'Teknisi', 'OP', 'Produksi']);
                $bahanPembelianBahanQuery->whereIn('divisi', ['RnD', 'Purchasing', 'Helper', 'Teknisi', 'OP', 'Produksi']);
            } elseif ($user->hasRole(['rnd', 'rnd level 3'])) {
                $bahanKeluarQuery->where('divisi', 'RnD');
                $bahanPembelianBahanQuery->where('divisi', 'RnD');
            } elseif ($user->hasRole(['purchasing level 3', 'helper'])) {
                $bahanKeluarQuery->whereIn('divisi', ['Purchasing', 'Helper']);
                $bahanPembelianBahanQuery->whereIn('divisi', ['Purchasing', 'Helper']);
            } elseif ($user->hasRole(['teknisi level 3', 'teknisi', 'op', 'produksi'])) {
                $bahanKeluarQuery->whereIn('divisi', ['Teknisi', 'OP', 'Produksi']);
                $bahanPembelianBahanQuery->whereIn('divisi', ['Teknisi', 'OP', 'Produksi']);
            } elseif ($user->hasRole(['marketing manager', 'marketing', 'marketing level 3'])) {
                $bahanKeluarQuery->where('divisi', 'Marketing');
                $bahanPembelianBahanQuery->where('divisi', 'Marketing');
            } elseif ($user->hasRole(['software manager', 'software', 'publikasi'])) {
                $bahanKeluarQuery->whereIn('divisi', ['Software', 'Publikasi']);
                $bahanPembelianBahanQuery->whereIn('divisi', ['Software', 'Publikasi']);
            } elseif ($user->hasRole(['hse'])) {
                $bahanKeluarQuery->where('divisi', 'HSE');
                $bahanPembelianBahanQuery->where('divisi', 'HSE');
            } elseif ($user->hasRole(['sekretaris'])) {
                $bahanKeluarQuery->where('divisi', 'Sekretaris');
                $bahanPembelianBahanQuery->where('divisi', 'Sekretaris');
            } elseif ($user->hasRole('administrasi')) {
                $bahanKeluarQuery->whereIn('divisi', ['HSE', 'Sekretaris', 'Administrasi']);
                $bahanPembelianBahanQuery->whereIn('divisi', ['HSE', 'Sekretaris', 'Administrasi']);
            }
            $jumlahBahanKeluar = $bahanKeluarQuery->where('status', 'Belum disetujui')->count();
            $jumlahPembelianBahan = $bahanPembelianBahanQuery->where('status', 'Belum disetujui')->count();
        }



        $jumlahBahanRusak = BahanRusak::where('status', 'Belum disetujui')->count();
        $jumlahBahanRetur = BahanRetur::where('status', 'Belum disetujui')->count();
        // $jumlahBahanKeluar = BahanKeluar::where('status', 'Belum disetujui')->count();


        // Simpan jumlah ke dalam session atau view composer
        view()->share('jumlahBahanRusak', $jumlahBahanRusak);
        view()->share('jumlahBahanRetur', $jumlahBahanRetur);
        view()->share('jumlahBahanKeluar', $jumlahBahanKeluar);
        view()->share('jumlahPembelianBahan', $jumlahPembelianBahan);

        return $next($request);
    }
}
