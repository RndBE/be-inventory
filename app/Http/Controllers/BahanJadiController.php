<?php

namespace App\Http\Controllers;

use App\Models\BahanJadi;
use Illuminate\Http\Request;

class BahanJadiController extends Controller
{
    public function index()
    {
        $bahanJadis = BahanJadi::with('bahanJadiDetails')->get();
        return view('pages.bahan-jadis.index', compact('bahanJadis'));
    }

    public function show($id)
    {
        $bahanJadi = BahanJadi::with('bahanJadiDetails.dataBahan.dataUnit')->findOrFail($id);
        return view('pages.bahan-jadis.show', [
            'kode_transaksi' => $bahanJadi->kode_transaksi,
            'tgl_masuk' => $bahanJadi->tgl_masuk,
            'bahanJadiDetails' => $bahanJadi->bahanJadiDetails,
        ]);
    }
}
