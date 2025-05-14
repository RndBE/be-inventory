<?php

namespace App\Livewire;

use App\Models\Projek;
use Livewire\Component;
use App\Models\Produksi;
use App\Models\ProdukSample;
use Livewire\WithPagination;

class ProdukSampleTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_produkSample;
    public $isDeleteModalOpen = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deleteProdukSample(int $id)
    {
        $this->id_produkSample = $id;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
    }

    public function render()
    {
        $produkSample = ProdukSample::with(['produkSampleDetails', 'bahanKeluar'])->orderBy('id', 'desc')
        ->where(function ($query) {
            $query->where('mulai_produk_sample', 'like', '%' . $this->search . '%')
                ->orWhere('selesai_produk_sample', 'like', '%' . $this->search . '%')
                ->orWhere('nama_produk_sample', 'like', '%' . $this->search . '%')
                ->orWhere('status', 'like', '%' . $this->search . '%')
                ->orWhere('kode_produk_sample', 'like', '%' . $this->search . '%');
        })
            ->paginate($this->perPage);

        return view('livewire.produk-sample-table', [
            'produkSample' => $produkSample,
        ]);
    }
}
