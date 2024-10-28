<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProdukProduksi;

class ProdukProduksiTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 15;
    public $id_produkproduksi, $nama_bahan;
    public $filter = 'semua';

    public function setFilter($value)
    {
        if ($value === 'semua') {
            $this->filter = null;
        } else {
            $this->filter = $value;
        }
        $this->resetPage();
    }

    public function render()
    {
        $produkproduksis = ProdukProduksi::orderBy('id', 'desc')
            ->whereHas('dataBahan', function($query) {
                $query->where('nama_bahan', 'like', '%' . $this->search . '%');
            })
            ->paginate($this->perPage);

        return view('livewire.produk-produksi-table', [
            'produkproduksis' => $produkproduksis,
        ]);

    }

    public function deleteprodukproduksis(int $id)
    {
        $this->id_produkproduksi = $id;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
