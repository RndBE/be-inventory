<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use App\Models\ProdukProduksi;
use App\Models\ProdukProduksiDetail;
use App\Models\BahanSetengahjadiDetails;

class BahanPengajuanCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $jml_bahan = [];
    public $details = [];
    public $details_raw = [];
    public $subtotals = [];
    public $spesifikasi = [];
    public $totalharga = 0;
    public $editingItemId = 0;

    protected $listeners = ['bahanSelected' => 'addToCart', 'bahanSetengahJadiSelected' => 'addToCart'];

    public function mount()
    {
        // Load cart items from session if they exist
        // $this->loadCartFromSession();
    }

    public function addToCart($bahan)
    {
        if (is_array($bahan)) {
            $bahan = (object) $bahan;
        }
        // Periksa apakah properti 'type' ada sebelum mengaksesnya
        $isSetengahJadi = isset($bahan->type) && $bahan->type === 'setengahjadi';

        $existingItemKey = array_search($bahan->id, array_column($this->cart, 'id'));

        if ($existingItemKey !== false) {
            // $this->updateQuantity($bahan->id);
        } else {
            // Buat objek item
            $item = (object)[
                'id' => $bahan->id,
                'nama_bahan' => $isSetengahJadi ? $bahan->nama : Bahan::find($bahan->id)->nama_bahan,
                'stok' => $bahan->stok,
                'unit' => $bahan->unit,
            ];

            // Tambahkan item ke keranjang
            $this->cart[] = $item;
            $this->qty[$bahan->id] = null;
            $this->jml_bahan[$bahan->id] = null;
            $this->spesifikasi[$bahan->id] = null;
        }

        // Simpan ke sesi
        $this->saveCartToSession();
        $this->calculateSubTotal($bahan->id);
    }



    protected function saveCartToSession()
    {
        session()->put('cartItems', $this->getCartItemsForStorage());
    }

    protected function loadCartFromSession()
    {
        if (session()->has('cartItems')) {
            $storedItems = session()->get('cartItems');
            foreach ($storedItems as $storedItem) {
                $this->cart[] = (object) ['id' => $storedItem['id'], 'nama_bahan' => Bahan::find($storedItem['id'])->nama_bahan];
                $this->qty[$storedItem['id']] = $storedItem['qty'];
                $this->jml_bahan[$storedItem['id']] = $storedItem['jml_bahan'];
                $this->subtotals[$storedItem['id']] = $storedItem['sub_total'];
                $this->spesifikasi[$storedItem['id']] = $storedItem['spesifikasi'];
            }
            $this->calculateTotalHarga();
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


    public function formatToRupiah($itemId)
    {
        // Pastikan untuk menghapus 'Rp.' dan mengonversi ke integer
        $this->details[$itemId] = intval(str_replace(['.', ' '], '', $this->details_raw[$itemId]));
        $this->details_raw[$itemId] = $this->details[$itemId];
        $this->calculateSubTotal($itemId); // Hitung subtotal setelah format
        $this->editingItemId = null; // Reset ID setelah selesai
    }

    
    public function updateQuantity($itemId)
    {
        $requestedQty = $this->jml_bahan[$itemId] ?? 0;
        $item = Bahan::find($itemId);

        // if ($item) {
        //     if ($item->jenisBahan->nama === 'Produksi') {
        //         $bahanSetengahjadiDetails = $item->bahanSetengahjadiDetails()
        //             ->where('sisa', '>', 0)
        //             ->with(['bahanSetengahjadi' => function ($query) {
        //                 $query->orderBy('tgl_masuk', 'asc');
        //             }])->get();

        //         $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
        //         if ($requestedQty > $totalAvailable) {
        //             $this->qty[$itemId] = $totalAvailable;
        //         } elseif ($requestedQty < 0) {
        //             $this->qty[$itemId] = null;
        //         } else {
        //             $this->qty[$itemId] = $requestedQty;
        //         }
        //         // $this->updateUnitPriceAndSubtotalBahanSetengahJadi($itemId, $this->qty[$itemId], $bahanSetengahjadiDetails);
        //     } elseif ($item->jenisBahan->nama !== 'Produksi') {
        //         $purchaseDetails = $item->purchaseDetails()
        //             ->where('sisa', '>', 0)
        //             ->with(['purchase' => function ($query) {
        //                 $query->orderBy('tgl_masuk', 'asc');
        //             }])->get();

        //         $totalAvailable = $purchaseDetails->sum('sisa');
        //         if ($requestedQty > $totalAvailable) {
        //             $this->qty[$itemId] = $totalAvailable;
        //         } elseif ($requestedQty < 0) {
        //             $this->qty[$itemId] = null;
        //         } else {
        //             $this->qty[$itemId] = $requestedQty;
        //         }
        //         // $this->updateUnitPriceAndSubtotal($itemId, $this->qty[$itemId], $purchaseDetails);
        //     }
        // }
    }

    // protected function updateUnitPriceAndSubtotalBahanSetengahJadi($itemId, $qty, $bahanSetengahjadiDetails)
    // {
    //     $remainingQty = $qty;
    //     $totalPrice = 0;
    //     $this->details_raw[$itemId] = [];
    //     $this->details[$itemId] = [];

    //     foreach ($bahanSetengahjadiDetails as $bahanSetengahjadiDetail) {
    //         if ($remainingQty <= 0) break;

    //         $availableQty = $bahanSetengahjadiDetail->sisa;

    //         if ($availableQty > 0) {
    //             $toTake = min($availableQty, $remainingQty);
    //             $totalPrice += $toTake * $bahanSetengahjadiDetail->unit_price;

    //             $this->details[$itemId][] = [
    //                 'kode_transaksi' => $bahanSetengahjadiDetail->bahanSetengahjadi->kode_transaksi,
    //                 'qty' => $toTake,
    //                 'unit_price' => $bahanSetengahjadiDetail->unit_price
    //             ];
    //             $remainingQty -= $toTake;
    //         }
    //     }

    //     $this->subtotals[$itemId] = $totalPrice;
    //     $this->calculateTotalHarga();
    // }

    // protected function updateUnitPriceAndSubtotal($itemId, $qty, $purchaseDetails)
    // {
    //     $remainingQty = $qty;
    //     $totalPrice = 0;
    //     $this->details_raw[$itemId] = [];
    //     $this->details[$itemId] = [];

    //     foreach ($purchaseDetails as $purchaseDetail) {
    //         if ($remainingQty <= 0) break;

    //         $availableQty = $purchaseDetail->sisa;

    //         if ($availableQty > 0) {
    //             $toTake = min($availableQty, $remainingQty);
    //             $totalPrice += $toTake * $purchaseDetail->unit_price;

    //             $this->details[$itemId][] = [
    //                 'kode_transaksi' => $purchaseDetail->purchase->kode_transaksi,
    //                 'qty' => $toTake,
    //                 'unit_price' => $purchaseDetail->unit_price
    //             ];
    //             $remainingQty -= $toTake;
    //         }
    //     }

    //     $this->subtotals[$itemId] = $totalPrice;
    //     $this->calculateTotalHarga();
    // }


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
        $this->cart = collect($this->cart)->filter(function ($item) use ($itemId) {
            return $item->id !== $itemId;
        })->values()->all();
        unset($this->subtotals[$itemId]);
        $this->calculateTotalHarga();
        $this->saveCartToSession();
    }


    public function getCartItemsForStorage()
    {
        $items = [];
        foreach ($this->cart as $item) {
            $itemId = $item->id;
            $items[] = [
                'id' => $itemId,
                'qty' => isset($this->qty[$itemId]) ? $this->qty[$itemId] : 0,
                'jml_bahan' => isset($this->jml_bahan[$itemId]) ? $this->jml_bahan[$itemId] : 0,
                'details' => isset($this->details[$itemId]) ? $this->details[$itemId] : [],
                'sub_total' => isset($this->subtotals[$itemId]) ? $this->subtotals[$itemId] : 0,
                'spesifikasi' => isset($this->spesifikasi[$itemId]) ? $this->spesifikasi[$itemId] : 0,
            ];
        }
        return $items;
    }
    public function render()
    {
        return view('livewire.bahan-pengajuan-cart', [
            'cartItems' => $this->cart,
        ]);
    }
}
