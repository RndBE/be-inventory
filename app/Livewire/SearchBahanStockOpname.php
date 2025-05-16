<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use Illuminate\Support\Collection;
use App\Models\BahanSetengahjadiDetails;

class SearchBahanStockOpname extends Component
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
        return view('livewire.search-bahan-stock-opname');
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
        $bahanSetengahJadiResults = BahanSetengahjadiDetails::with('bahanSetengahjadi', 'dataBahan.dataUnit')
        ->get()
        ->map(function ($bahanSetengahJadiDetail) {
            return (object) [
                'type' => 'setengahjadi',
                'id' => $bahanSetengahJadiDetail->id,
                'nama' => $bahanSetengahJadiDetail->nama_bahan,
                // 'kode' => $bahanSetengahJadiDetail->dataBahan->kode_bahan,
                'serial_number' => $bahanSetengahJadiDetail->serial_number,
                'stok' => $bahanSetengahJadiDetail->sisa,
                'unit' => 'Pcs',
            ];
        });
        $this->search_results = collect(array_merge($bahanResults->toArray(), $bahanSetengahJadiResults->toArray()));

        // Reset selected index
        $this->selectedIndex = -1;

    }

    public function selectBahan($type, $id)
    {
        if ($type === 'setengahjadi') {
            $bahanSetengahJadiDetail = BahanSetengahjadiDetails::with('bahanSetengahjadi')
                ->where('id', $id)
                ->first();
            // dd($bahanSetengahJadiDetail);
            if ($bahanSetengahJadiDetail) {
                $bahanSetengahJadiData = (object) [
                    'produk_id' => $bahanSetengahJadiDetail->id,
                    'nama' => $bahanSetengahJadiDetail->nama_bahan,
                    'serial_number' => $bahanSetengahJadiDetail->serial_number,
                    'stok' => $bahanSetengahJadiDetail->sisa,
                    'unit' => 'Pcs',
                    'type' => 'setengahjadi',
                    'bahan_setengahjadi_details_id' => $bahanSetengahJadiDetail->id,
                ];

                $this->dispatch('bahanSetengahJadiSelected', $bahanSetengahJadiData);
            } else {
                session()->flash('message', 'Bahan setengah jadi tidak ditemukan.');
            }
        } elseif ($type === 'bahan') {
            $bahan = Bahan::with('dataUnit')
                ->where('id', $id)
                ->first();

            if ($bahan) {
                $bahanData = (object) [
                    'bahan_id' => $bahan->id,
                    'nama' => $bahan->nama_bahan,
                    'kode' => $bahan->kode_bahan,
                    'stok' => $bahan->purchaseDetails->sum('sisa'),
                    'unit' => $bahan->dataUnit->nama ?? 'N/A',
                    'type' => 'bahan',
                ];
                $this->dispatch('bahanSelected', $bahanData);
            } else {
                session()->flash('message', 'Bahan tidak ditemukan.');
            }
        }

        $this->resetQuery();
    }


    public function loadMore()
    {
        $this->how_many += 10;
        $this->updatedQuery();
    }

    public function resetQuery()
    {
        $this->query = '';
        $this->how_many = 10;
        $this->search_results = Collection::empty();
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
            $item = $this->search_results[$this->selectedIndex];
            $this->selectBahan($item->type, $item->id);
        }
    }

}
