<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use Illuminate\Support\Collection;
use App\Models\BahanSetengahjadiDetails;

class SearchBahanProduksi extends Component
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
        return view('livewire.search-bahan-produksi');
    }


    public function updatedQuery()
    {
        // Pencarian di tabel Bahan
        $bahanResults = Bahan::with('dataUnit', 'purchaseDetails')
            ->where(function ($query) {
                $query->where('nama_bahan', 'like', '%' . $this->query . '%')
                    ->orWhere('kode_bahan', 'like', '%' . $this->query . '%');
            })
            ->get()
            ->map(function ($bahan) {
                return (object) [
                    'type' => 'bahan',
                    'id' => $bahan->id,
                    'nama' => $bahan->nama_bahan,
                    'kode' => $bahan->kode_bahan,
                    'stok' => $bahan->purchaseDetails->sum('sisa'),
                    'unit' => $bahan->dataUnit->nama,
                ];
            });

        // Pencarian di tabel Bahan Setengah Jadi Details
        $bahanSetengahJadiResults = BahanSetengahjadiDetails::with('bahanSetengahjadi', 'dataUnit')
            ->where('nama_produk', 'like', '%' . $this->query . '%')
            ->get()
            ->map(function ($bahanSetengahJadi) {
                return (object) [
                    'type' => 'setengahjadi',
                    'id' => $bahanSetengahJadi->id,
                    'nama' => $bahanSetengahJadi->nama_produk,
                    'kode' => $bahanSetengahJadi->bahanSetengahjadi->kode_transaksi,
                    'stok' => $bahanSetengahJadi->qty,
                    'unit' => $bahanSetengahJadi->dataUnit->nama,
                ];
            });

        // Gabungkan hasil dari kedua tabel
        $this->search_results = collect(array_merge($bahanResults->toArray(), $bahanSetengahJadiResults->toArray()));

        // Filter hasil hanya yang memiliki stok > 0
        $this->search_results = $this->search_results->filter(function ($item) {
            return $item->stok > 0;
        })->take($this->how_many);

        // Reset selected index
        $this->selectedIndex = -1;
    }

    public function selectBahan($bahanId)
    {
        // Cek apakah ID ada di hasil pencarian dari Bahan Setengah Jadi atau Bahan
        $bahanSetengahJadi = BahanSetengahjadiDetails::with('bahanSetengahjadi', 'dataUnit')->find($bahanId);

        if ($bahanSetengahJadi) {
            // Emit event untuk mengirim data bahan setengah jadi yang dipilih
            $this->dispatch('bahanSetengahJadiSelected', $bahanSetengahJadi);
        } else {
            // Jika tidak ditemukan, cari di tabel Bahan
            $bahan = Bahan::with('dataUnit')->find($bahanId);
            if ($bahan) {
                // Emit event untuk mengirim data bahan yang dipilih
                $this->dispatch('bahanSelected', $bahan);
            } else {
                // Jika tidak ditemukan di kedua tabel
                session()->flash('message', 'Bahan tidak ditemukan.');
            }
        }

        // Reset query setelah memilih bahan
        $this->resetQuery();
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

    public function selectNext()
    {
        if ($this->selectedIndex < $this->search_results->count() - 1) {
            $this->selectedIndex++;
        } else {
            $this->selectedIndex = 0; // Kembali ke atas jika sudah di bawah
        }
        // foreach ($this->search_results as $bahan) {
        //     $bahan->total_stok = $bahan->purchaseDetails->sum('sisa');
        // }
    }

    public function selectPrevious()
    {
        if ($this->selectedIndex > 0) {
            $this->selectedIndex--;
        } else {
            $this->selectedIndex = $this->search_results->count() - 1; // Kembali ke bawah jika sudah di atas
        }
        // foreach ($this->search_results as $bahan) {
        //     $bahan->total_stok = $bahan->purchaseDetails->sum('sisa');
        // }
    }


    public function selectCurrent()
    {

        if ($this->selectedIndex >= 0 && $this->selectedIndex < $this->search_results->count()) {
            $this->selectBahan($this->search_results[$this->selectedIndex]->id);
        }
    }
}
