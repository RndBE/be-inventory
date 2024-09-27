<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\JenisBahan;
use Livewire\WithPagination;

class JenisBahanTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 5;
    public $id_jenisbahan, $nama;

    public function editJenisBahan(int $id)
    {
        $Data = JenisBahan::findOrFail($id);
        $this->id_jenisbahan = $id;
        $this->nama = $Data->nama;
    }

    public function deleteJenisBahan(int $id)
    {
        $this->id_jenisbahan = $id;
    }

    public function render()
    {
        $Data = JenisBahan::orderBy('id', 'desc')
        ->where('nama', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.jenis-bahan-table', [
            'jenisbahans' => $Data,
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
