<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bahan;


class BahanSearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('query', '');
        $perPage = $request->input('per_page', 12);

        // Ambil data bahan dengan relasi
        $bahanQuery = Bahan::with(['dataUnit', 'purchaseDetails', 'dataSupplier', 'jenisBahan'])
            ->whereHas('jenisBahan', function ($q) {
                $q->where('nama', '!=', 'Produksi');
            })
            ->when($query, function ($q) use ($query) {
                $q->where(function ($subQuery) use ($query) {
                    $subQuery->where('nama_bahan', 'like', '%' . $query . '%')
                            ->orWhere('kode_bahan', 'like', '%' . $query . '%');
                });
            });

        $bahanResults = $bahanQuery->paginate($perPage);

        // Format hasil
        $formatted = collect($bahanResults->items())->map(function ($bahan) {
            return [
                'type' => 'bahan',
                'id' => $bahan->id,
                'nama' => $bahan->nama_bahan,
                'gambar' => $bahan->gambar,
                'kode' => $bahan->kode_bahan,
                'penempatan' => $bahan->penempatan ?? '-',
                'supplier' => $bahan->dataSupplier->nama ?? '-',
                'stok' => $bahan->purchaseDetails->sum('sisa'),
                'unit' => optional($bahan->dataUnit)->nama ?? '-',
            ];
        });


        return response()->json([
            'status' => true,
            'message' => 'Data bahan berhasil diambil',
            'data' => [
                'current_page' => $bahanResults->currentPage(),
                'per_page' => $bahanResults->perPage(),
                'total' => $bahanResults->total(),
                'last_page' => $bahanResults->lastPage(),
                'items' => $formatted,
            ]
        ]);
    }
}
