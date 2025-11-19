<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Produksi;
use Livewire\WithPagination;

class ProduksiTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_produksis;
    public $isDeleteModalOpen = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deleteProduksis(int $id)
    {
        $this->id_produksis = $id;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
    }

    public function render()
    {
        $produksis = Produksi::with(['produksiDetails', 'bahanKeluar'])->orderBy('id', 'desc')
        ->where(function ($query) {
            $query->where('mulai_produksi', 'like', '%' . $this->search . '%')
                ->orWhere('selesai_produksi', 'like', '%' . $this->search . '%')
                ->orWhere('jenis_produksi', 'like', '%' . $this->search . '%')
                ->orWhere('status', 'like', '%' . $this->search . '%')
                ->orWhere('kode_produksi', 'like', '%' . $this->search . '%')
                ->orWhere('keterangan', 'like', '%' . $this->search . '%')
                ->orWhereHas('dataBahan', function ($query) {
                    $query->where('nama_bahan', 'like', '%' . $this->search . '%');
                });
        })
            ->paginate($this->perPage);

        return view('livewire.produksi-table', [
            'produksis' => $produksis,
        ]);
    }
}
