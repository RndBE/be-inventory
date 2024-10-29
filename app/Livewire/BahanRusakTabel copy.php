<?php

namespace App\Livewire;

use App\Models\BahanRusak;
use Livewire\Component;
use Livewire\WithPagination;

class BahanRusakTabel extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_bahanRusaks;
    public function render()
    {
        $bahanRusaks = BahanRusak::with('bahanRusakDetails.dataBahan')->orderBy('id', 'desc')
        ->where('tgl_pengajuan', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.bahan-rusak-tabel', [
            'bahanRusaks' => $bahanRusaks,
        ]);
    }

    public function deleteBahanRusaks(int $id)
    {
        $this->id_bahanRusaks = $id;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
