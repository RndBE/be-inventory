<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Purchase;
use Livewire\WithPagination;

class PurchaseTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 25;
    public $id_purchases;
    public $isDeleteModalOpen = false;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function deletePurchases(int $id)
    {
        $this->id_purchases = $id;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
    }

    public function render()
    {
        $purchases = Purchase::with('purchaseDetails.dataBahan')->orderBy('id', 'desc')
        ->where(function ($query) {
            $query->where('tgl_masuk', 'like', '%' . $this->search . '%')
                ->orWhere('kode_transaksi', 'like', '%' . $this->search . '%')
                ->orWhereHas('purchaseDetails.dataBahan', function ($query) {
                    $query->where('nama_bahan', 'like', '%' . $this->search . '%');
                });
        })
        ->paginate($this->perPage);

        return view('livewire.purchase-table', [
            'purchases' => $purchases,
        ]);
    }
}
