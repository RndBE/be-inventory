<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use Illuminate\Support\Collection;

class SearchBahan extends Component
{

    public $query;
    public $search_results;
    public $how_many;
    public $selectedIndex = -1; // -1 berarti tidak ada yang dipilih


    public function mount() {
        $this->query = '';
        $this->how_many = 5;
        $this->search_results = Collection::empty();
    }

    public function render()
    {
        return view('livewire.search-bahan');
    }

    public function updatedQuery()
    {
        $this->search_results = Bahan::with('dataUnit', 'purchaseDetails')
            ->where('nama_bahan', 'like', '%' . $this->query . '%')
            ->orWhere('kode_bahan', 'like', '%' . $this->query . '%')
            ->take($this->how_many)
            ->get();

        // Calculate total stock for each result
        foreach ($this->search_results as $bahan) {
            $bahan->total_stok = $bahan->purchaseDetails->sum('sisa');
        }

        $this->selectedIndex = -1; // Reset selected index
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
        }
        foreach ($this->search_results as $bahan) {
            $bahan->total_stok = $bahan->purchaseDetails->sum('sisa');
        }
    }

    public function selectPrevious()
    {
        if ($this->selectedIndex > 0) {
            $this->selectedIndex--;
        }
        foreach ($this->search_results as $bahan) {
            $bahan->total_stok = $bahan->purchaseDetails->sum('sisa');
        }
    }

    public function selectCurrent()
    {
        if ($this->selectedIndex >= 0 && $this->selectedIndex < $this->search_results->count()) {
            $this->selectBahan($this->search_results[$this->selectedIndex]->id);
        }
        foreach ($this->search_results as $bahan) {
            $bahan->total_stok = $bahan->purchaseDetails->sum('sisa');
        }
    }


}
