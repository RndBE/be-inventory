<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchBahanProdukSample extends Component
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
        $this->search_results = collect();  // Lebih baik menggunakan collect() untuk koleksi kosong
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

        public function selectBahan($bahanId, $type)
    {
        if ($type === 'setengahjadi') {
            $bahanSetengahJadiDetail = BahanSetengahjadiDetails::with('bahanSetengahjadi')
                ->where('id', $bahanId)
                ->first();

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
            }
        } else {
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
    }


    public function render()
    {
        // Ambil data dari tabel Bahan
        $bahanResults = Bahan::with('dataUnit', 'purchaseDetails')
            ->where('status', 'Digunakan')
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

        // Pencarian di tabel Bahan Setengah Jadi Details
        $bahanSetengahJadiResults = BahanSetengahjadiDetails::with('bahanSetengahjadi', 'dataBahan.dataUnit')
            ->where('sisa', '>', 0)
            ->where(function ($query) {
                $query->where('nama_bahan', 'like', '%' . $this->query . '%')
                    ->orWhere('serial_number', 'like', '%' . $this->query . '%')
                    ->orWhereIn('nama_bahan', function ($sub) {
                        $sub->select('nama_bahan')
                            ->from('bahan')
                            ->where('kode_bahan', 'like', '%' . $this->query . '%');
                    });
            })
            ->get()
            ->map(function ($bahanSetengahJadiDetail) {
                $kodeBahan = Bahan::where('nama_bahan', $bahanSetengahJadiDetail->nama_bahan)
                    ->value('kode_bahan');

                $gambarBahan = Bahan::where('nama_bahan', $bahanSetengahJadiDetail->nama_bahan)
                    ->value('gambar');
                return [
                    'type' => 'setengahjadi',
                    'id' => $bahanSetengahJadiDetail->id,
                    'nama' => $bahanSetengahJadiDetail->nama_bahan,
                    'gambar' => $gambarBahan ?? null,
                    'serial_number' => $bahanSetengahJadiDetail->serial_number ?? '-',
                    'kode' => $kodeBahan ?? '-',
                    'stok' => $bahanSetengahJadiDetail->sisa,
                    'unit' => 'Pcs',
                ];
        });

        $merged = collect($bahanResults)->merge($bahanSetengahJadiResults)->sortBy('nama')->values();

        // Implement manual pagination karena ini bukan query builder
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $totalPages = ceil($merged->count() / $this->perPage);

        if ($currentPage > $totalPages && $totalPages > 0) {
            $currentPage = $totalPages;
        }

        $items = $merged->slice(($currentPage - 1) * $this->perPage, $this->perPage)->all();

        $paginated = new LengthAwarePaginator(
            $items,
            $merged->count(),
            $this->perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()] // penting untuk mempertahankan query
        );

        return view('livewire.search-bahan-produk-sample', [
            'bahanList' => $paginated,
        ]);
    }
}

