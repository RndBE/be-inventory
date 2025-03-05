<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use Illuminate\Support\Collection;
use App\Models\BahanSetengahjadiDetails;

class SearchPengambilanBahan extends Component
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
        return view('livewire.search-pengambilan-bahan');
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
        // $bahanSetengahJadiResults = BahanSetengahjadiDetails::with('bahanSetengahjadi', 'dataBahan.dataUnit')
        // // ->whereHas('bahanSetengahjadi', function ($query) {
        // //     $query->whereHas('produksiS'); // Pastikan bahan setengah jadi memiliki produksi
        // // })
        // ->where('sisa', '>', 0)
        // ->get()
        // ->map(function ($bahanSetengahJadiDetail) {
        //     return (object) [
        //         'type' => 'setengahjadi',
        //         'id' => $bahanSetengahJadiDetail->id,
        //         'nama' => $bahanSetengahJadiDetail->nama_bahan,
        //         // 'kode' => $bahanSetengahJadiDetail->dataBahan->kode_bahan,
        //         'serial_number' => $bahanSetengahJadiDetail->serial_number,
        //         'stok' => $bahanSetengahJadiDetail->sisa,
        //         'unit' => 'Pcs',
        //     ];
        // });

        //dd($bahanSetengahJadiResults);
        // Gabungkan hasil dari kedua tabel
        $this->search_results = collect(array_merge($bahanResults->toArray()));

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
        // $bahanSetengahJadiDetail = BahanSetengahjadiDetails::with('bahanSetengahjadi')
        //     ->where('id', $bahanId)
        //     ->first();

        // if ($bahanSetengahJadiDetail) {
        //     // Emit event untuk mengirim data bahan setengah jadi yang dipilih
        //     $bahanSetengahJadiData = (object) [
        //         'produk_id' => $bahanSetengahJadiDetail->id,
        //         'nama' => $bahanSetengahJadiDetail->nama_bahan,
        //         'serial_number' => $bahanSetengahJadiDetail->serial_number,
        //         // 'kode' => $bahanSetengahJadiDetail->bahanSetengahjadi->kode_bahan,
        //         'stok' => $bahanSetengahJadiDetail->sisa,

        //         'unit' => 'Pcs',
        //         'type' => 'setengahjadi', // Tambahkan ini agar dikenali sebagai bahan setengah jadi
        //         'bahan_setengahjadi_details_id' => $bahanSetengahJadiDetail->id, // ID unik bahan setengah jadi details
        //     ];

        //     $this->dispatch('bahanSetengahJadiSelected', $bahanSetengahJadiData);
        // } else {
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
        // }

        // Reset query setelah memilih bahan
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
            $this->selectBahan($this->search_results[$this->selectedIndex]->id);
        }
    }
}
