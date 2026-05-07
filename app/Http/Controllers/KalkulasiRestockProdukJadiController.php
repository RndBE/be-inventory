<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KalkulasiRestockProdukJadiController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-kalkulasi-restock-produk-jadi', ['only' => ['index']]);
    }

    public function index()
    {
        return view('pages.kalkulasi-restock-produk-jadi.index');
    }
}
