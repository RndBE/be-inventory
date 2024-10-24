<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Produksi;
use Livewire\WithPagination;

class ProjekTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 5;
    public $id_produksis;
    public function render()
    {
        $produksis = Produksi::with(['produksiDetails', 'bahanKeluar'])->orderBy('id', 'desc')
        ->where('mulai_produksi', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.projek-table', [
            'produksis' => $produksis,
        ]);
    }

    public function deleteProduksis(int $id)
    {
        $this->id_produksis = $id;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
