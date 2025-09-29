<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QcProdukJadiList;
use Illuminate\Http\Request;

class QcProdukJadiController extends Controller
{
    public function index(Request $request)
    {
        $query = QcProdukJadiList::with([
            'produksiProdukJadi',
            'qc1.dokumentasi',
            'qc2.dokumentasi',
            'ProdukJadi'
        ])
        ->whereNotNull('serial_number')
        ->where('serial_number', '!=', '') // pastikan tidak string kosong
        ->orderBy('created_at', 'desc');


        // optional: filter by grade
        if ($request->has('grade')) {
            $query->whereHas('qc1', function ($q) use ($request) {
                $q->where('grade', $request->grade);
            })->orWhereHas('qc2', function ($q) use ($request) {
                $q->where('grade', $request->grade);
            });
        }

        // optional: pencarian nama produk
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('produksiProdukJadi.dataProdukJadi', function ($q) use ($search) {
                $q->where('nama_produk', 'like', "%$search%");
            });
        }

        // ambil semua data tanpa pagination
        $qcList = $query->get();

        return response()->json([
            'success' => true,
            'data' => $qcList
        ]);
    }


    public function show($id)
    {
        $qc = QcProdukJadiList::with([
            'produksiProdukJadi.dataProdukJadi',
            'qc1.dokumentasi',
            'qc2.dokumentasi'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $qc
        ]);
    }
}

