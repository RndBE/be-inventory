<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;

class BahanKeluarCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $details = [];
    public $details_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;

    protected $listeners = ['bahanSelected' => 'addToCart'];

    public function addToCart($bahan)
    {
        if (is_array($bahan)) {
            $bahan = (object) $bahan;
        }
        // Cek apakah bahan sudah ada di keranjang
        $existingItemKey = array_search($bahan->id, array_column($this->cart, 'id'));
        if ($existingItemKey !== false) {
            // Jika bahan sudah ada, tingkatkan kuantitas
            $this->qty[$bahan->id]++;
        } else {
            // Jika bahan belum ada, tambahkan ke keranjang
            $this->cart[] = $bahan;
            $this->qty[$bahan->id] = null;
        }
        // Hitung subtotal untuk item yang ditambahkan atau diperbarui
        $this->calculateSubTotal($bahan->id);
    }


    public function calculateSubTotal($itemId)
    {
        $unitPrice = isset($this->details[$itemId]) ? intval($this->details[$itemId]) : 0;
        $qty = isset($this->qty[$itemId]) ? intval($this->qty[$itemId]) : 0;

        $this->subtotals[$itemId] = $unitPrice * $qty;

        // Hitung total harga setelah memperbarui subtotal
        $this->calculateTotalHarga();
    }


    public function calculateTotalHarga()
    {
        $this->totalharga = array_sum($this->subtotals);
    }


    public function increaseQuantity($itemId)
    {
        $item = Bahan::find($itemId); // Ganti dengan model yang sesuai
        if ($item && $item->total_stok > 0) {
            // Cek apakah kuantitas saat ini kurang dari total_stok
            if (!isset($this->qty[$itemId]) || $this->qty[$itemId] < $item->total_stok) {
                $this->qty[$itemId] = isset($this->qty[$itemId]) ? $this->qty[$itemId] + 1 : 1;
                $this->calculateSubTotal($itemId); // Panggil untuk menghitung subtotal
            }
        }
    }


    public function decreaseQuantity($itemId)
    {
        if (isset($this->qty[$itemId]) && $this->qty[$itemId] > 1) {
            $this->qty[$itemId]--;
            $this->calculateSubTotal($itemId); // Panggil untuk menghitung subtotal
        }
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
        $item = Bahan::find($itemId);
        if ($item) {
            $requestedQty = $this->qty[$itemId];

            // Ambil semua purchase details yang memiliki sisa > 0 untuk item ini
            $purchaseDetails = $item->purchaseDetails()
            ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
            ->where('sisa', '>', 0)
            ->orderBy('purchases.tgl_masuk', 'asc')
            ->select('purchase_details.*', 'purchases.kode_transaksi') // Include kode_transaksi_masuk
            ->get();


            $totalAvailable = $purchaseDetails->sum('sisa');

            // Jika permintaan melebihi total sisa yang tersedia
            if ($requestedQty > $totalAvailable) {
                $this->qty[$itemId] = $totalAvailable; // Atur kuantitas ke total sisa
            } elseif ($requestedQty < 0) {
                $this->qty[$itemId] = null; // Atur kuantitas ke 0
            } else {
                // Kuantitas yang diminta valid, biarkan seperti itu
                $this->qty[$itemId] = $requestedQty;
            }

            // Perbarui unit price dan hitung subtotal berdasarkan kuantitas
            $this->updateUnitPriceAndSubtotal($itemId, $this->qty[$itemId], $purchaseDetails);
        }
    }

    protected function updateUnitPriceAndSubtotal($itemId, $qty, $purchaseDetails)
    {
        $remainingQty = $qty;
        $totalPrice = 0;
        $this->details_raw[$itemId] = []; // Reset for item
        $this->details[$itemId] = []; // Reset array details for this item

        foreach ($purchaseDetails as $purchaseDetail) {
            if ($remainingQty <= 0) break;

            $availableQty = $purchaseDetail->sisa;

            if ($availableQty > 0) {
                $toTake = min($availableQty, $remainingQty);
                $totalPrice += $toTake * $purchaseDetail->unit_price;

                // Store unit price as [kode_transaksi_masuk, qty, details]
                $this->details[$itemId][] = [
                    'kode_transaksi' => $purchaseDetail->kode_transaksi, // Assuming this is the column name
                    'qty' => $toTake,
                    'details' => $purchaseDetail->unit_price
                ];
                $remainingQty -= $toTake;
            }
        }

        $this->subtotals[$itemId] = $totalPrice;
        $this->calculateTotalHarga();
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
        return view('livewire.bahan-keluar-cart', [
            'cartItems' => $this->cart,
        ]);
    }
}
