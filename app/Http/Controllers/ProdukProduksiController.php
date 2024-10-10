<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProdukProduksiController extends Controller
{
    public function index()
    {
        return view('pages.produk-produksis.index');
    }

    public function create()
    {
        return view('pages.produk-produksis.create');
    }
}
