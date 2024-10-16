<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use App\Models\ProdukProduksi;
use App\Models\ProdukProduksiDetail;
use App\Models\BahanSetengahjadiDetails;

class BahanProduksiCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $details = [];
    public $details_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;
    public $produkProduksi = [];
    public $selectedProdukId = null;
    public $warningMessage = [];
    public $jml_bahan = [];
    public $used_materials = [];
    public $jmlProduksi,$originalJmlBahan;

    protected $listeners = [
        'bahanSelected' => 'addToCart',
        'bahanSetengahJadiSelected' => 'addToCart'
    ];

    public function mount()
    {
        $this->produkProduksi = ProdukProduksi::all();
    }


    public function onProductSelected()
    {
        if ($this->selectedProdukId) {
            $this->cart = [];
            $this->qty = [];
            $this->warningMessage = [];
            $this->jml_bahan = [];
            $this->used_materials = [];
            $this->subtotals = [];
            $this->totalharga = 0;

            $produk = ProdukProduksi::with('produkProduksiDetails.dataBahan')->find($this->selectedProdukId);

            if ($produk) {
                foreach ($produk->produkProduksiDetails as $detail) {
                    if ($detail->dataBahan) {
                        $jmlBahan = $detail->jml_bahan ?? 0;
                        $usedMaterials = $detail->used_materials ?? 0;
                        $this->addToCart($detail->dataBahan, $jmlBahan, $usedMaterials);

                        $stock = $this->checkRemainingStock($detail->dataBahan->id);
                        if ($stock === 'Not Available') {
                            $this->warningMessage[$detail->dataBahan->id] = 'Not Available';
                        }
                    }
                }
            }
        }
    }

    public function addToCart($bahan, $jmlBahan, $usedMaterials)
    {
        if (is_array($bahan)) {
            $bahan = (object) $bahan;
        }
        //dd($bahan);
        $bahanId = $bahan->id ?? $bahan->bahan_id;
        $existingItemKey = array_search($bahanId, array_column($this->cart, 'id'));
        $currentStock = $this->checkRemainingStock($bahanId);

        if ($existingItemKey !== false) {
            // If item already exists, adjust the quantity based on current stock
            if ($this->qty[$bahanId] < $currentStock) {
                $this->qty[$bahanId]++;
                $this->calculateSubTotal($bahanId);
            }
        } else {
            // If it's a new item, initialize the quantity
            $this->cart[] = $bahan;
            if ($currentStock === 'Not Available') {
                $this->qty[$bahanId] = 0; // No stock available
            } elseif ($currentStock >= $jmlBahan) {
                $this->qty[$bahanId] = $jmlBahan; // Set qty to jmlBahan
            } else {
                $this->qty[$bahanId] = $currentStock; // Set qty to current stock
            }

            $this->originalJmlBahan[$bahanId] = $jmlBahan;
            $this->jml_bahan[$bahanId] = $jmlBahan;
            $this->used_materials[$bahanId] = $usedMaterials;
            $this->calculateSubTotal($bahanId);
        }
    }

    public function updateJmlBahan()
    {
        // dd($this->cart);
        foreach ($this->cart as $item) {
            $itemObject = (object) $item;
            $bahanId = $itemObject->id ?? $itemObject->bahan_id;
            //dd($itemObject);
            $currentStock = $this->checkRemainingStock($bahanId);
            $originalJmlBahan = $this->originalJmlBahan[$bahanId] ?? 0;

            // Calculate jml_bahan based on jmlProduksi
            if ($this->jmlProduksi < 0) {
                $this->jml_bahan[$bahanId] = $originalJmlBahan;
            } elseif ($this->jmlProduksi > 0) {
                $this->jml_bahan[$bahanId] = $this->jmlProduksi * $originalJmlBahan;
            } else {
                $this->jml_bahan[$bahanId] = 0;
            }

            if ($currentStock === 'Not Available') {
                $this->qty[$bahanId] = 0;
            } elseif ($currentStock >= $this->jml_bahan[$bahanId]) {
                $this->qty[$bahanId] = $this->jml_bahan[$bahanId];
            } else {
                $this->qty[$bahanId] = $currentStock;
            }
            $this->calculateSubTotal($bahanId);
        }
    }

    public function calculateSubTotal($itemId)
    {
        $unitPrice = isset($this->details[$itemId]) ? intval($this->details[$itemId]) : 0;
        $qty = isset($this->qty[$itemId]) ? intval($this->qty[$itemId]) : 0;

        $this->subtotals[$itemId] = $unitPrice * $qty;
        $this->calculateTotalHarga();
    }

    public function calculateTotalHarga()
    {
        $this->totalharga = array_sum($this->subtotals);
    }



    public function checkRemainingStock($itemId)
    {
        $item = Bahan::find($itemId);

        if ($item) {
            if ($item->jenisBahan->nama === 'Produksi') {
                $bahanSetengahjadiDetails = $item->bahanSetengahjadiDetails()
                    ->where('sisa', '>', 0)
                    ->with(['bahanSetengahjadi' => function ($query) {
                        $query->orderBy('tgl_masuk', 'asc');
                    }])->get();
                $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');

                // Ensure qty exists before passing it
                $currentQty = isset($this->qty[$itemId]) ? $this->qty[$itemId] : 0;
                $this->updateUnitPriceAndSubtotalBahanSetengahJadi($itemId, $currentQty, $bahanSetengahjadiDetails);
            } elseif ($item->jenisBahan->nama !== 'Produksi') {
                // Get purchase details with stock greater than 0
                $purchaseDetails = $item->purchaseDetails()
                    ->where('sisa', '>', 0)
                    ->with(['purchase' => function ($query) {
                        $query->orderBy('tgl_masuk', 'asc');
                    }])->get();
                $totalAvailable = $purchaseDetails->sum('sisa');

                // Ensure qty exists before passing it
                $currentQty = isset($this->qty[$itemId]) ? $this->qty[$itemId] : 0;
                $this->updateUnitPriceAndSubtotal($itemId, $currentQty, $purchaseDetails);
            }

            if ($totalAvailable <= 0) {
                return 'Not Available';
            }
            return $totalAvailable;
        }
        return 0;
    }




    public function updateQuantity($itemId)
    {
        $requestedQty = $this->qty[$itemId] ?? 0;
        $item = Bahan::find($itemId);

        if ($item) {
            if ($item->jenisBahan->nama === 'Produksi') {
                $bahanSetengahjadiDetails = $item->bahanSetengahjadiDetails()
                    ->where('sisa', '>', 0)
                    ->with(['bahanSetengahjadi' => function ($query) {
                        $query->orderBy('tgl_masuk', 'asc');
                    }])->get();

                $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
                if ($requestedQty > $totalAvailable) {
                    $this->qty[$itemId] = $totalAvailable;
                } elseif ($requestedQty < 0) {
                    $this->qty[$itemId] = null;
                } else {
                    $this->qty[$itemId] = $requestedQty;
                }
                $this->updateUnitPriceAndSubtotalBahanSetengahJadi($itemId, $this->qty[$itemId], $bahanSetengahjadiDetails);
            }

            elseif ($item->jenisBahan->nama !== 'Produksi') {
                $purchaseDetails = $item->purchaseDetails()
                    ->where('sisa', '>', 0)
                    ->with(['purchase' => function ($query) {
                        $query->orderBy('tgl_masuk', 'asc');
                    }])->get();

                $totalAvailable = $purchaseDetails->sum('sisa');
                if ($requestedQty > $totalAvailable) {
                    $this->qty[$itemId] = $totalAvailable;
                } elseif ($requestedQty < 0) {
                    $this->qty[$itemId] = null;
                } else {
                    $this->qty[$itemId] = $requestedQty;
                }
                $this->updateUnitPriceAndSubtotal($itemId, $this->qty[$itemId], $purchaseDetails);
            }
        }
    }

    protected function updateUnitPriceAndSubtotalBahanSetengahJadi($itemId, $qty, $bahanSetengahjadiDetails)
    {
        $remainingQty = $qty;
        $totalPrice = 0;
        $this->details_raw[$itemId] = [];
        $this->details[$itemId] = [];

        foreach ($bahanSetengahjadiDetails as $bahanSetengahjadiDetail) {
            if ($remainingQty <= 0) break;

            $availableQty = $bahanSetengahjadiDetail->sisa;

            if ($availableQty > 0) {
                $toTake = min($availableQty, $remainingQty);
                $totalPrice += $toTake * $bahanSetengahjadiDetail->unit_price;

                $this->details[$itemId][] = [
                    'kode_transaksi' => $bahanSetengahjadiDetail->bahanSetengahjadi->kode_transaksi,
                    'qty' => $toTake,
                    'unit_price' => $bahanSetengahjadiDetail->unit_price
                ];
                $remainingQty -= $toTake;
            }
        }

        $this->subtotals[$itemId] = $totalPrice;
        $this->calculateTotalHarga();
    }

    protected function updateUnitPriceAndSubtotal($itemId, $qty, $purchaseDetails)
    {
        $remainingQty = $qty;
        $totalPrice = 0;
        $this->details_raw[$itemId] = [];
        $this->details[$itemId] = [];

        foreach ($purchaseDetails as $purchaseDetail) {
            if ($remainingQty <= 0) break;

            $availableQty = $purchaseDetail->sisa;

            if ($availableQty > 0) {
                $toTake = min($availableQty, $remainingQty);
                $totalPrice += $toTake * $purchaseDetail->unit_price;

                $this->details[$itemId][] = [
                    'kode_transaksi' => $purchaseDetail->purchase->kode_transaksi,
                    'qty' => $toTake,
                    'unit_price' => $purchaseDetail->unit_price
                ];
                $remainingQty -= $toTake;
            }
        }

        $this->subtotals[$itemId] = $totalPrice;
        $this->calculateTotalHarga();
    }

    public function formatToRupiah($itemId)
    {
        // Pastikan untuk menghapus 'Rp.' dan mengonversi ke integer
        $this->details[$itemId] = intval(str_replace(['.', ' '], '', $this->details_raw[$itemId]));
        $this->details_raw[$itemId] = $this->details[$itemId];
        $this->calculateSubTotal($itemId); // Hitung subtotal setelah format
        $this->editingItemId = null; // Reset ID setelah selesai
    }

    public function editItem($itemId)
    {
        $this->editingItemId = $itemId; // Set ID item yang sedang diedit
        $this->details_raw[$itemId] = $this->details[$itemId]; // Ambil nilai untuk diedit
    }

    public function saveUnitPrice($itemId)
    {
        $this->formatToRupiah($itemId);
    }

    public function removeItem($itemId)
    {
        // Hapus item dari keranjang
        $this->cart = collect($this->cart)->filter(function ($item) use ($itemId) {
            return $item->id !== $itemId;
        })->values()->all(); // Menggunakan collect untuk memfilter dan mengembalikan array
        // Hapus subtotal yang terkait dengan item yang dihapus
        unset($this->subtotals[$itemId]);
        // Hitung ulang total harga setelah penghapusan
        $this->calculateTotalHarga();
    }

    public function getCartItemsForStorage()
    {
        $items = [];
        foreach ($this->cart as $item) {
            $itemId = $item->id;

            $items[] = [
                'id' => $itemId,
                'qty' => isset($this->qty[$itemId]) ? $this->qty[$itemId] : 0,
                'details' => isset($this->details[$itemId]) ? $this->details[$itemId] : [],
                'sub_total' => isset($this->subtotals[$itemId]) ? $this->subtotals[$itemId] : 0,
            ];
        }
        return $items;
    }

    public function render()
    {
        return view('livewire.bahan-produksi-cart', [
            'cartItems' => $this->cart,
        ]);
    }
}
