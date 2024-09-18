<?php

namespace App\Livewire;

use App\Models\Bahan;
use App\Models\StokProduksi;
use Livewire\Component;

class StokProduksiTable extends Component
{
    public $search = '';
    public $perPage = 5;

    public function render()
    {
        $stokProduksis = StokProduksi::with('dataBahan.dataUnit')
        ->whereHas('dataBahan', function ($query) {
            $query->where('nama_bahan', 'like', '%' . $this->search . '%')
                ->orWhere('kode_bahan', 'like', '%' . $this->search . '%');
        })
            ->paginate($this->perPage);

        return view('livewire.stok-produksi-table', [
            'stokProduksis' => $stokProduksis
        ]);
    }
}
