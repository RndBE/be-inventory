<?php

namespace App\Livewire;

use App\Models\BahanKeluar;
use Livewire\Component;
use Livewire\WithPagination;

class BahanKeluarTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 5;
    public $id_bahan_keluars, $status;

    public function render()
    {
        $bahan_keluars = BahanKeluar::with('bahanKeluarDetails')->orderBy('id', 'desc')
        ->where('tgl_keluar', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.bahan-keluar-table', [
            'bahan_keluars' => $bahan_keluars,
        ]);
    }

    public function editBahanKeluar(int $id)
    {
        $Data = BahanKeluar::findOrFail($id);
        $this->id_bahan_keluars = $id;
        $this->status = $Data->status;
    }

    public function deleteBahanKeluars(int $id)
    {
        $this->id_bahan_keluars = $id;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
