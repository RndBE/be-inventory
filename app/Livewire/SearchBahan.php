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
    public $selectedIndex = -1;


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
        $this->search_results = Bahan::with('dataUnit')
            ->where(function($query) {
                $query->where('nama_bahan', 'like', '%' . $this->query . '%')
                    ->orWhere('kode_bahan', 'like', '%' . $this->query . '%');
            })
            ->take($this->how_many)
            ->get();

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
    }

    public function selectPrevious()
    {
        if ($this->selectedIndex > 0) {
            $this->selectedIndex--;
        } else {
            $this->selectedIndex = $this->search_results->count() - 1; // Kembali ke bawah jika sudah di atas
        }
    }


    public function selectCurrent()
    {
        if ($this->selectedIndex >= 0 && $this->selectedIndex < $this->search_results->count()) {
            $this->selectBahan($this->search_results[$this->selectedIndex]->id);
        }
    }


}
