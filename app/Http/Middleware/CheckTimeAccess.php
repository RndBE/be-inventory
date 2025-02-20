<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTimeAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        // Set zona waktu
        date_default_timezone_set('Asia/Jakarta');

        // Ambil waktu sekarang
        $currentTime = Carbon::now()->format('H:i');

        // Cek jika waktu akses lebih dari 11:45 atau kurang dari 07:00
        if ($currentTime >= '11:45' || $currentTime < '07:00') {
            // Redirect ke halaman lain, misalnya ke dashboard atau halaman pemberitahuan
            return response()->view('pages.utility.bataspengajuan', [], 403);
        }

        return $next($request);
    }
}
