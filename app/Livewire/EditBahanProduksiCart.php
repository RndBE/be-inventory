<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use App\Models\Produksi;

class EditBahanProduksiCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $details = [];
    public $details_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;
    public $produksiId;
    public $produksiDetails = [];
    public $bahanRusak = [];
    public $produksiStatus;

    protected $listeners = [
        'bahanSelected' => 'addToCart',
        'bahanSetengahJadiSelected' => 'addToCart'
    ];

    public function mount($produksiId)
    {
        $this->produksiId = $produksiId;
        $this->loadProduksi();
    }
    public function loadProduksi()
    {
        $produksi = Produksi::with('produksiDetails')->find($this->produksiId);

        if ($produksi) {
            $this->produksiStatus = $produksi->status;
            foreach ($produksi->produksiDetails as $detail) {
                $this->produksiDetails[] = [
                    'bahan' => Bahan::find($detail->bahan_id),
                    'qty' => $detail->qty,
                    'sub_total' => $detail->sub_total,
                    'details' => json_decode($detail->details, true),
                ];
            }
        }
    }

    public function addToCart($bahan)
    {
        if (is_array($bahan)) {
            $bahan = (object) $bahan;
        }

        // Tentukan apakah ini bahan atau bahan setengah jadi
        $bahanId = isset($bahan->id) ? $bahan->id : $bahan->bahan_id;
        // dd($bahanId);
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
        $item = Bahan::find($itemId);
        if ($item) {
            if ($item->jenisBahan->nama !== 'Produksi') {
                $totalStok = $item->purchaseDetails()->where('sisa', '>', 0)->sum('sisa');
                if ($totalStok > 0 && (!isset($this->qty[$itemId]) || $this->qty[$itemId] < $totalStok)) {
                    $this->qty[$itemId] = isset($this->qty[$itemId]) ? $this->qty[$itemId] + 1 : 1;
                    $this->updateQuantity($itemId);
                }
            } elseif ($item->jenisBahan->nama === 'Produksi') {
                $totalStok = $item->bahanSetengahjadiDetails()->where('sisa', '>', 0)->sum('sisa');
                if ($totalStok > 0 && (!isset($this->qty[$itemId]) || $this->qty[$itemId] < $totalStok)) {
                    $this->qty[$itemId] = isset($this->qty[$itemId]) ? $this->qty[$itemId] + 1 : 1;
                    $this->updateQuantity($itemId);
                }
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

    public function decreaseQuantityPerPrice($itemId, $unitPrice)
    {
        foreach ($this->produksiDetails as &$detail) {
            if ($detail['bahan']->id === $itemId) {
                // Check for each detail entry with the specific unit price
                foreach ($detail['details'] as &$d) {
                    if ($d['unit_price'] === $unitPrice && $d['qty'] > 0) {
                        // Check if the item is already in the damaged materials
                        $found = false;
                        foreach ($this->bahanRusak as &$rusak) {
                            if ($rusak['id'] === $itemId && $rusak['unit_price'] === $unitPrice) {
                                // If found, just increment the qty
                                $rusak['qty'] += 1;
                                $found = true;
                                break; // Exit the loop once we find it
                            }
                        }

                        // If not found, add a new entry to damaged materials
                        if (!$found) {
                            $this->bahanRusak[] = [
                                'id' => $itemId,
                                'qty' => 1, // Add one item
                                'unit_price' => $unitPrice,
                            ];
                        }

                        // Decrease the quantity and update subtotal
                        $d['qty'] -= 1; // Decrease by 1
                        $detail['sub_total'] -= $unitPrice; // Update subtotal by unit price

                        // If quantity goes to zero, set it to zero
                        if ($d['qty'] < 0) {
                            $d['qty'] = 0; // Ensure it doesn't go negative
                        }

                        break; // Exit once we find the right price
                    }
                }
                break; // Exit the loop once we found and updated the item
            }
        }

        // Recalculate total prices
        $this->calculateTotalHarga();
    }

    public function returnToProduction($itemId, $unitPrice, $qty)
    {
        // Find the index of the item in the damaged materials
        foreach ($this->bahanRusak as $key => $rusak) {
            if ($rusak['id'] === $itemId && $rusak['unit_price'] === $unitPrice) {
                // Return the quantity to production
                // Adjust the quantity in the damaged materials
                $this->bahanRusak[$key]['qty'] -= $qty;

                // If the quantity goes to zero, remove the item from the damaged materials
                if ($this->bahanRusak[$key]['qty'] <= 0) {
                    unset($this->bahanRusak[$key]); // Remove the item
                }

                // Update the quantity in produksiDetails
                $foundInDetails = false;
                foreach ($this->produksiDetails as &$detail) {
                    if ($detail['bahan']->id === $itemId) {
                        // Check for each detail entry with the specific unit price
                        foreach ($detail['details'] as &$d) {
                            if ($d['unit_price'] === $unitPrice) {
                                // Increase the quantity and update the subtotal
                                $d['qty'] += $qty; // Increase quantity
                                $detail['sub_total'] += $unitPrice * $qty; // Update subtotal
                                $foundInDetails = true;
                                break; // Exit once we find the right price
                            }
                        }
                    }
                    if ($foundInDetails) {
                        break; // Exit outer loop if found
                    }
                }
                break;
            }
        }
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

    public function getCartItemsForBahanRusak()
    {
        $bahanRusak = []; // Initialize an array to hold the cart items for bahan rusak

        foreach ($this->bahanRusak as $rusak) {
            $bahanRusak[] = [
                'id' => $rusak['id'],
                'qty' => $rusak['qty'],
                'unit_price' => $rusak['unit_price'],
                'sub_total' => $rusak['qty'] * $rusak['unit_price'],
            ];
        }

        return $bahanRusak;
    }


    public function render()
    {
        $produksiTotal = array_sum(array_column($this->produksiDetails, 'sub_total'));

        return view('livewire.edit-bahan-produksi-cart', [
            'cartItems' => $this->cart,
            'produksiDetails' => $this->produksiDetails,
            'produksiTotal' => $produksiTotal,
            'bahanRusak' => $this->bahanRusak,
        ]);
    }
}
