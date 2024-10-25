<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use App\Models\Produksi;
use App\Models\Projek;

class EditBahanProjekCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $details = [];
    public $details_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;
    public $projekId;
    public $projekDetails = [];
    public $bahanRusak = [];
    public $produksiStatus;
    public $grandTotal = 0;

    protected $listeners = [
        'bahanSelected' => 'addToCart',
        'bahanSetengahJadiSelected' => 'addToCart'
    ];

    public function mount($projekId)
    {
        $this->projekId = $projekId;
        $this->cart = [];
        $this->loadProduksi();

        foreach ($this->projekDetails as $detail) {
            $this->qty[$detail['bahan']->id] = $detail['used_materials']; // atau nilai default lainnya
        }
    }
    public function loadProduksi()
    {
        $produksi = Projek::with('projekDetails')->find($this->projekId);

        if ($produksi) {
            $this->produksiStatus = $produksi->status;
            foreach ($produksi->projekDetails as $detail) {
                $this->projekDetails[] = [
                    'bahan' => Bahan::find($detail->bahan_id),
                    'qty' => $detail->qty,
                    'jml_bahan' => $detail->jml_bahan,
                    'used_materials' => $detail->used_materials,
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
        //dd($bahan);
        // Check if the type property is set
        $isSetengahJadi = isset($bahan->type) && $bahan->type === 'setengahjadi';

        $existingItemKey = array_search($bahan->id, array_column($this->cart, 'id'));

        if ($existingItemKey !== false) {
            $this->updateQuantity($bahan->id);
        } else {
            // Create item object
            $item = (object)[
                'id' => $bahan->id,
                'nama_bahan' => $isSetengahJadi ? $bahan->nama : Bahan::find($bahan->id)->nama_bahan,
                'stok' => $bahan->stok,
                'unit' => $bahan->unit,
            ];

            // Add item to cart
            $this->cart[] = $item;
            // Initialize qty for this item
            $this->qty[$bahan->id] = 1; // or any default value
        }

        $this->saveCartToSession();
        $this->calculateSubTotal($bahan->id);
    }

    public function getCombinedCart()
    {
        // Prepare an array for the combined details
        $combinedDetails = [];

        // Add items from the cart
        foreach ($this->cart as $item) {
            // Check if the item has the necessary properties
            if (isset($item->id)) {
                $combinedDetails[$item->id] = [
                    'nama_bahan' => $item->nama_bahan,
                    'qty' => $this->qty[$item->id] ?? 0,
                    'stok' => $item->stok,
                    'unit' => $item->unit,
                ];
            }
        }

        // Add projek details
        foreach ($this->projekDetails as $detail) {
            $bahanId = $detail['bahan']->id ?? null; // Use null coalescing to avoid undefined index
            if ($bahanId !== null) {
                if (!isset($combinedDetails[$bahanId])) {
                    $combinedDetails[$bahanId] = [
                        'nama_bahan' => $detail['bahan']->nama_bahan,
                        'qty' => 0, // Start with 0 for qty as per your request
                        'stok' => $detail['bahan']->stok,
                        'unit' => $detail['bahan']->unit,
                    ];
                }
            }
        }

        return array_values($combinedDetails); // Convert associative array back to indexed array
    }



    protected function saveCartToSession()
    {
        session()->put('cartItems', $this->getCartItemsForStorage());
        dd($this->getCartItemsForStorage());
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
        $this->details[$itemId] = intval(str_replace(['.', ' '], '', $this->details_raw[$itemId]));
        $this->details_raw[$itemId] = $this->details[$itemId];
        $this->calculateSubTotal($itemId);
        $this->editingItemId = null;
    }

    public function updateQuantity($itemId)
    {
        $requestedQty = $this->qty[$itemId] ?? 0;
        $item = Bahan::find($itemId);

        if ($item) {
            if ($item->jenisBahan->nama === 'Produksi') {
                // Ambil data stok bahan setengah jadi
                $bahanSetengahjadiDetails = $item->bahanSetengahjadiDetails()
                    ->where('sisa', '>', 0)
                    ->with(['bahanSetengahjadi' => function ($query) {
                        $query->orderBy('tgl_masuk', 'asc');
                    }])->get();

                // Jumlah stok tersedia dari bahan setengah jadi
                $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
                if ($requestedQty > $totalAvailable) {
                    $this->qty[$itemId] = $totalAvailable;
                } else {
                    $this->qty[$itemId] = $requestedQty;
                }

                // Perbarui harga unit dan subtotal untuk bahan setengah jadi
                $this->updateUnitPriceAndSubtotalBahanSetengahJadi($itemId, $this->qty[$itemId], $bahanSetengahjadiDetails);

            } else {
                // Ambil data stok dari purchase details
                $purchaseDetails = $item->purchaseDetails()
                    ->where('sisa', '>', 0)
                    ->with(['purchase' => function ($query) {
                        $query->orderBy('tgl_masuk', 'asc');
                    }])->get();

                // Jumlah stok tersedia dari purchase details
                $totalAvailable = $purchaseDetails->sum('sisa');
                if ($requestedQty > $totalAvailable) {
                    $this->qty[$itemId] = $totalAvailable;
                } else {
                    $this->qty[$itemId] = $requestedQty;
                }

                // Perbarui harga unit dan subtotal untuk purchase details
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
        $this->cart = collect($this->cart)->filter(function ($item) use ($itemId) {
            return $item->id !== $itemId;
        })->values()->all();
        unset($this->subtotals[$itemId]);
        $this->calculateTotalHarga();
    }

    public function decreaseQuantityPerPrice($itemId, $unitPrice)
    {
        foreach ($this->projekDetails as &$detail) {
            if ($detail['bahan']->id === $itemId) {
                foreach ($detail['details'] as &$d) {
                    if ($d['unit_price'] === $unitPrice && $d['qty'] > 0) {
                        $found = false;
                        foreach ($this->bahanRusak as &$rusak) {
                            if ($rusak['id'] === $itemId && $rusak['unit_price'] === $unitPrice) {
                                $rusak['qty'] += 1;
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $this->bahanRusak[] = [
                                'id' => $itemId,
                                'qty' => 1,
                                'unit_price' => $unitPrice,
                            ];
                        }
                        $d['qty'] -= 1;
                        $detail['sub_total'] -= $unitPrice;
                        if ($d['qty'] < 0) {
                            $d['qty'] = 0;
                        }

                        break;
                    }
                }
                break;
            }
        }
        $this->calculateTotalHarga();
    }

    public function returnToProduction($itemId, $unitPrice, $qty)
    {
        foreach ($this->bahanRusak as $key => $rusak) {
            if ($rusak['id'] === $itemId && $rusak['unit_price'] === $unitPrice) {
                $this->bahanRusak[$key]['qty'] -= $qty;
                if ($this->bahanRusak[$key]['qty'] <= 0) {
                    unset($this->bahanRusak[$key]);
                }
                $foundInDetails = false;
                foreach ($this->projekDetails as &$detail) {
                    if ($detail['bahan']->id === $itemId) {
                        foreach ($detail['details'] as &$d) {
                            if ($d['unit_price'] === $unitPrice) {
                                $d['qty'] += $qty;
                                $detail['sub_total'] += $unitPrice * $qty;
                                $foundInDetails = true;
                                break;
                            }
                        }
                    }
                    if ($foundInDetails) {
                        break;
                    }
                }
                break;
            }
        }
        $this->calculateTotalHarga();
    }

    public function getCartItemsForStorage()
    {
        $projekDetails = [];

        foreach ($this->projekDetails as $item) {
            $bahanId = $item['bahan']->id;
            $requestedQty = $this->qty[$bahanId] ?? 0;
            $usedMaterials = 0;
            $totalPrice = 0;
            $details = [];
            if ($item['bahan']->jenisBahan->nama === 'Produksi') {
                $bahanSetengahjadiDetails = $item['bahan']->bahanSetengahjadiDetails()
                    ->where('sisa', '>', 0)
                    ->with(['bahanSetengahjadi' => function ($query) {
                        $query->orderBy('tgl_masuk', 'asc');
                    }])->get();

                // Calculate used materials for 'Produksi'
                foreach ($bahanSetengahjadiDetails as $bahan) {
                    if ($usedMaterials < $requestedQty) {
                        $availableQty = min($bahan->sisa, $requestedQty - $usedMaterials);
                        $unitPrice = $bahan->unit_price ?? 0;

                        if ($availableQty > 0) {
                            $details[] = [
                                'kode_transaksi' => $bahan->bahanSetengahjadi->kode_transaksi,
                                'qty' => $availableQty,
                                'unit_price' => $unitPrice,
                            ];
                            $usedMaterials += $availableQty;
                        }
                    }
                }
            } else {
                $purchaseDetails = $item['bahan']->purchaseDetails()
                    ->where('sisa', '>', 0)
                    ->with(['purchase' => function ($query) {
                        $query->orderBy('tgl_masuk', 'asc');
                    }])->get();

                // Calculate used materials for purchase details
                foreach ($purchaseDetails as $purchase) {
                    if ($usedMaterials < $requestedQty) {
                        $availableQty = min($purchase->sisa, $requestedQty - $usedMaterials); // Determine how much can be used
                        $unitPrice = $purchase->unit_price ?? 0;

                        // If available quantity is greater than zero, add to details
                        if ($availableQty > 0) {
                            $details[] = [
                                'kode_transaksi' => $purchase->purchase->kode_transaksi,
                                'qty' => $availableQty, // Use available quantity
                                'unit_price' => $unitPrice,
                            ];
                            $usedMaterials += $availableQty; // Update used materials
                        }
                    }
                }
            }

            // Calculate total price based on usedMaterials and unitPrice
            foreach ($details as $detail) {
                $totalPrice += $detail['qty'] * $detail['unit_price']; // Calculate subtotal for each detail
            }

            // Add data to projekDetails
            $projekDetails[] = [
                'id' => $bahanId,
                'qty' => $usedMaterials, // Now correctly reflects the used materials
                'details' => $details,
                'sub_total' => $totalPrice,
            ];
        }

        return $projekDetails;
    }

    public function getCartItemsForBahanRusak()
    {
        $bahanRusak = [];
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
        $produksiTotal = array_sum(array_column($this->projekDetails, 'sub_total'));
        $grandTotal = $produksiTotal;

        return view('livewire.edit-bahan-projek-cart', [
            'cartItems' => $this->cart,
            'projekDetails' => $this->projekDetails,
            'produksiTotal' => $produksiTotal,
            'grandTotal' => $grandTotal,
            'bahanRusak' => $this->bahanRusak,
        ]);
    }
}
