<?php

namespace App\Livewire;

use App\Models\ProdukJadis;
use Livewire\Component;
use Livewire\WithPagination;

class ProdukJadisTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_produk_jadis;
    public function render()
    {
        $produkJadis = ProdukJadis::with(['ProdukJadiDetails', 'qcProdukJadi'])->orderBy('id', 'desc')
        ->where(function ($query) {
            $query->where('tgl_masuk', 'like', '%' . $this->search . '%')
                ->orWhere('kode_transaksi', 'like', '%' . $this->search . '%')
                ->orWhereHas('ProdukJadiDetails', function ($query) {
                    $query->where('nama_produk', 'like', '%' . $this->search . '%')
                    ->orWhere('serial_number', 'like', '%' . $this->search . '%');
                });
        })
            ->paginate($this->perPage);

        return view('livewire.produk-jadis-table', [
            'produkJadis' => $produkJadis,
        ]);
    }

    public function deleteProdukJadis(int $id)
    {
        $this->id_produk_jadis = $id;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
