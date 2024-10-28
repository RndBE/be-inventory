<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Purchase;
use Livewire\WithPagination;

class PurchaseTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_purchases;
    public function render()
    {
        $purchases = Purchase::with('purchaseDetails.dataBahan')->orderBy('id', 'desc')
        ->where('tgl_masuk', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.purchase-table', [
            'purchases' => $purchases,
        ]);
    }

    public function deletePurchases(int $id)
    {
        $this->id_purchases = $id;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
