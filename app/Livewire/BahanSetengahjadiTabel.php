<?php

namespace App\Livewire;

use App\Models\BahanSetengahjadi;
use Livewire\Component;
use Livewire\WithPagination;

class BahanSetengahjadiTabel extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_bahanSetengahjadis;
    public function render()
    {
        $bahanSetengahjadis = BahanSetengahjadi::with('bahanSetengahjadiDetails.dataBahan')->orderBy('id', 'desc')
        ->where('tgl_masuk', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.bahan-setengahjadi-tabel', [
            'bahanSetengahjadis' => $bahanSetengahjadis,
        ]);
    }

    public function deleteBahanSetengahjadis(int $id)
    {
        $this->id_bahanSetengahjadis = $id;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
