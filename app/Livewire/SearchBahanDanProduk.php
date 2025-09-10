<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProdukJadiDetails;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchBahanDanProduk extends Component
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
                ->find($bahanId);

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
        } elseif ($type === 'jadi') {
            $produkJadiDetail = ProdukJadiDetails::with('dataProduk')
                ->find($bahanId);

            if ($produkJadiDetail) {
                $produkJadiData = (object) [
                    'produk_jadis_id' => $produkJadiDetail->id,
                    'nama' => $produkJadiDetail->nama_produk,
                    'serial_number' => $produkJadiDetail->serial_number,
                    'stok' => $produkJadiDetail->sisa,
                    'unit' => 'Pcs',
                    'type' => 'jadi',
                    'produk_jadi_details_id' => $produkJadiDetail->id,
                ];

                $this->dispatch('produkJadiSelected', $produkJadiData);
            }
        } else {
            $bahan = Bahan::with('dataUnit', 'purchaseDetails')
                ->find($bahanId);

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
            ->whereHas('jenisBahan', function ($query) {
                $query->where('nama', '!=', 'Produksi');
            })
            ->when($this->query, function ($query) {
                $query->where('nama_bahan', 'like', '%' . $this->query . '%')
                    ->orWhere('kode_bahan', 'like', '%' . $this->query . '%');
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

        // Bahan Setengah Jadi
        $bahanSetengahJadiResults = BahanSetengahjadiDetails::with('bahanSetengahjadi', 'dataBahan.dataUnit')
            ->where('sisa', '>', 0)
            ->when($this->query, function ($query) {
                $query->where('nama_bahan', 'like', '%' . $this->query . '%')
                    ->orWhere('serial_number', 'like', '%' . $this->query . '%');
            })
            ->get()
            ->map(function ($bahanSetengahJadiDetail) {
                return [
                    'type' => 'setengahjadi',
                    'id' => $bahanSetengahJadiDetail->id,
                    'nama' => $bahanSetengahJadiDetail->nama_bahan,
                    'gambar' => $bahanSetengahJadiDetail->gambar,
                    'kode' => $bahanSetengahJadiDetail->serial_number ?? '-',
                    'stok' => $bahanSetengahJadiDetail->sisa,
                    'unit' => 'Pcs',
                ];
            });

        // Produk Jadi
        $produkJadiResults = ProdukJadiDetails::with('dataProduk')
            ->where('sisa', '>', 0)
            ->when($this->query, function ($query) {
                $query->where('nama_produk', 'like', '%' . $this->query . '%')
                    ->orWhere('serial_number', 'like', '%' . $this->query . '%');
            })
            ->get()
            ->map(function ($produkJadiDetail) {
                return [
                    'type' => 'jadi',
                    'id' => $produkJadiDetail->id,
                    'nama' => $produkJadiDetail->nama_produk,
                    'gambar' => $produkJadiDetail->ProdukJadis->qcProdukJadi->produkJadi->gambar ?? null,
                    'kode' => $produkJadiDetail->serial_number ?? '-',
                    'stok' => $produkJadiDetail->sisa,
                    'unit' => 'Pcs',
                ];
            });

        // Gabungkan semua hasil
        $merged = collect()
            ->merge($bahanResults)
            ->merge($bahanSetengahJadiResults)
            ->merge($produkJadiResults)
            ->sortBy('nama')
            ->values();

        // Manual pagination
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
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.search-bahan-dan-produk', [
            'bahanList' => $paginated,
        ]);
    }

}
