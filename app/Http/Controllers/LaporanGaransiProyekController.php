<?php

namespace App\Http\Controllers;

use App\Models\LaporanProyek;
use Illuminate\Http\Request;

class LaporanGaransiProyekController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-laporan-garansi-proyek', ['only' => ['index']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pages.laporan-garansi-proyek.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.laporan-garansi-proyek.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(LaporanProyek $laporanProyek)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LaporanProyek $laporanProyek)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LaporanProyek $laporanProyek)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LaporanProyek $laporanProyek)
    {
        //
    }
}
