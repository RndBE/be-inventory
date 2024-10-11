<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProdukProduksi;

class ProdukProduksiTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 5;
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
        $produkproduksis = ProdukProduksi::with('produkProduksiDetails.dataBahan')->orderBy('id', 'desc')
        ->where('nama_produk', 'like', '%' . $this->search . '%')
            ->when($this->filter === 'Produk Jadi', function ($query) {
                return $query->where('jenis_produksi', 'Produk Jadi');
            })
            ->when($this->filter === 'Produk Setengah Jadi', function ($query) {
                return $query->where('jenis_produksi', 'Produk Setengah Jadi');
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
