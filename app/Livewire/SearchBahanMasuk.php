<?php

namespace App\Livewire;

use App\Models\Bahan;
use App\Models\PurchaseDetail;
use Livewire\Component;
use Illuminate\Support\Collection;

class SearchBahanMasuk extends Component
{

    public $query;
    public $search_results;
    public $how_many;
    public $selectedIndex = -1;

    public function mount()
    {
        $this->query = '';
        $this->how_many = 5;
        $this->search_results = Collection::empty();
    }

    public function render()
    {
        return view('livewire.search-bahan-masuk');
    }

    public function updatedQuery()
    {
        $this->search_results = Bahan::with('dataUnit', 'purchaseDetails')
        ->where(function ($query) {
            $query->where('nama_bahan', 'like', '%' . $this->query . '%')
                ->orWhere('kode_bahan', 'like', '%' . $this->query . '%');
        })
            ->get();

        // Hitung total stok untuk setiap hasil dan filter hanya yang total_stok > 0
        $this->search_results = $this->search_results->filter(function ($bahan) {
            $bahan->total_stok = $bahan->purchaseDetails->sum('sisa');
            return $bahan->total_stok > 0; // Hanya ambil yang total_stok tidak sama dengan 0
        })->take($this->how_many);

        $this->selectedIndex = -1;
    }


    public function loadMore()
    {
        $this->how_many += 5;
        $this->updatedQuery();
    }

    public function resetQuery()
    {
        $this->query = '';
        $this->how_many = 5;
        $this->search_results = Collection::empty();
    }

    public function selectBahan($bahanId)
    {
        $bahan = Bahan::with('dataUnit')->find($bahanId);

        // Emit event untuk mengirim data bahan yang dipilih
        $this->dispatch('bahanSelected', $bahan);

        // Reset query setelah memilih bahan
        $this->resetQuery();
    }

    public function selectNext()
    {
        if ($this->selectedIndex < $this->search_results->count() - 1) {
            $this->selectedIndex++;
        } else {
            $this->selectedIndex = 0; // Kembali ke atas jika sudah di bawah
        }
        foreach ($this->search_results as $bahan) {
            $bahan->total_stok = $bahan->purchaseDetails->sum('sisa');
        }
    }

    public function selectPrevious()
    {
        if ($this->selectedIndex > 0) {
            $this->selectedIndex--;
        } else {
            $this->selectedIndex = $this->search_results->count() - 1; // Kembali ke bawah jika sudah di atas
        }
        foreach ($this->search_results as $bahan) {
            $bahan->total_stok = $bahan->purchaseDetails->sum('sisa');
        }
    }


    public function selectCurrent()
    {
        foreach ($this->search_results as $bahan) {
            $bahan->total_stok = $bahan->purchaseDetails->sum('sisa');
        }
        if ($this->selectedIndex >= 0 && $this->selectedIndex < $this->search_results->count()) {
            $this->selectBahan($this->search_results[$this->selectedIndex]->id);
        }
    }


}
