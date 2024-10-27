<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use Illuminate\Http\Request;
use App\Models\DataFeed;
use App\Models\JenisBahan;
use App\Models\ProdukProduksi;
use App\Models\Unit;

class DashboardController extends Controller
{
    public function index()
    {
        $dataFeed = new DataFeed();

        $totalBahan = Bahan::whereDoesntHave('jenisBahan', function ($query) {
            $query->where('nama', 'Produksi');
        })->count();
        $totalJenisBahan = JenisBahan::count();
        $totalSatuanUnit = Unit::count();
        $totalProdukProduksi = ProdukProduksi::count();

        return view('pages/dashboard/dashboard', compact('dataFeed',
        'totalBahan', 'totalJenisBahan', 'totalProdukProduksi', 'totalSatuanUnit'));
    }

    /**
     * Displays the analytics screen
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function analytics()
    {
        return view('pages/dashboard/analytics');
    }

    /**
     * Displays the fintech screen
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function fintech()
    {
        return view('pages/dashboard/fintech');
    }
}
