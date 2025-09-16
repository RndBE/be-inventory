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
    public $selectedTab = 'bahanMasuk';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function setTab($tab)
    {
        $this->selectedTab = $tab;
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
        $purchases = Purchase::with('purchaseDetails.dataBahan', 'qcBahanMasuk')->orderBy('id', 'desc')
        ->where(function ($query) {
            $query->where('tgl_masuk', 'like', '%' . $this->search . '%')
                ->orWhere('kode_transaksi', 'like', '%' . $this->search . '%')
                ->orWhere('no_invoice', 'like', '%' . $this->search . '%')
                ->orWhereHas('purchaseDetails.dataBahan', function ($query) {
                    $query->where('nama_bahan', 'like', '%' . $this->search . '%');
                });
        })
        ->when($this->selectedTab === 'bahanMasuk', function ($query) {
            // Hanya ambil kode transaksi bukan retur
            $query->where('kode_transaksi', 'not like', 'BRT%');
        })
        ->when($this->selectedTab === 'bahanRetur', function ($query) {
            // Hanya ambil kode transaksi retur
            $query->where('kode_transaksi', 'like', 'BRT%');
        })
        ->paginate($this->perPage);

        return view('livewire.purchase-table', [
            'purchases' => $purchases,
        ]);
    }
}
