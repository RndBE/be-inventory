<?php

namespace App\Livewire;

use Livewire\Component;
use App\Helpers\LogHelper;
use App\Models\ProdukJadi;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

class ProdukJadiTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 15;
    public $id_produkjadi, $nama_produk, $sub_solusi, $gambar;
    public $filter = 'semua';
    public $isDeleteModalOpen = false;
    public $isEditModalOpen = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function editProdukJadi(int $id)
    {
        $data = ProdukJadi::findOrFail($id);
        $this->id_produkjadi = $id;
        $this->nama_produk = $data->nama_produk;
        $this->sub_solusi = $data->sub_solusi;
        $this->gambar = $data->gambar;
        $this->isEditModalOpen = true;
    }

    public function deleteProdukJadi(int $id)
    {
        $this->id_produkjadi = $id;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
        $this->isEditModalOpen   = false;
    }

    public function render()
    {
        $produkjadis = ProdukJadi::where(function ($query) {
                $query->where('nama_produk', 'like', '%' . $this->search . '%')
                    ->orWhere('sub_solusi', 'like', '%' . $this->search . '%');
            })
            ->orderBy('sub_solusi', 'asc') // urut per sub_solusi A-Z
            ->orderBy('id', 'desc')        // dalam sub_solusi, urutkan id terbaru
            ->paginate($this->perPage);

        return view('livewire.produk-jadi-table', [
            'produkjadis' => $produkjadis,
        ]);
    }

}
