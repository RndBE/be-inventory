<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProduksiProdukJadi;

class ProduksiProdukJadiTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_produksi_produkjadis;
    public $isDeleteModalOpen = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deleteProduksiProdukJadi(int $id)
    {
        $this->id_produksi_produkjadis = $id;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
    }

    public function render()
    {

        $produksi_produkjadis = ProduksiProdukJadi::with(['produksiProdukJadiDetails', 'bahanKeluar', 'dataProdukJadi'])->orderBy('id', 'desc')
        ->where(function ($query) {
            $query->where('mulai_produksi', 'like', '%' . $this->search . '%')
                ->orWhere('selesai_produksi', 'like', '%' . $this->search . '%')
                ->orWhere('status', 'like', '%' . $this->search . '%')
                ->orWhere('kode_produksi', 'like', '%' . $this->search . '%')
                ->orWhere('serial_number', 'like', '%' . $this->search . '%')
                ->orWhere('pengaju', 'like', '%' . $this->search . '%')
                ->orWhere('keterangan', 'like', '%' . $this->search . '%')
                ->orWhereHas('dataProdukJadi', function ($query) {
                    $query->where('nama_produk', 'like', '%' . $this->search . '%');
                });
        })
            ->paginate($this->perPage);

        return view('livewire.produksi-produk-jadi-table', [
            'produksi_produkjadis' => $produksi_produkjadis,
        ]);
    }
}
