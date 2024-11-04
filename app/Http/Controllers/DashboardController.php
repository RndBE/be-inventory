<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Unit;
use App\Models\Bahan;
use App\Models\DataFeed;
use App\Models\Purchase;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\JenisBahan;
use App\Models\BahanKeluar;
use Illuminate\Http\Request;
use App\Models\ProdukProduksi;
use App\Models\PurchaseDetail;
use App\Models\BahanKeluarDetails;
use Illuminate\Support\Facades\DB;
use App\Models\BahanSetengahjadiDetails;
use App\Models\Produksi;
use App\Models\Projek;
use App\Models\ProjekRnd;

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

        $totalPengajuanBahanKeluar = BahanKeluar::where('status', '=', 'Belum disetujui')->count();
        $totalPengajuanBahanRetur = BahanRetur::where('status', '=', 'Belum disetujui')->count();
        $totalPengajuanBahanRusak = BahanRusak::where('status', '=', 'Belum disetujui')->count();
        $prosesProduksi = Produksi::where('status', 'Dalam proses')
            ->with(['produksiDetails' => function ($query) {
                $query->select('produksi_id', 'jml_bahan', 'used_materials', 'sub_total');
            }])
            ->get()
            ->map(function ($produksi) {
                $totalSubTotal = $produksi->produksiDetails->sum('sub_total'); // Sum of all sub_totals for each production
                $totalBahan = $produksi->produksiDetails->sum('jml_bahan'); // Total bahan needed
                $totalUsed = $produksi->produksiDetails->sum('used_materials'); // Total used materials

                $completionPercentage = $totalBahan > 0 ? round(($totalUsed / $totalBahan) * 100) : 0; // Calculate percentage

                $produksi->total_subtotal = $totalSubTotal;
                $produksi->completion_percentage = $completionPercentage;

                return $produksi;
            });
        $projeks = Projek::where('status', 'Dalam proses')
            ->with(['projekDetails' => function ($query) {
                $query->select('projek_id', 'sub_total', 'jml_bahan', 'used_materials');
            }])
            ->get()
            ->map(function ($projek) {
                $totalSubTotalProjek = $projek->projekDetails->sum('sub_total'); // Sum of all sub_totals for each production
                $totalBahanProjek = $projek->projekDetails->sum('jml_bahan'); // Total bahan needed
                $totalUsedProjek = $projek->projekDetails->sum('used_materials'); // Total used materials

                $completionPercentageProjek = $totalBahanProjek > 0 ? round(($totalUsedProjek / $totalBahanProjek) * 100) : 0; // Calculate percentage

                $projek->total_subtotal = $totalSubTotalProjek;
                $projek->completion_percentage_projek = $completionPercentageProjek;

                return $projek;
            });

        $projeks_rnd = ProjekRnd::where('status', 'Dalam proses')
            ->with(['projekRndDetails' => function ($query) {
                $query->select('projek_rnd_id', 'jml_bahan', 'used_materials', 'sub_total');
            }])
            ->get()
            ->map(function ($projekrnd) {
                $totalSubTotalProjekRnd = $projekrnd->projekRndDetails->sum('sub_total'); // Sum of all sub_totals for each production
                $totalBahanProjekRnd = $projekrnd->projekRndDetails->sum('jml_bahan'); // Total bahan needed
                $totalUsedProjekRnd = $projekrnd->projekRndDetails->sum('used_materials'); // Total used materials

                $completionPercentageProjekRnd = $totalBahanProjekRnd > 0 ? round(($totalUsedProjekRnd / $totalBahanProjekRnd) * 100) : 0; // Calculate percentage

                $projekrnd->total_subtotal = $totalSubTotalProjekRnd;
                $projekrnd->completion_percentage_projekrnd = $completionPercentageProjekRnd;

                return $projekrnd;
            });

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


        // Retrieve total quantities for each material in bahan_setengahjadi_details
        $materialsData = BahanSetengahjadiDetails::select('bahan_id', DB::raw('SUM(sisa) as total_qty'))
            ->groupBy('bahan_id')
            ->get();

            // Prepare data for the pie chart
        $chartLabels = [];
        $chartData = [];

        foreach ($materialsData as $data) {
            $material = Bahan::find($data->bahan_id); // Get material name from bahan table
            $chartLabels[] = $material->nama_bahan; // Assuming 'name' is a column in bahan
            $chartData[] = $data->total_qty;
        }

        return view('pages/dashboard/dashboard', compact('totalBahan', 'totalJenisBahan', 'totalProdukProduksi', 'totalSatuanUnit', 'dates', 'chartDataMasuk', 'chartDataKeluar',
        'availableYears', 'year', 'period', 'totalPengajuanBahanKeluar','totalPengajuanBahanRetur','totalPengajuanBahanRusak', 'chartLabels', 'chartData','prosesProduksi','projeks','projeks_rnd'));
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
