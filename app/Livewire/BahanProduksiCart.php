<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
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

    protected $listeners = [
        'bahanSelected' => 'addToCart',
        'bahanSetengahJadiSelected' => 'addToCart'
    ];

    public function mount()
    {

    }

    public function addToCart($bahan)
    {
        if (is_array($bahan)) {
            $bahan = (object) $bahan;
        }

        // Tentukan apakah ini bahan atau bahan setengah jadi
        $bahanId = isset($bahan->id) ? $bahan->id : $bahan->bahan_setengahjadi_id;

        // Cek apakah bahan sudah ada di cart
        $existingItemKey = array_search($bahanId, array_column($this->cart, 'id'));

        if ($existingItemKey !== false) {
            // Tambahkan quantity jika sudah ada di cart
            $this->qty[$bahanId]++;
        } else {
            // Tambahkan bahan baru ke cart
            $this->cart[] = $bahan;
            $this->qty[$bahanId] = 1; // Inisialisasi qty
        }

        // Hitung subtotal untuk bahan ini
        $this->calculateSubTotal($bahanId);
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
        $item = Bahan::find($itemId);
        if ($item) {
            // Ambil total stok dari purchaseDetails berdasarkan sisa
            $totalStok = $item->purchaseDetails()->where('sisa', '>', 0)->sum('sisa');

            // Cek apakah ada stok yang tersedia dan apakah kuantitas yang diminta lebih kecil dari total stok
            if ($totalStok > 0 && (!isset($this->qty[$itemId]) || $this->qty[$itemId] < $totalStok)) {
                // Tambah kuantitas jika belum melebihi stok yang tersedia
                $this->qty[$itemId] = isset($this->qty[$itemId]) ? $this->qty[$itemId] + 1 : 1;
                $this->updateQuantity($itemId); // Panggil updateQuantity untuk menghitung ulang subtotal dan total harga
            }
        }
    }

    public function decreaseQuantity($itemId)
    {
        // Cek apakah kuantitas untuk item tersebut sudah diatur dan lebih besar dari 1
        if (isset($this->qty[$itemId]) && $this->qty[$itemId] > 1) {
            $this->qty[$itemId]--; // Kurangi kuantitas sebesar 1
            $this->updateQuantity($itemId); // Panggil updateQuantity untuk memperbarui subtotal dan total harga
        } elseif (isset($this->qty[$itemId]) && $this->qty[$itemId] == 1) {
            // Jika kuantitas adalah 1, setel ke nol
            $this->qty[$itemId] = 0;
            $this->updateQuantity($itemId); // Tetap panggil updateQuantity untuk mengupdate subtotal
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
        // First, check if the item is from Bahan (raw material)
        $item = Bahan::find($itemId);
        if ($item) {
            $requestedQty = $this->qty[$itemId];
            // dd($requestedQty);

            // Fetch all purchase details that have remaining stock for the raw material
            $purchaseDetails = $item->purchaseDetails()
                ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
                ->where('sisa', '>', 0)
                ->orderBy('purchases.tgl_masuk', 'asc')
                ->select('purchase_details.*', 'purchases.kode_transaksi') // Include kode_transaksi_masuk
                ->get();

            $totalAvailable = $purchaseDetails->sum('sisa');

            // If requested quantity exceeds available stock
            if ($requestedQty > $totalAvailable) {
                $this->qty[$itemId] = $totalAvailable; // Set quantity to total available stock
            } elseif ($requestedQty < 0) {
                $this->qty[$itemId] = null; // Set quantity to 0 if it's negative
            } else {
                $this->qty[$itemId] = $requestedQty; // Valid requested quantity
            }

            // Update unit price and calculate subtotal based on quantity for raw material
            $this->updateUnitPriceAndSubtotal($itemId, $this->qty[$itemId], $purchaseDetails);
        }

        $setengahJadiItem = BahanSetengahjadiDetails::find($itemId);
        if ($setengahJadiItem) {
            $requestedQty = $this->qty[$itemId];
            $availableQty = $setengahJadiItem->sisa;
            if ($requestedQty > $availableQty) {
                $this->qty[$itemId] = $availableQty;
            } elseif ($requestedQty < 0) {
                $this->qty[$itemId] = null;
            } else {
                $this->qty[$itemId] = $requestedQty;
            }

            $this->subtotals[$itemId] = $setengahJadiItem->unit_price * $this->qty[$itemId];
            $this->calculateTotalHarga();
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
                    'unit_price' => $purchaseDetail->unit_price
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
        return view('livewire.bahan-produksi-cart', [
            'cartItems' => $this->cart,
        ]);
    }
}
