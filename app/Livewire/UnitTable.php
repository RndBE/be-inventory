<?php

namespace App\Livewire;

use App\Models\Unit;
use Livewire\Component;
use Livewire\WithPagination;

class UnitTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_unit, $nama;
    public $isDeleteModalOpen = false;
    public $isEditModalOpen = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function editUnit(int $id)
    {
        $Data = Unit::findOrFail($id);
        $this->id_unit = $id;
        $this->nama = $Data->nama;
        $this->isEditModalOpen = true;
    }

    public function deleteUnit(int $id)
    {
        $this->id_unit = $id;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
        $this->isEditModalOpen = false;
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
}
