<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use App\Models\Produksi;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;

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
    public $bahanRetur = [];
    public $produksiStatus;
    public $isFirstTimePengajuan = [];
    public $isBahanReturPending = [];
    public $pendingReturCount = [];
    public $isBahanRusakPending = [];
    public $pendingRusakCount = [];
    public $bahanKeluars = [];

    protected $listeners = [
        'bahanSelected' => 'addToCart',
        'bahanSetengahJadiSelected' => 'addToCart'
    ];

    // Menyimpan bahan keluar yang akan ditampilkan

    public function mount($produksiId)
    {
        $this->produksiId = $produksiId;
        $this->loadProduksi();
        $this->loadBahanKeluar(); // Panggil metode untuk memuat bahan keluar

        foreach ($this->produksiDetails as $detail) {
            $this->qty[$detail['bahan']->id] = $detail['used_materials']; // atau nilai default lainnya
        }
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
                    'jml_bahan' => $detail->jml_bahan,
                    'used_materials' => $detail->used_materials,
                    'sub_total' => $detail->sub_total,
                    'details' => json_decode($detail->details, true),
                ];
            }
        }
    }

    public function loadBahanKeluar()
    {
        $existingBahanKeluar = BahanKeluar::where('produksi_id', $this->produksiId)->exists();
        $this->isFirstTimePengajuan = !$existingBahanKeluar;

        $this->bahanKeluars = BahanKeluar::with('bahanKeluarDetails.dataBahan')
            ->where('status', 'Belum disetujui')
            ->where('produksi_id', $this->produksiId)
            ->get();

        $this->pendingReturCount = BahanRetur::where('produksi_id', $this->produksiId)
            ->where('status', 'Belum disetujui')
            ->count();

        $this->pendingRusakCount = BahanRusak::where('produksi_id', $this->produksiId)
            ->where('status', 'Belum disetujui')
            ->count();

        $this->isBahanReturPending = $this->pendingReturCount > 0;
        $this->isBahanRusakPending = $this->pendingRusakCount > 0;
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
        $this->calculateTotalHarga();
    }

    public function decreaseQuantityPerPrice($itemId, $unitPrice)
    {
        foreach ($this->produksiDetails as &$detail) {
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
        foreach ($this->produksiDetails as &$detail) {
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
                foreach ($this->produksiDetails as &$detail) {
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
                foreach ($this->produksiDetails as &$detail) {
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
        $produksiDetails = [];

        foreach ($this->produksiDetails as $item) {
            $bahanId = $item['bahan']->id;

            $usedMaterials = 0;
            $stokSaatIni = 0;
            $totalPrice = 0;

            // Hitung jumlah maksimum yang diizinkan berdasarkan detail produksi
            $maxAllowedQty = $item['jml_bahan'] - $item['used_materials'];

            // Inisialisasi array untuk menyimpan detail transaksi
            $details = [];

            if ($item['bahan']->jenisBahan->nama === 'Produksi') {
                $bahanSetengahjadiDetails = $item['bahan']->bahanSetengahjadiDetails()
                    ->where('sisa', '>', 0)
                    ->with(['bahanSetengahjadi' => function ($query) {
                        $query->orderBy('tgl_masuk', 'asc');
                    }])->get();

                // Hitung used materials dan detailnya
                foreach ($bahanSetengahjadiDetails as $bahan) {
                    if ($usedMaterials < $maxAllowedQty) {
                        $qtyToUse = min($bahan->sisa, $maxAllowedQty - $usedMaterials);
                        $usedMaterials += $qtyToUse;

                        // Ambil harga satuan dan kode transaksi untuk detail
                        $unitPrice = $bahan->unit_price ?? 0;
                        $details[] = [
                            'kode_transaksi' => $bahan->bahanSetengahjadi->kode_transaksi,
                            'qty' => $qtyToUse, // Tambahkan qty yang digunakan
                            'unit_price' => $unitPrice,
                        ];
                    }
                }
            } else {
                // Cek purchase details
                $purchaseDetails = $item['bahan']->purchaseDetails()
                    ->where('sisa', '>', 0)
                    ->with(['purchase' => function ($query) {
                        $query->orderBy('tgl_masuk', 'asc');
                    }])->get();

                // Hitung used materials dan detailnya
                foreach ($purchaseDetails as $purchase) {
                    if ($usedMaterials < $maxAllowedQty) {
                        $qtyToUse = min($purchase->sisa, $maxAllowedQty - $usedMaterials);
                        $usedMaterials += $qtyToUse;

                        // Ambil harga satuan dan kode transaksi untuk detail
                        $unitPrice = $purchase->unit_price ?? 0;
                        $details[] = [
                            'kode_transaksi' => $purchase->purchase->kode_transaksi,
                            'qty' => $qtyToUse, // Tambahkan qty yang digunakan
                            'unit_price' => $unitPrice,
                        ];
                    }
                }
            }

            // Hitung total price berdasarkan usedMaterials dan unitPrice
            foreach ($details as $detail) {
                $totalPrice += $detail['qty'] * $detail['unit_price']; // Hitung subtotal dari setiap detail
            }

            // Tambahkan total price ke grand total
            $grandTotal += $totalPrice;

            // Tambahkan data ke array produksiDetails
            $produksiDetails[] = [
                'id' => $bahanId,
                'qty' => $usedMaterials,
                'jml_bahan' => $item['jml_bahan'],
                'details' => $details,
                'sub_total' => $totalPrice,
            ];
        }

        return $produksiDetails;
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
        $produksiTotal = array_sum(array_column($this->produksiDetails, 'sub_total'));

        return view('livewire.edit-bahan-produksi-cart', [
            'cartItems' => $this->cart,
            'produksiDetails' => $this->produksiDetails,
            'produksiTotal' => $produksiTotal,
            'bahanRusak' => $this->bahanRusak,
            'bahanRetur' => $this->bahanRetur,
        ]);
    }
}
