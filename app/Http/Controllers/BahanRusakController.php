<?php

namespace App\Http\Controllers;

use App\Models\BahanRusak;
use Illuminate\Http\Request;

class BahanRusakController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-bahan-rusak', ['only' => ['index']]);
        $this->middleware('permission:detail-bahan-rusak', ['only' => ['show']]);
    }

    public function index()
    {
        $bahanRusaks = BahanRusak::with('bahanRusakDetails')->get();
        return view('pages.bahan-rusaks.index', compact('bahanRusaks'));
    }

    public function show($id)
    {
        $bahanRusak = BahanRusak::with('bahanRusakDetails.dataBahan.dataUnit')->findOrFail($id);
        return view('pages.bahan-rusaks.show', [
            'kode_transaksi' => $bahanRusak->kode_transaksi,
            'tgl_masuk' => $bahanRusak->tgl_masuk,
            'bahanRusakDetails' => $bahanRusak->bahanRusakDetails,
        ]);
    }
}
