<?php

namespace App\Http\Controllers;

use App\Models\BahanRusak;
use Illuminate\Http\Request;

class BahanRusakController extends Controller
{

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
