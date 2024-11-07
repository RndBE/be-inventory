<?php

namespace App\Livewire;

use App\Models\Bahan;
use App\Models\Projek;
use Livewire\Component;
use App\Models\Produksi;
use App\Models\ProjekRnd;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;

class EditBahanProjekRndCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $jml_bahan = [];
    public $details = [];
    public $details_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;
    public $projekId;
    public $projekRndDetails = [];
    public $bahanRusak = [];
    public $bahanRetur = [];
    public $isFirstTimePengajuan = [];
    public $isBahanReturPending = [];
    public $pendingReturCount = [];
    public $isBahanRusakPending = [];
    public $pendingRusakCount = [];
    public $projekRndStatus;

    protected $listeners = [
        'bahanSelected' => 'addToCart',
        'bahanSetengahJadiSelected' => 'addToCart'
    ];

    public $bahanKeluars = [];

    public function mount($projekId)
    {
        $this->projekId = $projekId;
        $this->loadProduksi();
        $this->loadBahanKeluar();

        foreach ($this->projekRndDetails as $detail) {
            $bahanId = $detail['bahan']->id;
            $this->jml_bahan[$bahanId] = $detail['jml_bahan'] ?? 0; // Default to 0 if not set
            // $this->qty[$bahanId] = $detail['used_materials'] ?? 0;
        }
    }

    public function loadProduksi()
    {
        $projekRnd = ProjekRnd::with('projekRndDetails')->find($this->projekId);

        if ($projekRnd) {
            $this->projekRndStatus = $projekRnd->status;
            foreach ($projekRnd->projekRndDetails as $detail) {
                $this->projekRndDetails[] = [
                    'bahan' => Bahan::find($detail->bahan_id), // Ensure this returns an object
                    // 'qty' => $detail->qty,
                    'jml_bahan' => $detail->jml_bahan,
                    'used_materials' => $detail->used_materials ?? 0,
                    'sub_total' => $detail->sub_total,
                    'details' => json_decode($detail->details, true),
                ];
            }
        }
    }


    public function loadBahanKeluar()
    {
        $existingBahanKeluar = BahanKeluar::where('projek_rnd_id', $this->projekId)->exists();
        $this->isFirstTimePengajuan = !$existingBahanKeluar;

        $this->bahanKeluars = BahanKeluar::with('bahanKeluarDetails.dataBahan')
            ->where('status', 'Belum disetujui')
            ->where('projek_rnd_id', $this->projekId)
            ->get();

            $this->pendingReturCount = BahanRetur::where('projek_rnd_id', $this->projekId)
            ->where('status', 'Belum disetujui')
            ->count();

        $this->pendingRusakCount = BahanRusak::where('projek_rnd_id', $this->projekId)
            ->where('status', 'Belum disetujui')
            ->count();

        $this->isBahanReturPending = $this->pendingReturCount > 0;
        $this->isBahanRusakPending = $this->pendingRusakCount > 0;
    }

    public function addToCart($bahan)
    {
        if (is_array($bahan)) {
            $bahan = (object)$bahan;
        }

        if (!$bahan instanceof Bahan) {
            $bahanModel = Bahan::find($bahan->id);
            if ($bahanModel) {
                $bahan = $bahanModel;
            } else {
                return;
            }
        }

        // Check if the item already exists in projekRndDetails
        $bahanExistsInProjek = false;
        foreach ($this->projekRndDetails as $detail) {
            if ($detail['bahan']->id === $bahan->id) {
                $bahanExistsInProjek = true;
                break;
            }
        }
        if ($bahanExistsInProjek) {
            return;
        }

        // Check if the item is already in the cart
        $existingItemKey = array_search($bahan->id, array_column($this->cart, 'id'));
        $unitPrice = property_exists($bahan, 'unit_price') ? $bahan->unit_price : 0;

        if ($existingItemKey !== false) {
            // Update quantity and subtotal if already exists in the cart
            $this->qty[$bahan->id]++;
            $this->subtotals[$bahan->id] += $unitPrice;
        } else {
            // Add new item to the cart
            $this->cart[] = (object)[
                'id' => $bahan->id,
                'nama_bahan' => $bahan->nama,
                'stok' => $bahan->stok,
                'unit' => $bahan->unit,
                'newly_added' => true // Flag for newly added item
            ];
            $this->qty[$bahan->id] = null;
            $this->subtotals[$bahan->id] = $unitPrice;

            // Add the detail to projekRndDetails
            $this->projekRndDetails[] = [
                'bahan' => $bahan,
                'qty' => null,
                'jml_bahan' => 0,
                'sub_total' => 0,
                'details' => [],
                'newly_added' => true // Keep track of newly added item
            ];
        }

        // Calculate total price
        $this->totalharga = array_sum($this->subtotals);
        $this->saveCartToSession();
    }

    protected function saveCartToSession()
    {
        session()->put('cartItems', $this->getCartItemsForStorage());
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
                    'kode_transaksi' => $bahanSetengahjadiDetail->kode_transaksi,
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
        $this->projekRndDetails = collect($this->projekRndDetails)->filter(function ($detail) use ($itemId) {
            return $detail['bahan']->id !== $itemId;
        })->values()->all();
        $this->calculateTotalHarga();
    }



    public function decreaseQuantityPerPrice($itemId, $unitPrice)
    {
        foreach ($this->projekRndDetails as &$detail) {
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

    public function returQuantityPerPrice($itemId, $unitPrice)
    {
        foreach ($this->projekRndDetails as &$detail) {
            if ($detail['bahan']->id === $itemId) {
                foreach ($detail['details'] as &$d) {
                    if ($d['unit_price'] === $unitPrice && $d['qty'] > 0) {
                        $found = false;
                        foreach ($this->bahanRetur as &$retur) {
                            if ($retur['id'] === $itemId && $retur['unit_price'] === $unitPrice) {
                                $retur['qty'] += 1;
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $this->bahanRetur[] = [
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
                foreach ($this->projekRndDetails as &$detail) {
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

    public function returnReturToProduction($itemId, $unitPrice, $qty)
    {
        foreach ($this->bahanRetur as $key => $retur) {
            if ($retur['id'] === $itemId && $retur['unit_price'] === $unitPrice) {
                $this->bahanRetur[$key]['qty'] -= $qty;
                if ($this->bahanRetur[$key]['qty'] <= 0) {
                    unset($this->bahanRetur[$key]);
                }
                $foundInDetails = false;
                foreach ($this->projekRndDetails as &$detail) {
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
        $grandTotal = 0;
        $projekRndDetails = [];

        foreach ($this->projekRndDetails as $item) {
            $bahanId = $item['bahan']->id;
            $usedMaterials = $this->qty[$bahanId] ?? 0;

            if ($usedMaterials <= 0) {
                continue;
            }

            $totalPrice = 0;
            $details = [];

            if ($item['bahan']->jenisBahan->nama === 'Produksi') {
                $bahanSetengahjadiDetails = $item['bahan']->bahanSetengahjadiDetails()
                    ->where('sisa', '>', 0)
                    ->with(['bahanSetengahjadi' => function ($query) {
                        $query->orderBy('tgl_masuk', 'asc');
                    }])->get();
                foreach ($bahanSetengahjadiDetails as $detail) {
                    if ($usedMaterials <= 0) break;

                    $availableQty = $detail->sisa;
                    if ($availableQty > 0) {
                        $toTake = min($availableQty, $usedMaterials);
                        $details[] = [
                            'kode_transaksi' => $detail->bahanSetengahjadi->kode_transaksi,
                            'qty' => $toTake,
                            'unit_price' => $detail->unit_price,
                        ];

                        $totalPrice += $toTake * $detail->unit_price;
                        $usedMaterials -= $toTake;
                    }
                }
            } else {
                $purchaseDetails = $item['bahan']->purchaseDetails()
                    ->where('sisa', '>', 0)
                    ->with(['purchase' => function ($query) {
                        $query->orderBy('tgl_masuk', 'asc');
                    }])->get();

                foreach ($purchaseDetails as $detail) {
                    if ($usedMaterials <= 0) break;

                    $availableQty = $detail->sisa;
                    if ($availableQty > 0) {
                        $toTake = min($availableQty, $usedMaterials);

                        // Add this detail
                        $details[] = [
                            'kode_transaksi' => $detail->purchase->kode_transaksi,
                            'qty' => $toTake,
                            'unit_price' => $detail->unit_price,
                        ];
                        $totalPrice += $toTake * $detail->unit_price;
                        $usedMaterials -= $toTake;
                    }
                }
            }
            $projekRndDetails[] = [
                'id' => $bahanId,
                'qty' => $this->qty[$bahanId],
                'jml_bahan' => $this->jml_bahan[$bahanId] ?? 0,
                'details' => $details,
                'sub_total' => $totalPrice,
            ];
        }
        return $projekRndDetails;
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


    public function getCartItemsForBahanRetur()
    {
        $bahanRetur = [];
        foreach ($this->bahanRetur as $retur) {
            $bahanRetur[] = [
                'id' => $retur['id'],
                'qty' => $retur['qty'],
                'unit_price' => $retur['unit_price'],
                'sub_total' => $retur['qty'] * $retur['unit_price'],
            ];
        }
        return $bahanRetur;
    }


    public function render()
    {
        $projekRndTotal = array_sum(array_column($this->projekRndDetails, 'sub_total'));

        return view('livewire.edit-bahan-projek-rnd-cart', [
            'cartItems' => $this->cart,
            'projekRndDetails' => $this->projekRndDetails,
            'produksiTotal' => $projekRndTotal,
            'bahanRusak' => $this->bahanRusak,
            'bahanRetur' => $this->bahanRetur,
        ]);
    }
}
