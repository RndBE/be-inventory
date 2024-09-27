<?php

namespace App\Livewire;

use App\Models\Unit;
use Livewire\Component;
use Livewire\WithPagination;

class UnitTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 5;
    public $id_unit, $nama;

    public function editUnit(int $id)
    {
        $Data = Unit::findOrFail($id);
        $this->id_unit = $id;
        $this->nama = $Data->nama;
    }

    public function deleteUnit(int $id)
    {
        $this->id_unit = $id;
    }

    public function render()
    {
        $Data = Unit::orderBy('id', 'desc')
        ->where('nama', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.unit-table', [
            'units' => $Data,
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
