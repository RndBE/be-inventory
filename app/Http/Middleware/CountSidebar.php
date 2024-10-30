<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use Illuminate\Http\Request;
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
        $jumlahBahanRusak = BahanRusak::where('status', 'Belum disetujui')->count();
        $jumlahBahanRetur = BahanRetur::where('status', 'Belum disetujui')->count();
        $jumlahBahanKeluar = BahanKeluar::where('status', 'Belum disetujui')->count();

        // Simpan jumlah ke dalam session atau view composer
        view()->share('jumlahBahanRusak', $jumlahBahanRusak);
        view()->share('jumlahBahanRetur', $jumlahBahanRetur);
        view()->share('jumlahBahanKeluar', $jumlahBahanKeluar);

        return $next($request);
    }
}
