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
            $this->cart[] = $bahan;
            $this->qty[$bahanId] = null;
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
        // Check if the item is from Bahan (raw material)
        $item = Bahan::find($itemId);
        if ($item) {
            // Get total available stock
            $totalStok = $item->purchaseDetails()->where('sisa', '>', 0)->sum('sisa');

            if ($totalStok > 0 && (!isset($this->qty[$itemId]) || $this->qty[$itemId] < $totalStok)) {
                $this->qty[$itemId] = isset($this->qty[$itemId]) ? $this->qty[$itemId] + 1 : 1;
                $this->updateQuantity($itemId); // Update subtotal and total price
            }
        }

        // Check if the item is from Bahan Setengahjadi (semi-finished material)
        $setengahJadiItem = BahanSetengahjadiDetails::find($itemId);
        if ($setengahJadiItem) {
            $availableQty = $setengahJadiItem->sisa;

            if ($availableQty > 0 && (!isset($this->qty[$itemId]) || $this->qty[$itemId] < $availableQty)) {
                $this->qty[$itemId] = isset($this->qty[$itemId]) ? $this->qty[$itemId] + 1 : 1;
                $this->updateQuantity($itemId); // Update subtotal and total price
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

    public function updateQuantity($itemId)
    {
        // Initialize requested quantity
        $requestedQty = $this->qty[$itemId] ?? 0;
        $setengahJadiItem = BahanSetengahjadiDetails::find($itemId);
        $item = Bahan::find($itemId);

        if ($setengahJadiItem) {
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

        }elseif ($item) {
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
