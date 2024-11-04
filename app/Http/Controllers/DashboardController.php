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

class DashboardController extends Controller
{
    public function index()
    {
        // Get today and last 6 days
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $dates[Carbon::now()->subDays($i)->format('Y-m-d')] = 0;
        }

        // Fetch data from the last 7 days, grouped by date
        $last7DaysData = PurchaseDetail::selectRaw('DATE(purchases.tgl_masuk) as date, SUM(purchase_details.sub_total) as total_sub_total')
            ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
            ->where('purchases.tgl_masuk', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Fill in the dates array with actual data
        foreach ($last7DaysData as $dayData) {
            $dates[$dayData->date] = $dayData->total_sub_total;
        }

        // Separate labels (dates) and data (sub_totals) for the chart
        $chartLabels = array_keys($dates);
        $chartData = array_values($dates);

        $totalBahan = Bahan::whereDoesntHave('jenisBahan', function ($query) {
            $query->where('nama', 'Produksi');
        })->count();
        $totalJenisBahan = JenisBahan::where('nama', '!=', 'Produksi')->count();
        $totalSatuanUnit = Unit::count();
        $totalProdukProduksi = ProdukProduksi::count();

        return view('pages/dashboard/dashboard', compact('totalBahan', 'totalJenisBahan', 'totalProdukProduksi', 'totalSatuanUnit', 'chartLabels', 'chartData'));
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
