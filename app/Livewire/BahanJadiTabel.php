<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\BahanJadi;
use Livewire\WithPagination;

class BahanJadiTabel extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 5;
    public $id_bahanJadis;
    public function render()
    {
        $bahanJadis = BahanJadi::with('bahanJadiDetails.dataBahan')->orderBy('id', 'desc')
        ->where('tgl_masuk', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.bahan-jadi-tabel', [
            'bahanJadis' => $bahanJadis,
        ]);
    }

    public function deleteBahanJadis(int $id)
    {
        $this->id_bahanJadis = $id;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
