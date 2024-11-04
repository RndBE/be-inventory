<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Unit;
use App\Models\Bahan;
use App\Models\DataFeed;
use App\Models\Purchase;
use App\Models\JenisBahan;
use Illuminate\Http\Request;
use App\Models\ProdukProduksi;
use App\Models\PurchaseDetail;
use App\Models\BahanKeluarDetails;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalBahan = Bahan::whereDoesntHave('jenisBahan', function ($query) {
            $query->where('nama', 'Produksi');
        })->count();
        $totalJenisBahan = JenisBahan::where('nama', '!=', 'Produksi')->count();
        $totalSatuanUnit = Unit::count();
        $totalProdukProduksi = ProdukProduksi::count();

        $year = $request->input('year', Carbon::now()->year);
        $period = $request->input('period', '7_days');

        $dates = [];
        $chartDataMasuk = [];
        $chartDataKeluar = [];

        $currentYear = Carbon::now()->year;
        $availableYears = collect(range($currentYear - 5, $currentYear + 1));

        if ($period === '7_days') {
            // Get the last 7 days
            $dates = collect(range(0, 6))->map(function ($i) {
                return Carbon::today()->subDays($i)->toDateString();
            })->reverse()->values();

            $chartDataMasuk = $dates->map(function ($date) {
                return PurchaseDetail::whereHas('purchase', function ($query) use ($date) {
                    $query->whereDate('tgl_masuk', $date);
                })->sum('sub_total');
            })->toArray();

            $chartDataKeluar = $dates->map(function ($date) {
                return BahanKeluarDetails::whereHas('bahanKeluar', function ($query) use ($date) {
                    $query->whereDate('tgl_keluar', $date);
                })->sum('sub_total');
            })->toArray();
        } elseif ($period === 'monthly') {
            $dates = collect(range(1, 12))->map(function ($month) use ($year) {
                return Carbon::createFromDate($year, $month, 1)->format('F');
            });

            $chartDataMasuk = $dates->map(function ($month, $key) use ($year) {
                return PurchaseDetail::whereHas('purchase', function ($query) use ($year, $key) {
                    $query->whereYear('tgl_masuk', $year)
                        ->whereMonth('tgl_masuk', $key + 1);
                })->sum('sub_total');
            })->toArray();

            $chartDataKeluar = $dates->map(function ($month, $key) use ($year) {
                return BahanKeluarDetails::whereHas('bahanKeluar', function ($query) use ($year, $key) {
                    $query->whereYear('tgl_keluar', $year)
                        ->whereMonth('tgl_keluar', $key + 1);
                })->sum('sub_total');
            })->toArray();
        }

        return view('pages/dashboard/dashboard', compact('totalBahan', 'totalJenisBahan', 'totalProdukProduksi', 'totalSatuanUnit', 'dates', 'chartDataMasuk', 'chartDataKeluar',
        'availableYears', 'year', 'period'));
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
