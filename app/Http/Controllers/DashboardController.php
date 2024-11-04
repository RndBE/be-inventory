<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Unit;
use App\Models\Bahan;
use App\Models\DataFeed;
use App\Models\JenisBahan;
use Illuminate\Http\Request;
use App\Models\ProdukProduksi;
use App\Models\PurchaseDetail;
use App\Models\BahanKeluarDetails;

class DashboardController extends Controller
{
    public function index()
    {
        $totalBahan = Bahan::whereDoesntHave('jenisBahan', function ($query) {
            $query->where('nama', 'Produksi');
        })->count();
        $totalJenisBahan = JenisBahan::where('nama', '!=', 'Produksi')->count();
        $totalSatuanUnit = Unit::count();
        $totalProdukProduksi = ProdukProduksi::count();

       // Get the last 7 days of dates
        $dates = collect(range(0, 6))->map(function ($i) {
            return Carbon::today()->subDays($i)->toDateString();
        })->reverse()->values();

        // Prepare data for "Bahan Masuk"
        $chartDataMasuk = $dates->map(function ($date) {
            return PurchaseDetail::whereHas('purchase', function ($query) use ($date) {
                $query->whereDate('tgl_masuk', $date);
            })->sum('sub_total');
        })->toArray();

        // Prepare data for "Bahan Keluar"
        $chartDataKeluar = $dates->map(function ($date) {
            return BahanKeluarDetails::whereHas('bahanKeluar', function ($query) use ($date) {
                $query->whereDate('tgl_keluar', $date);
            })->sum('sub_total');
        })->toArray();

        return view('pages/dashboard/dashboard', compact('totalBahan', 'totalJenisBahan', 'totalProdukProduksi', 'totalSatuanUnit', 'dates', 'chartDataMasuk', 'chartDataKeluar'));
    }

    /**
     * Displays the analytics screen
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    // public function analytics()
    // {
    //     return view('pages/dashboard/analytics');
    // }

    /**
     * Displays the fintech screen
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    // public function fintech()
    // {
    //     return view('pages/dashboard/fintech');
    // }
}
