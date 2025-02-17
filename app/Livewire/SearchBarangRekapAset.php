<?php

namespace App\Livewire;

use App\Models\Unit;
use App\Models\User;
use Livewire\Component;
use App\Models\BarangAset;
use App\Models\JenisBahan;

class SearchBarangRekapAset extends Component
{
    public $searchBarangAset = '';

    public function render()
    {
        $units = Unit::all();
        $jenisBahan = JenisBahan::all();
        $barangAset = BarangAset::where('nama_barang', 'like', '%' . $this->searchBarangAset . '%')->get();
        $dataUser = User::all();

        return view('pages.rekap_aset.create', compact('units', 'suppliers', 'jenisBahan', 'barangAset', 'dataUser'));
    }

}
