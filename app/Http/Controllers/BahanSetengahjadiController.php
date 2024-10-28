<?php

namespace App\Http\Controllers;

use App\Models\BahanSetengahjadi;
use Illuminate\Http\Request;

class BahanSetengahjadiController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-bahan-setengahjadi', ['only' => ['index']]);
        $this->middleware('permission:detail-bahan-setengahjadi', ['only' => ['show']]);
    }

    public function index()
    {
        $bahanSetengahjadis = BahanSetengahjadi::with('bahanSetengahjadiDetails')->get();
        return view('pages.bahan-setengahjadis.index', compact('bahanSetengahjadis'));
    }

    public function show($id)
    {
        $bahanSetengahjadi = BahanSetengahjadi::with('bahanSetengahjadiDetails.dataBahan.dataUnit')->findOrFail($id);
        return view('pages.bahan-setengahjadis.show', [
            'kode_transaksi' => $bahanSetengahjadi->kode_transaksi,
            'tgl_masuk' => $bahanSetengahjadi->tgl_masuk,
            'bahanSetengahjadiDetails' => $bahanSetengahjadi->bahanSetengahjadiDetails,
        ]);
    }
}
