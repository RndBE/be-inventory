<?php

namespace App\Livewire;

use App\Models\Bahan;
use App\Models\PengambilanBahan;
use Livewire\Component;
use App\Models\Produksi;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;

class EditBahanPengambilanBahanCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $jml_bahan = [];
    public $details = [];
    public $details_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;
    public $pengambilanBahanId;
    public $pengambilanBahanDetails = [];
    public $bahanRusak = [];
    public $bahanRetur = [];
    public $isFirstTimePengambilanBahan = [];
    public $isBahanReturPending = [];
    public $pendingReturCount = [];
    public $isBahanRusakPending = [];
    public $pendingRusakCount = [];
    public $produksiStatus;

    protected $listeners = [
        'bahanSelected' => 'addToCart',
    ];

    public $bahanKeluars = [];

    public function mount($pengambilanBahanId)
    {
        $this->pengambilanBahanId = $pengambilanBahanId;
        $this->loadProduksi();
        $this->loadBahanKeluar();

        foreach ($this->pengambilanBahanDetails as $detail) {
            $bahanId = $detail['bahan']->id;
            $this->jml_bahan[$bahanId] = $detail['jml_bahan'] ?? 0;
            // $this->qty[$bahanId] = $detail['used_materials'] ?? 0;
        }
    }

    public function loadProduksi()
    {
        $this->pengambilanBahanDetails = [];

        $produksi = PengambilanBahan::with('pengambilanBahanDetails')->find($this->pengambilanBahanId);

        if ($produksi) {
            $this->produksiStatus = $produksi->status;
            foreach ($produksi->pengambilanBahanDetails as $detail) {
                $this->pengambilanBahanDetails[] = [
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
        $existingBahanKeluar = BahanKeluar::where('pengambilan_bahan_id', $this->pengambilanBahanId)->exists();
        $this->isFirstTimePengambilanBahan = !$existingBahanKeluar;

        $this->bahanKeluars = BahanKeluar::with('bahanKeluarDetails.dataBahan')
            ->where('status', 'Belum disetujui')
            ->where('pengambilan_bahan_id', $this->pengambilanBahanId)
            ->get();

        $this->pendingReturCount = BahanRetur::where('pengambilan_bahan_id', $this->pengambilanBahanId)
            ->where('status', 'Belum disetujui')
            ->count();

        $this->pendingRusakCount = BahanRusak::where('pengambilan_bahan_id', $this->pengambilanBahanId)
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

    // Ambil ID bahan dengan fallback ke bahan_id jika id tidak tersedia
    $bahanId = $bahan->id ?? $bahan->bahan_id ?? null;

    if (!$bahanId) {
        session()->flash('error', 'ID bahan tidak ditemukan.');
        return;
    }

    // Ambil model bahan dari database
    if (!$bahan instanceof Bahan) {
        $bahanModel = Bahan::find($bahanId);
        if (!$bahanModel) {
            session()->flash('error', 'Bahan tidak ditemukan.');
            return;
        }
        $bahan = $bahanModel;
    }

    $totalAvailable = $bahan->purchaseDetails->sum('sisa');
    if ($totalAvailable <= 0) {
        session()->flash('error', 'Sisa bahan tidak tersedia.');
        return;
    }

    // Cegah duplikasi
    foreach ($this->pengambilanBahanDetails as $detail) {
        if ($detail['bahan']->id === $bahan->id) {
            session()->flash('warning', 'Bahan sudah ditambahkan sebelumnya.');
            return;
        }
    }

    // Tambahkan ke cart
    if (!in_array($bahan->id, array_column($this->cart, 'id'))) {
        $this->cart[] = (object)[
            'id' => $bahan->id,
            'nama_bahan' => $bahan->nama,
            'stok' => $bahan->stok,
            'unit' => $bahan->unit,
            'newly_added' => true,
        ];

        $this->qty[$bahan->id] = null;
        $this->subtotals[$bahan->id] = 0;

        $this->pengambilanBahanDetails[] = [
            'bahan' => $bahan,
            'qty' => null,
            'jml_bahan' => 0,
            'sub_total' => 0,
            'details' => [],
            'newly_added' => true,
        ];

        $this->calculateTotalHarga();
        $this->saveCartToSession();
    }
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
            }
        }
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
        $this->pengambilanBahanDetails = collect($this->pengambilanBahanDetails)->filter(function ($detail) use ($itemId) {
            return $detail['bahan']->id !== $itemId;
        })->values()->all();
        $this->calculateTotalHarga();
    }



    // public function decreaseQuantityPerPrice($itemId, $unitPrice)
    // {
    //     foreach ($this->pengambilanBahanDetails as &$detail) {
    //         if ($detail['bahan']->id === $itemId) {
    //             foreach ($detail['details'] as &$d) {
    //                 if ($d['unit_price'] === $unitPrice && $d['qty'] > 0) {
    //                     $found = false;
    //                     foreach ($this->bahanRusak as &$rusak) {
    //                         if ($rusak['id'] === $itemId && $rusak['unit_price'] === $unitPrice) {
    //                             $rusak['qty'] += 1;
    //                             $found = true;
    //                             break;
    //                         }
    //                     }
    //                     if (!$found) {
    //                         $this->bahanRusak[] = [
    //                             'id' => $itemId,
    //                             'qty' => 1,
    //                             'unit_price' => $unitPrice,
    //                         ];
    //                     }
    //                     $d['qty'] -= 1;
    //                     $detail['sub_total'] -= $unitPrice;
    //                     if ($d['qty'] < 0) {
    //                         $d['qty'] = 0;
    //                     }

    //                     break;
    //                 }
    //             }
    //             break;
    //         }
    //     }
    //     $this->calculateTotalHarga();
    // }

    // public function decreaseQuantityPerPrice($itemId, $unitPrice)
    // {
    //     // Cek apakah sudah ada bahan ini dalam bahanRusak
    //     $alreadyExists = false;
    //     foreach ($this->bahanRusak as $retur) {
    //         if ($retur['id'] === $itemId) {
    //             $alreadyExists = true;
    //             break;
    //         }
    //     }

    //     if (!$alreadyExists) {
    //         $this->bahanRusak[] = [
    //             'id' => $itemId,
    //             'unit_price' => $unitPrice,
    //         ];
    //     }

    //     // Tidak perlu manipulasi qty, sub_total, atau pemanggilan fungsi lain
    // }
    public function decreaseQuantityPerPrice($itemId, $unitPrice)
    {
        // Cek apakah bahan sudah ada di bahanRetur
        foreach ($this->bahanRetur as $retur) {
            if ($retur['id'] === $itemId) {
                // Jika sudah ada di bahan retur, tidak boleh ditambahkan ke bahan rusak
                return;
            }
        }

        // Cek apakah sudah ada bahan ini dalam bahanRusak
        $alreadyExists = false;
        foreach ($this->bahanRusak as $rusak) {
            if ($rusak['id'] === $itemId) {
                $alreadyExists = true;
                break;
            }
        }

        if (!$alreadyExists) {
            $this->bahanRusak[] = [
                'id' => $itemId,
                'unit_price' => $unitPrice,
            ];
        }
    }


    // public function returQuantityPerPrice($itemId, $unitPrice)
    // {
    //     foreach ($this->pengambilanBahanDetails as &$detail) {
    //         if ($detail['bahan']->id === $itemId) {
    //             foreach ($detail['details'] as &$d) {
    //                 if ($d['unit_price'] === $unitPrice && $d['qty'] > 0) {
    //                     $found = false;
    //                     foreach ($this->bahanRetur as &$retur) {
    //                         if ($retur['id'] === $itemId && $retur['unit_price'] === $unitPrice) {
    //                             $retur['qty'] += 1;
    //                             $found = true;
    //                             break;
    //                         }
    //                     }
    //                     if (!$found) {
    //                         $this->bahanRetur[] = [
    //                             'id' => $itemId,
    //                             'qty' => 1,
    //                             'unit_price' => $unitPrice,
    //                         ];
    //                     }
    //                     $d['qty'] -= 1;
    //                     $detail['sub_total'] -= $unitPrice;
    //                     if ($d['qty'] < 0) {
    //                         $d['qty'] = 0;
    //                     }

    //                     break;
    //                 }
    //             }
    //             break;
    //         }
    //     }
    //     $this->calculateTotalHarga();
    // }
    // public function returQuantityPerPrice($itemId, $unitPrice)
    // {
    //     // Cek apakah sudah ada bahan ini dalam bahanRetur
    //     $alreadyExists = false;
    //     foreach ($this->bahanRetur as $retur) {
    //         if ($retur['id'] === $itemId) {
    //             $alreadyExists = true;
    //             break;
    //         }
    //     }

    //     if (!$alreadyExists) {
    //         $this->bahanRetur[] = [
    //             'id' => $itemId,
    //             'unit_price' => $unitPrice,
    //         ];
    //     }

    //     // Tidak perlu manipulasi qty, sub_total, atau pemanggilan fungsi lain
    // }

    public function returQuantityPerPrice($itemId, $unitPrice)
    {
        // Cek apakah bahan sudah ada di bahanRusak
        foreach ($this->bahanRusak as $rusak) {
            if ($rusak['id'] === $itemId) {
                // Jika sudah ada di bahan rusak, tidak boleh ditambahkan ke bahan retur
                return;
            }
        }

        // Cek apakah sudah ada bahan ini dalam bahanRetur
        $alreadyExists = false;
        foreach ($this->bahanRetur as $retur) {
            if ($retur['id'] === $itemId) {
                $alreadyExists = true;
                break;
            }
        }

        if (!$alreadyExists) {
            $this->bahanRetur[] = [
                'id' => $itemId,
                'unit_price' => $unitPrice,
            ];
        }
    }




    // public function returnToProduction($itemId, $unitPrice, $qty)
    // {
    //     foreach ($this->bahanRusak as $key => $rusak) {
    //         if ($rusak['id'] === $itemId && $rusak['unit_price'] === $unitPrice) {
    //             $this->bahanRusak[$key]['qty'] -= $qty;
    //             if ($this->bahanRusak[$key]['qty'] <= 0) {
    //                 unset($this->bahanRusak[$key]);
    //             }
    //             $foundInDetails = false;
    //             foreach ($this->pengambilanBahanDetails as &$detail) {
    //                 if ($detail['bahan']->id === $itemId) {
    //                     foreach ($detail['details'] as &$d) {
    //                         if ($d['unit_price'] === $unitPrice) {
    //                             $d['qty'] += $qty;
    //                             $detail['sub_total'] += $unitPrice * $qty;
    //                             $foundInDetails = true;
    //                             break;
    //                         }
    //                     }
    //                 }
    //                 if ($foundInDetails) {
    //                     break;
    //                 }
    //             }
    //             break;
    //         }
    //     }
    //     $this->calculateTotalHarga();
    // }
    public function returnToProduction($itemId, $unitPrice)
    {
        foreach ($this->bahanRusak as $key => $rusak) {
            if ($rusak['id'] === $itemId && $rusak['unit_price'] === $unitPrice) {
                unset($this->bahanRusak[$key]);
                break;
            }
        }

        $this->calculateTotalHarga();
    }

    // public function returnReturToProduction($itemId, $unitPrice, $qty)
    // {
    //     foreach ($this->bahanRetur as $key => $retur) {
    //         if ($retur['id'] === $itemId && $retur['unit_price'] === $unitPrice) {
    //             $this->bahanRetur[$key]['qty'] -= $qty;
    //             if ($this->bahanRetur[$key]['qty'] <= 0) {
    //                 unset($this->bahanRetur[$key]);
    //             }
    //             $foundInDetails = false;
    //             foreach ($this->pengambilanBahanDetails as &$detail) {
    //                 if ($detail['bahan']->id === $itemId) {
    //                     foreach ($detail['details'] as &$d) {
    //                         if ($d['unit_price'] === $unitPrice) {
    //                             $d['qty'] += $qty;
    //                             $detail['sub_total'] += $unitPrice * $qty;
    //                             $foundInDetails = true;
    //                             break;
    //                         }
    //                     }
    //                 }
    //                 if ($foundInDetails) {
    //                     break;
    //                 }
    //             }
    //             break;
    //         }
    //     }
    //     $this->calculateTotalHarga();
    // }

    public function returnReturToProduction($itemId, $unitPrice)
    {
        foreach ($this->bahanRetur as $key => $retur) {
            if ($retur['id'] === $itemId && $retur['unit_price'] === $unitPrice) {
                unset($this->bahanRetur[$key]);
                break;
            }
        }

        $this->calculateTotalHarga();
    }


    public function getCartItemsForStorage()
    {
        $grandTotal = 0;
        $pengambilanBahanDetails = [];

        foreach ($this->pengambilanBahanDetails as $item) {
            $bahanId = $item['bahan']->id;
            $usedMaterials = $this->qty[$bahanId] ?? 0;

            if ($usedMaterials <= 0) {
                continue;
            }

            $totalPrice = 0;
            $details = [];

            $pengambilanBahanDetails[] = [
                'id' => $bahanId,
                'qty' => $this->qty[$bahanId],
                'jml_bahan' => $this->jml_bahan[$bahanId] ?? 0,
                'details' => $details,
                'sub_total' => $totalPrice,
            ];
        }
        return $pengambilanBahanDetails;
    }

    // public function getCartItemsForBahanRusak()
    // {
    //     $bahanRusak = [];
    //     foreach ($this->bahanRusak as $rusak) {
    //         $bahanRusak[] = [
    //             'id' => $rusak['id'],
    //             'qty' => $rusak['qty'],
    //             'unit_price' => $rusak['unit_price'],
    //             'sub_total' => $rusak['qty'] * $rusak['unit_price'],
    //         ];
    //     }
    //     return $bahanRusak;
    // }
    public function getCartItemsForBahanRusak()
    {
        $bahanRusak = [];

        foreach ($this->bahanRusak as $rusak) {
            $qty = isset($rusak['qty'])
                ? floatval(str_replace(',', '.', $rusak['qty']))
                : 1;

            $unitPrice = isset($rusak['unit_price'])
                ? floatval($rusak['unit_price'])
                : 0;

            // ROUND sub_total ke 2 desimal (atau 0 jika integer)
            $subTotal = round($qty * $unitPrice, 2);

            $bahanRusak[] = [
                'id' => $rusak['id'],
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'sub_total' => $subTotal,
            ];
        }

        return $bahanRusak;
    }


    // public function getCartItemsForBahanRetur()
    // {
    //     $bahanRetur = [];
    //     foreach ($this->bahanRetur as $retur) {
    //         $bahanRetur[] = [
    //             'id' => $retur['id'],
    //             'qty' => $retur['qty'],
    //             'unit_price' => $retur['unit_price'],
    //             'sub_total' => $retur['qty'] * $retur['unit_price'],
    //         ];
    //     }
    //     return $bahanRetur;
    // }
public function getCartItemsForBahanRetur()
{
    $bahanRetur = [];

    foreach ($this->bahanRetur as $retur) {
        $qty = isset($retur['qty'])
            ? floatval(str_replace(',', '.', $retur['qty']))
            : 1;

        $unitPrice = isset($retur['unit_price'])
            ? floatval($retur['unit_price'])
            : 0;

        // ROUND sub_total ke 2 desimal (atau 0 jika integer)
        $subTotal = round($qty * $unitPrice, 2);

        $bahanRetur[] = [
            'id' => $retur['id'],
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'sub_total' => $subTotal,
        ];
    }

    return $bahanRetur;
}



public function updateReturQty($id, $unitPrice, $newQty)
{
    $parsedQty = floatval(str_replace(',', '.', $newQty));

    // Hitung total qty pengambilan untuk bahan ini
    $maxQty = 0;
    foreach ($this->pengambilanBahanDetails as $detail) {
        if ($detail['bahan']->id == $id) {
            foreach ($detail['details'] as $d) {
                $maxQty += $d['qty'];
            }
            break;
        }
    }

    // Validasi agar tidak melebihi
    if ($parsedQty > $maxQty) {
        $parsedQty = $maxQty;
        session()->flash('error', 'Qty retur tidak boleh melebihi jumlah pengambilan.');
    }

    // Update nilai qty bahan retur
    foreach ($this->bahanRetur as &$retur) {
        if ($retur['id'] == $id && $retur['unit_price'] == $unitPrice) {
            $retur['qty'] = max(0, $parsedQty);
            break;
        }
    }

    $this->calculateTotalHarga();
}

public function updateRusakQty($id, $unitPrice, $newQty)
{
    $parsedQty = floatval(str_replace(',', '.', $newQty));

    // Hitung total qty pengambilan untuk bahan ini
    $maxQty = 0;
    foreach ($this->pengambilanBahanDetails as $detail) {
        if ($detail['bahan']->id == $id) {
            foreach ($detail['details'] as $d) {
                $maxQty += $d['qty'];
            }
            break;
        }
    }

    // Validasi agar tidak melebihi
    if ($parsedQty > $maxQty) {
        $parsedQty = $maxQty;
        session()->flash('error', 'Qty rusak tidak boleh melebihi jumlah pengambilan.');
    }

    // Update nilai qty bahan rusak
    foreach ($this->bahanRusak as &$rusak) {
        if ($rusak['id'] == $id && $rusak['unit_price'] == $unitPrice) {
            $rusak['qty'] = max(0, $parsedQty);
            break;
        }
    }

    $this->calculateTotalHarga();
}






    public function render()
    {
        $produksiTotal = array_sum(array_column($this->pengambilanBahanDetails, 'sub_total'));

        return view('livewire.edit-bahan-pengambilan-bahan-cart', [
            'cartItems' => $this->cart,
            'pengambilanBahanDetails' => $this->pengambilanBahanDetails,
            'produksiTotal' => $produksiTotal,
            'bahanRusak' => $this->bahanRusak,
            'bahanRetur' => $this->bahanRetur,
        ]);
    }
}
