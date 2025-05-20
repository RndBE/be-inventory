<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchBahanPengambilan extends Component
{
    use WithPagination;

    public $query = '';
    public $search_results;
    public $how_many;
    public $selectedIndex = -1;
    protected $queryString = ['query'];
    public $perPage = 12;

    public function mount()
    {
        $this->search_results = collect(); // Inisialisasi koleksi kosong
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function selectBahan($bahanId)
    {
        $bahan = Bahan::with('dataUnit', 'purchaseDetails')
            ->where('id', $bahanId)
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
        }
    }

    public function render()
    {
        // Ambil data dari tabel Bahan
        $bahanResults = Bahan::with('dataUnit', 'purchaseDetails')
            ->whereHas('jenisBahan', function ($query) {
                $query->where('nama', '!=', 'Produksi');
            })
            ->where(function ($query) {
                if ($this->query) {
                    $query->where('nama_bahan', 'like', '%' . $this->query . '%')
                        ->orWhere('kode_bahan', 'like', '%' . $this->query . '%');
                }
            })
            ->get()
            ->map(function ($bahan) {
                return [
                    'type' => 'bahan',
                    'id' => $bahan->id,
                    'nama' => $bahan->nama_bahan,
                    'gambar' => $bahan->gambar,
                    'kode' => $bahan->kode_bahan,
                    'penempatan' => $bahan->penempatan ?? '-',
                    'supplier' => $bahan->dataSupplier->nama ?? '-',
                    'stok' => $bahan->purchaseDetails->sum('sisa'),
                    'unit' => optional($bahan->dataUnit)->nama ?? '-',
                ];
            });

        // Manual pagination
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $totalPages = ceil($bahanResults->count() / $this->perPage);

        if ($currentPage > $totalPages && $totalPages > 0) {
            $currentPage = $totalPages;
        }

        $items = $bahanResults->slice(($currentPage - 1) * $this->perPage, $this->perPage)->all();

        $paginated = new LengthAwarePaginator(
            $items,
            $bahanResults->count(),
            $this->perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.search-bahan-pengambilan', [
            'bahanList' => $paginated,
        ]);
    }
}
