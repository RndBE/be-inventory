<?php

namespace App\Livewire;

use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_supplier, $nama;

    public function editSupplier(int $id)
    {
        $Data = Supplier::findOrFail($id);
        $this->id_supplier = $id;
        $this->nama = $Data->nama;
    }

    public function deleteSupplier(int $id)
    {
        $this->id_supplier = $id;
    }

    public function render()
    {
        $Data = Supplier::orderBy('id', 'desc')
        ->where('nama', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.supplier-table', [
            'suppliers' => $Data,
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
