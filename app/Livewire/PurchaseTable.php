<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Purchase;
use Livewire\WithPagination;

class PurchaseTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 5;
    public function render()
    {
        $purchases = Purchase::with('details')->orderBy('id', 'desc')
        ->where('tgl_masuk', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.purchase-table', [
            'purchases' => $purchases,
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
