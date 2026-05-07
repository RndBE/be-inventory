<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProdukProduksi;
use App\Models\ProdukProduksiDetail;

class KalkulasiRestockProdukJadiTable extends Component
{
    use WithPagination;

    public $search = "";
    public $perPage = 15;

    public $selectedIds = [];
    public $selectedProductId = '';
    public $productionQty = [];
    public $selectedSummary = [];
    public $kalkulasiResult = [];
    public $showResult = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function toggleItem(int $id)
    {
        if (in_array($id, $this->selectedIds)) {
            $this->selectedIds = array_values(array_filter(
                $this->selectedIds,
                fn($v) => $v !== $id
            ));
            unset($this->productionQty[$id]);
        } else {
            $this->selectedIds[] = $id;
            $this->productionQty[$id] = $this->productionQty[$id] ?? 1;
        }
        $this->showResult = false;
    }

    public function addSelectedProduct()
    {
        $id = (int) $this->selectedProductId;

        if ($id && !in_array($id, $this->selectedIds)) {
            $this->selectedIds[] = $id;
            $this->productionQty[$id] = $this->productionQty[$id] ?? 1;
        }

        $this->selectedProductId = '';
        $this->showResult = false;
    }

    public function removeSelectedProduct(int $id)
    {
        $this->selectedIds = array_values(array_filter(
            $this->selectedIds,
            fn($v) => $v !== $id
        ));

        unset($this->productionQty[$id]);
        $this->showResult = false;
    }

    public function kalkulasi()
    {
        if (empty($this->selectedIds)) {
            session()->flash('error', 'Pilih minimal satu Product Number untuk dikalkulasi.');
            return;
        }

        $selectedProducts = ProdukProduksi::with('dataBahan')
            ->whereIn('bahan_id', $this->selectedIds)
            ->get();

        if ($selectedProducts->isEmpty()) {
            session()->flash('error', 'Product Number yang dipilih belum memiliki data master produksi untuk acuan kalkulasi.');
            return;
        }

        $productIds = $selectedProducts->pluck('id');
        $productNumberMap = $selectedProducts->pluck('bahan_id', 'id');
        $this->selectedSummary = $selectedProducts
            ->map(function ($product) {
                return [
                    'bahan_id' => $product->bahan_id,
                    'nama_bahan' => optional($product->dataBahan)->nama_bahan ?? '-',
                    'jumlah_produksi' => max(1, (float)($this->productionQty[$product->bahan_id] ?? 1)),
                ];
            })
            ->values()
            ->all();

        // Ambil semua detail bahan dari master Produk Produksi, sama seperti pilihan di Produksi Produk Setengah Jadi.
        $productDetails = ProdukProduksiDetail::with(['dataBahan', 'dataBahan.dataUnit'])
            ->whereIn('produk_produksis_id', $productIds)
            ->whereNotNull('bahan_id')
            ->get();

        // Ambil stok real per bahan dari purchase_details (SUM sisa)
        $bahanIds = $productDetails->pluck('bahan_id')->filter()->unique()->values();
        $stokMap = \App\Models\PurchaseDetail::whereIn('bahan_id', $bahanIds)
            ->selectRaw('bahan_id, SUM(sisa) as total_sisa')
            ->groupBy('bahan_id')
            ->pluck('total_sisa', 'bahan_id');

        // Ambil harga terakhir per bahan dari purchase_details (berdasarkan data terbaru)
        $latestPrices = \App\Models\PurchaseDetail::whereIn('bahan_id', $bahanIds)
            ->orderBy('id', 'desc')
            ->get()
            ->unique('bahan_id')
            ->pluck('unit_price', 'bahan_id');

        // Agregasi kebutuhan per bahan
        $kebutuhan = [];
        foreach ($productDetails as $detail) {
            $bahanId = $detail->bahan_id;
            if (!$bahanId || !$detail->dataBahan) continue;

            $bahan = $detail->dataBahan;
            $qty   = (float)($detail->qty ?? $detail->jml_bahan ?? 0);
            $productNumberId = $productNumberMap[$detail->produk_produksis_id] ?? null;
            if (!$productNumberId) continue;
            $jumlahProduksi = max(1, (float)($this->productionQty[$productNumberId] ?? 1));
            $totalQty = $qty * $jumlahProduksi;

            if (!isset($kebutuhan[$bahanId])) {
                $kebutuhan[$bahanId] = [
                    'nama_bahan'     => $bahan->nama_bahan,
                    'kode_bahan'     => $bahan->kode_bahan ?? '-',
                    'unit'           => optional($bahan->dataUnit)->nama ?? '-',
                    'stok_sekarang'  => (float)($stokMap[$bahanId] ?? 0),
                    'harga_terakhir' => (float)($latestPrices[$bahanId] ?? 0),
                    'total_butuh'    => 0,
                    'breakdown'      => [],
                ];
            }
            $kebutuhan[$bahanId]['total_butuh'] += $totalQty;
            
            if (!isset($kebutuhan[$bahanId]['breakdown'][$productNumberId])) {
                $kebutuhan[$bahanId]['breakdown'][$productNumberId] = 0;
            }
            $kebutuhan[$bahanId]['breakdown'][$productNumberId] += $totalQty;
        }

        // Hitung kekurangan
        $result = [];
        foreach ($kebutuhan as $bahanId => $item) {
            $kekurangan = max(0, $item['total_butuh'] - $item['stok_sekarang']);
            $total_kekurangan_biaya = $kekurangan * $item['harga_terakhir'];
            
            $status = 'Cukup';
            if ($kekurangan > 0) {
                $status = 'Kurang';
            } elseif ($item['stok_sekarang'] == $item['total_butuh']) {
                $status = 'Pas';
            }

            $result[] = array_merge($item, [
                'kekurangan'             => $kekurangan,
                'total_kekurangan_biaya' => $total_kekurangan_biaya,
                'status'                 => $status,
            ]);
        }

        // Sort: kurang dulu
        usort($result, fn($a, $b) => $b['kekurangan'] <=> $a['kekurangan']);

        $this->kalkulasiResult = $result;
        $this->showResult = true;
    }

    public function exportExcel()
    {
        if (empty($this->kalkulasiResult)) {
            return;
        }

        $selectedProjects = ProdukProduksi::with('dataBahan')
            ->whereIn('bahan_id', $this->selectedIds)
            ->get();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\KalkulasiRestockProdukJadiExport($this->kalkulasiResult, $selectedProjects), 
            'Kalkulasi_Restock_Produk_Jadi.xlsx'
        );
    }

    public function resetKalkulasi()
    {
        $this->selectedIds = [];
        $this->selectedProductId = '';
        $this->productionQty = [];
        $this->selectedSummary = [];
        $this->kalkulasiResult = [];
        $this->showResult = false;
    }

    public function render()
    {
        $items = ProdukProduksi::with('dataBahan')
            ->whereHas('dataBahan', function ($query) {
                $query->where('nama_bahan', 'like', '%' . $this->search . '%')
                    ->orWhere('kode_bahan', 'like', '%' . $this->search . '%');
            })
            ->orderBy('id', 'desc')
            ->get();

        $selectedProducts = ProdukProduksi::with('dataBahan')
            ->whereIn('bahan_id', $this->selectedIds)
            ->get();

        return view('livewire.kalkulasi-restock-produk-jadi-table', [
            'items' => $items,
            'selectedProducts' => $selectedProducts,
        ]);
    }
}
