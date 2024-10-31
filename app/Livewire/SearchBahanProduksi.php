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
            })->whereHas('jenisBahan', function ($query) {
                $query->where('nama', '!=', 'Produksi');
            })
            ->get()
            ->map(function ($bahan) {
                return (object) [
                    'type' => 'bahan',
                    'id' => $bahan->id,
                    'nama' => $bahan->nama_bahan,
                    'kode' => $bahan->kode_bahan,
                    'stok' => $bahan->purchaseDetails->sum('sisa'),
                    'unit' => optional($bahan->dataUnit)->nama ?? 'N/A',
                ];
            });

        // Pencarian di tabel Bahan Setengah Jadi Details
        $bahanSetengahJadiResults = Bahan::with('dataUnit', 'bahanSetengahjadiDetails')
        ->whereHas('bahanSetengahjadiDetails', function ($query) {
            $query->where('sisa', '>', 0); // Ensure there is some quantity in stock
        })
        ->where(function ($query) {
            $query->where('nama_bahan', 'like', '%' . $this->query . '%')
                ->orWhere('kode_bahan', 'like', '%' . $this->query . '%');
        })
        ->get()
        ->map(function ($bahanSetengahJadi) {
            // Calculate the total stock from bahan_setengahjadi_details (sum of 'sisa')
            $totalSisa = $bahanSetengahJadi->bahanSetengahjadiDetails->sum('sisa');
            return (object) [
                'type' => 'setengahjadi',
                'id' => $bahanSetengahJadi->id,
                'nama' => $bahanSetengahJadi->nama_bahan,
                'kode' => $bahanSetengahJadi->kode_bahan,
                'stok' => $totalSisa,
                'unit' => optional($bahanSetengahJadi->dataUnit)->nama ?? 'N/A',
            ];
        });
        //dd($bahanSetengahJadiResults);
        // Gabungkan hasil dari kedua tabel
        $this->search_results = collect(array_merge($bahanResults->toArray(), $bahanSetengahJadiResults->toArray()));

        // Filter hasil hanya yang memiliki stok > 0
        $this->search_results = $this->search_results->filter(function ($item) {
            return $item->stok >= 0;
        })->take($this->how_many);

        // Reset selected index
        $this->selectedIndex = -1;

    }

    public function selectBahan($bahanId)
    {
        // Cek apakah ID ada di hasil pencarian dari Bahan Setengah Jadi
        $bahanSetengahJadiDetail = BahanSetengahjadiDetails::with('bahanSetengahjadi')
            ->where('bahan_id', $bahanId) // Find by bahan_id as it's foreign key
            ->first();

        if ($bahanSetengahJadiDetail) {
            // Emit event untuk mengirim data bahan setengah jadi yang dipilih
            $bahanSetengahJadiData = (object) [
                'id' => $bahanSetengahJadiDetail->bahan_id,
                'nama' => $bahanSetengahJadiDetail->dataBahan->nama_bahan, // Ensure this property exists
                'kode' => $bahanSetengahJadiDetail->bahanSetengahjadi->kode_bahan,
                'stok' => $bahanSetengahJadiDetail->sisa,
                'unit' => $bahanSetengahJadiDetail->dataBahan->dataUnit->nama ?? 'N/A',
            ];
            $this->dispatch('bahanSetengahJadiSelected', $bahanSetengahJadiData);
        } else {
            // Jika tidak ditemukan, cari di tabel Bahan
            $bahan = Bahan::with('dataUnit')
                ->where('id', $bahanId)
                ->first();
                //dd($bahan);
            if ($bahan) {
                // dispatch event untuk mengirim data bahan yang dipilih
                $bahanData = (object) [
                    'id' => $bahan->id,
                    'nama' => $bahan->nama_bahan, // Use 'nama' instead of 'nama_bahan'
                    'kode' => $bahan->kode_bahan,
                    'stok' => $bahan->purchaseDetails->sum('sisa'),
                    'unit' => $bahan->dataUnit->nama ?? 'N/A',
                ];
                $this->dispatch('bahanSelected', $bahanData);
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
