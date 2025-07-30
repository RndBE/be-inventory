<?php

namespace App\Http\Controllers\API;

use App\Helpers\LogHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\Bahan;
use App\Models\JenisBahan;
use App\Models\Unit;
use App\Models\ProdukProduksi;
use App\Models\BahanKeluar;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\PurchaseDetail;
use App\Models\BahanKeluarDetails;
use App\Models\BahanSetengahjadiDetails;
use App\Models\Produksi;
use App\Models\Projek;
use App\Models\ProjekRnd;

class DashboardApiController extends Controller
{
    public function getStatistics()
    {
        return response()->json([
            'total_bahan' => Bahan::whereDoesntHave('jenisBahan', fn($q) => $q->where('nama', 'Produksi'))->count(),
            'total_jenis_bahan' => JenisBahan::where('nama', '!=', 'Produksi')->count(),
            'total_unit' => Unit::count(),
            'total_produk_produksi' => ProdukProduksi::count(),
        ]);
    }

    public function getPendingPengajuan()
    {
        return response()->json([
            'bahan_keluar' => BahanKeluar::where('status', 'Belum disetujui')->count(),
            'bahan_retur' => BahanRetur::where('status', 'Belum disetujui')->count(),
            'bahan_rusak' => BahanRusak::where('status', 'Belum disetujui')->count(),
        ]);
    }

    public function getBahanSisaTerbanyak()
    {
        $data = PurchaseDetail::with('dataBahan')
            ->select('bahan_id')
            ->selectRaw('SUM(sisa) as total_sisa')
            ->groupBy('bahan_id')
            ->orderByDesc('total_sisa')
            ->take(10)
            ->get();

        return response()->json($data);
    }

    public function getBahanSisaPalingSedikit()
    {
        $data = PurchaseDetail::with('dataBahan')
            ->select('bahan_id')
            ->selectRaw('SUM(sisa) as total_sisa')
            ->groupBy('bahan_id')
            ->orderBy('total_sisa')
            ->take(10)
            ->get();

        return response()->json($data);
    }

    public function getProduksiProses()
    {
        $data = Produksi::where('status', 'Dalam proses')
            ->with(['produksiDetails:id,produksi_id,jml_bahan,used_materials,sub_total'])
            ->get()
            ->map(function ($item) {
                $totalBahan = $item->produksiDetails->sum('jml_bahan');
                $totalUsed = $item->produksiDetails->sum('used_materials');
                $item->total_subtotal = $item->produksiDetails->sum('sub_total');
                $item->completion_percentage = $totalBahan > 0 ? round(($totalUsed / $totalBahan) * 100) : 0;
                return $item;
            });

        return response()->json($data);
    }

    public function getProjekProses()
    {
        $data = Projek::where('status', 'Dalam proses')
            ->with(['projekDetails:id,projek_id,qty,used_materials,sub_total'])
            ->get()
            ->map(function ($item) {
                $total = $item->projekDetails->sum('qty');
                $used = $item->projekDetails->sum('used_materials');
                $item->total_subtotal = $item->projekDetails->sum('sub_total');
                $item->completion_percentage_projek = $total > 0 ? round(($used / $total) * 100) : 0;
                return $item;
            });

        return response()->json($data);
    }

    public function getProjekRndProses()
    {
        $data = ProjekRnd::where('status', 'Dalam proses')
            ->with(['projekRndDetails:id,projek_rnd_id,jml_bahan,used_materials,sub_total'])
            ->get()
            ->map(function ($item) {
                $total = $item->projekRndDetails->sum('jml_bahan');
                $used = $item->projekRndDetails->sum('used_materials');
                $item->total_subtotal = $item->projekRndDetails->sum('sub_total');
                $item->completion_percentage_projekrnd = $total > 0 ? round(($used / $total) * 100) : 0;
                return $item;
            });

        return response()->json($data);
    }

    public function getChartData(Request $request)
    {
        $period = $request->input('period', '7_days');
        $year = $request->input('year', now()->year);

        $dates = [];
        $chartMasuk = [];
        $chartKeluar = [];

        if ($period == '7_days') {
            $dates = collect(range(0, 6))->map(fn($i) => now()->subDays($i)->toDateString())->reverse();
            $chartMasuk = $dates->map(fn($d) => PurchaseDetail::whereHas('purchase', fn($q) => $q->whereDate('tgl_masuk', $d))->sum('sub_total'));
            $chartKeluar = $dates->map(fn($d) => BahanKeluarDetails::whereHas('bahanKeluar', fn($q) => $q->whereDate('tgl_keluar', $d))->sum('sub_total'));
        } else {
            $dates = collect(range(1, 12))->map(fn($m) => Carbon::createFromDate($year, $m, 1)->format('F'));
            $chartMasuk = $dates->map(fn($_, $i) => PurchaseDetail::whereHas('purchase', fn($q) => $q->whereYear('tgl_masuk', $year)->whereMonth('tgl_masuk', $i + 1))->sum('sub_total'));
            $chartKeluar = $dates->map(fn($_, $i) => BahanKeluarDetails::whereHas('bahanKeluar', fn($q) => $q->whereYear('tgl_keluar', $year)->whereMonth('tgl_keluar', $i + 1))->sum('sub_total'));
        }

        return response()->json([
            'dates' => $dates,
            'masuk' => $chartMasuk,
            'keluar' => $chartKeluar,
        ]);
    }

    public function getBahanSetengahJadi()
    {
        $data = BahanSetengahjadiDetails::select('nama_bahan', DB::raw('SUM(sisa) as total_qty'))
            ->groupBy('nama_bahan')
            ->havingRaw('SUM(sisa) > 0')
            ->get();

        $totalQty = $data->sum('total_qty');

        $result = $data->map(function ($item) use ($totalQty) {
            return [
                'nama_bahan' => $item->nama_bahan,
                'total_qty' => $item->total_qty,
                'percentage' => $totalQty > 0 ? ($item->total_qty / $totalQty) * 100 : 0
            ];
        });

        return response()->json([
            'total_qty' => $totalQty,
            'data' => $result
        ]);
    }


    public function getSisaStokBahan(Request $request)
{
     try {
    $namaBahan = $request->query('nama_bahan'); // ambil parameter dari URL

    $query = PurchaseDetail::with([
            'dataBahan.jenisBahan',
            'dataBahan.dataUnit',
            'dataBahan.dataSupplier'
        ])
        ->select('bahan_id')
        ->selectRaw('SUM(sisa) as total_sisa')
        ->groupBy('bahan_id');

    // Filter berdasarkan nama_bahan (jika ada)
    if ($namaBahan) {
        $query->whereHas('dataBahan', function ($q) use ($namaBahan) {
            $q->where('nama_bahan', 'like', '%' . $namaBahan . '%');
        });
    }

    $data = $query->get()->map(function ($item) {
        $bahan = $item->dataBahan;

        return [
            'nama_bahan'   => $bahan->nama_bahan ?? '-',
            'kode_bahan'   => $bahan->kode_bahan ?? '-',
            'jenis_bahan'  => $bahan->jenisBahan->nama ?? '-',
            'satuan_unit'  => $bahan->dataUnit->nama ?? '-',
            'penempatan'   => $bahan->penempatan ?? '-',
            'supplier'     => $bahan->dataSupplier->nama ?? '-',
            'total_sisa'   => (float) $item->total_sisa,
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $data,
    ]);
} catch (\Throwable $e) {
        LogHelper::error('SISA STOK ERROR: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}




}
