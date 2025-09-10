<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use App\Models\PurchaseDetail;
use App\Models\ProdukJadiDetails;
use App\Models\BahanSetengahjadiDetails;

class KomponenProjekCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $jml_bahan = [];
    public $details = [];
    public $details_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;

    protected $listeners = ['bahanSelected' => 'addToCart', 'bahanSetengahJadiSelected' => 'addToCart', 'produkJadiSelected' => 'addToCart'];

    public function mount()
    {

    }

    // public function addToCart($bahan)
    // {
    //     if (is_array($bahan)) {
    //         $bahan = (object) $bahan;
    //     }
    //     // dd($bahan);
    //     // Pilih ID yang benar: Produk ID jika ada, jika tidak gunakan Bahan ID
    //     if (!empty($bahan->produk_id)) {
    //         $itemId = $bahan->produk_id;
    //         $item = BahanSetengahjadiDetails::find($itemId);
    //     }elseif (!empty($bahan->produk_jadis_id)) {
    //         $itemId = $bahan->produk_jadis_id;
    //         $item = ProdukJadiDetails::find($itemId);
    //     }else {
    //         $itemId = $bahan->bahan_id;
    //         $item = Bahan::find($itemId);
    //     }
    //     if (!$itemId) {
    //         session()->flash('error', 'ID bahan tidak ditemukan.');
    //         return;
    //     }

    //     // Cek apakah item sudah ada di keranjang
    //     $existingItemKey = array_search($itemId, array_column($this->cart, 'id'));
    //     if ($existingItemKey !== false) {
    //         $this->updateQuantity($itemId);
    //         return;
    //     }

    //     // Periksa sisa bahan berdasarkan jenisnya
    //     if (isset($bahan->produk_id)) {
    //         // Cek di bahan setengah jadi details
    //         $bahanSetengahjadiDetails = BahanSetengahjadiDetails::where('id', $bahan->produk_id)
    //             ->where('sisa', '>', 0)
    //             ->with(['bahanSetengahjadi' => function ($query) {
    //                 $query->orderBy('tgl_masuk', 'asc');
    //             }])->get();

    //         $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
    //         if ($totalAvailable <= 0) {
    //             session()->flash('error', 'Sisa bahan tidak ada di bahan setengah jadi.');
    //             return;
    //         }
    //     }elseif (isset($bahan->produk_jadis_id)) {
    //         // Cek di produk jadi details
    //         $produkJadiDetails = ProdukJadiDetails::where('id', $bahan->produk_jadis_id)
    //             ->where('sisa', '>', 0)
    //             ->with(['ProdukJadis' => function ($query) {
    //                 $query->orderBy('tgl_masuk', 'asc');
    //             }])->get();
    //         // dd($produkJadiDetails);
    //         $totalAvailable = $produkJadiDetails->sum('sisa');
    //         if ($totalAvailable <= 0) {
    //             session()->flash('error', 'Sisa produk tidak ada di produk setengah jadi.');
    //             return;
    //         }
    //     } else {
    //         // Cek di purchase details untuk bahan biasa
    //         $purchaseDetails = PurchaseDetail::where('bahan_id', $bahan->bahan_id)
    //             ->where('sisa', '>', 0)
    //             ->with(['purchase' => function ($query) {
    //                 $query->orderBy('tgl_masuk', 'asc');
    //             }])->get();

    //         $totalAvailable = $purchaseDetails->sum('sisa');
    //         if ($totalAvailable <= 0) {
    //             session()->flash('error', 'Sisa bahan tidak ada di purchase details.');
    //             return;
    //         }
    //     }

    //     // Tambahkan item ke keranjang
    //     // $item = (object)[
    //     //     'id' => $itemId,
    //     //     'bahan_id' => $bahan->bahan_id ?? null,
    //     //     'produk_id' => $bahan->produk_id ?? null,
    //     //     'produk_jadis_id' => $bahan->produk_jadis_id ?? null,
    //     //     'serial_number' => $bahan->serial_number ?? null,
    //     //     'nama_bahan' => $bahan->nama ?? 'Tanpa Nama',
    //     //     'stok' => $bahan->stok ?? 0,
    //     //     'type' => $bahan->type ?? '-',
    //     //     'unit' => $bahan->unit ?? 'Pcs',
    //     // ];

    //     // $this->cart[] = $item;
    //     // $this->qty[$itemId] = null;
    //     // $this->jml_bahan[$itemId] = null;

    //     $itemKey = ($bahan->type ?? 'bahan') . '-' . ($bahan->bahan_id ?? $bahan->produk_id ?? $bahan->produk_jadis_id ?? $bahan->id);
    //     if (!empty($bahan->serial_number)) {
    //         $itemKey .= '-' . $bahan->serial_number;
    //     }
    //     dd($itemKey);
    //     $item = (object)[
    //         'cart_key' => $itemKey, // unique untuk Livewire
    //         'id' => $itemId,
    //         'bahan_id' => $bahan->bahan_id ?? null,
    //         'produk_id' => $bahan->produk_id ?? null,
    //         'produk_jadis_id' => $bahan->produk_jadis_id ?? null,
    //         'serial_number' => $bahan->serial_number ?? null,
    //         'nama_bahan' => $bahan->nama ?? 'Tanpa Nama',
    //         'stok' => $bahan->stok ?? 0,
    //         'type' => $bahan->type ?? '-',
    //         'unit' => $bahan->unit ?? 'Pcs',
    //     ];

    //     $this->cart[$itemKey] = $item;
    //     $this->qty[$itemKey] = null;
    //     $this->jml_bahan[$itemKey] = null;

    //     $this->saveCartToSession();
    //     $this->calculateSubTotal($itemId);
    // }

    public function addToCart($bahan)
    {
        if (is_array($bahan)) {
            $bahan = (object) $bahan;
        }

        // Tentukan key unik (cart_key)
        $itemKey = ($bahan->type ?? 'bahan') . '-' . ($bahan->bahan_id ?? $bahan->produk_id ?? $bahan->produk_jadis_id ?? $bahan->id);
        if (!empty($bahan->serial_number)) {
            $itemKey .= '-' . $bahan->serial_number;
        }

        // Jika item sudah ada di cart, update qty
        if (isset($this->cart[$itemKey])) {
            $this->updateQuantity($itemKey);
            return;
        }

        // Validasi stok
        if (isset($bahan->produk_id)) {
            $totalAvailable = BahanSetengahjadiDetails::where('id', $bahan->produk_id)
                ->where('sisa', '>', 0)
                ->sum('sisa');
            if ($totalAvailable <= 0) {
                session()->flash('error', 'Sisa bahan tidak ada di bahan setengah jadi.');
                return;
            }
        } elseif (isset($bahan->produk_jadis_id)) {
            $totalAvailable = ProdukJadiDetails::where('id', $bahan->produk_jadis_id)
                ->where('sisa', '>', 0)
                ->sum('sisa');
            if ($totalAvailable <= 0) {
                session()->flash('error', 'Sisa produk tidak ada di produk jadi.');
                return;
            }
        } else {
            $totalAvailable = PurchaseDetail::where('bahan_id', $bahan->bahan_id)
                ->where('sisa', '>', 0)
                ->sum('sisa');
            if ($totalAvailable <= 0) {
                session()->flash('error', 'Sisa bahan tidak ada di purchase details.');
                return;
            }
        }

        // Simpan item ke cart
        $item = (object)[
            'cart_key'       => $itemKey,
            'id'             => $bahan->bahan_id ?? $bahan->produk_id ?? $bahan->produk_jadis_id ?? $bahan->id,
            'bahan_id'       => $bahan->bahan_id ?? null,
            'produk_id'      => $bahan->produk_id ?? null,
            'produk_jadis_id'=> $bahan->produk_jadis_id ?? null,
            'serial_number'  => $bahan->serial_number ?? null,
            'nama_bahan'     => $bahan->nama ?? 'Tanpa Nama',
            'stok'           => $bahan->stok ?? 0,
            'type'           => $bahan->type ?? '-',
            'unit'           => $bahan->unit ?? 'Pcs',
        ];

        $this->cart[$itemKey] = $item;
        $this->qty[$itemKey] = null;
        $this->jml_bahan[$itemKey] = null;

        $this->saveCartToSession();
        $this->calculateSubTotal($itemKey);
    }


    protected function saveCartToSession()
    {
        session()->put('cartItems', $this->getCartItemsForStorage());
    }

    // protected function loadCartFromSession()
    // {
    //     if (session()->has('cartItems')) {
    //         $storedItems = session()->get('cartItems');
    //         foreach ($storedItems as $storedItem) {
    //             $this->cart[] = (object) ['id' => $storedItem['id'], 'nama_bahan' => Bahan::find($storedItem['id'])->nama_bahan];
    //             $this->qty[$storedItem['id']] = $storedItem['qty'];
    //             $this->jml_bahan[$storedItem['id']] = $storedItem['jml_bahan'];
    //             $this->subtotals[$storedItem['id']] = $storedItem['sub_total'];
    //         }
    //         $this->calculateTotalHarga();
    //     }
    // }
    protected function loadCartFromSession()
    {
        if (session()->has('cartItems')) {
            $storedItems = session()->get('cartItems');
            foreach ($storedItems as $storedItem) {
                $cartKey = $storedItem['cart_key'];

                $this->cart[$cartKey] = (object)[
                    'cart_key'        => $cartKey,
                    'id'              => $storedItem['bahan_id'] ?? $storedItem['produk_id'] ?? $storedItem['produk_jadis_id'],
                    'bahan_id'        => $storedItem['bahan_id'] ?? null,
                    'produk_id'       => $storedItem['produk_id'] ?? null,
                    'produk_jadis_id' => $storedItem['produk_jadis_id'] ?? null,
                    'serial_number'   => $storedItem['serial_number'] ?? null,
                    'nama_bahan'      => Bahan::find($storedItem['bahan_id'])->nama_bahan ?? 'Tanpa Nama',
                    'type'            => $storedItem['produk_id'] ? 'setengahjadi' : ($storedItem['produk_jadis_id'] ? 'jadi' : 'bahan'),
                ];

                $this->qty[$cartKey]        = $storedItem['qty'];
                $this->jml_bahan[$cartKey]  = $storedItem['jml_bahan'];
                $this->subtotals[$cartKey]  = $storedItem['sub_total'];
                $this->details[$cartKey]    = $storedItem['details'];
            }
            $this->calculateTotalHarga();
        }
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

    public function formatToRupiah($itemId)
    {
        // Pastikan untuk menghapus 'Rp.' dan mengonversi ke integer
        $this->details[$itemId] = intval(str_replace(['.', ' '], '', $this->details_raw[$itemId]));
        $this->details_raw[$itemId] = $this->details[$itemId];
        $this->calculateSubTotal($itemId); // Hitung subtotal setelah format
        $this->editingItemId = null; // Reset ID setelah selesai
    }

    // public function updateQuantity($itemId)
    // {
    //     $requestedQty = $this->qty[$itemId] ?? 0;

    //     // Cari item di keranjang berdasarkan itemId
    //     $cartItem = collect($this->cart)->firstWhere('id', $itemId);

    //     // dd($cartItem);

    //     if ($cartItem) {
    //         // Cek apakah ini bahan setengah jadi berdasarkan serial number
    //         if ($cartItem->type === 'setengahjadi' && !empty($cartItem->serial_number)) {
    //             $bahanSetengahJadiDetail = BahanSetengahjadiDetails::where('bahan_id', $cartItem->bahan_id)
    //                 ->where('serial_number', $cartItem->serial_number)
    //                 ->first();

    //             if ($bahanSetengahJadiDetail) {
    //                 $totalAvailable = $bahanSetengahJadiDetail->sisa;

    //                 if ($requestedQty > $totalAvailable) {
    //                     $this->qty[$itemId] = $totalAvailable;
    //                     session()->flash('error', 'Jumlah melebihi stok yang tersedia untuk serial number ini (bahan setengah jadi).');
    //                 } elseif ($requestedQty < 0) {
    //                     $this->qty[$itemId] = null;
    //                 } else {
    //                     $this->qty[$itemId] = $requestedQty;
    //                 }
    //             }
    //         } elseif ($cartItem->type === 'jadi' && !empty($cartItem->serial_number)) {
    //             $produkJadiDetail = ProdukJadiDetails::where('id', $cartItem->produk_jadis_id)
    //                 ->where('serial_number', $cartItem->serial_number)
    //                 ->first();

    //             // dd($produkJadiDetail );

    //             if ($produkJadiDetail) {
    //                 $totalAvailable = $produkJadiDetail->sisa;

    //                 if ($requestedQty > $totalAvailable) {
    //                     $this->qty[$itemId] = $totalAvailable;
    //                     session()->flash('error', 'Jumlah melebihi stok yang tersedia untuk serial number ini (produk jadi).');
    //                 } elseif ($requestedQty < 0) {
    //                     $this->qty[$itemId] = null;
    //                 } else {
    //                     $this->qty[$itemId] = $requestedQty;
    //                 }
    //             }
    //         } else {
    //             // Untuk bahan non-setengah jadi, gunakan logika seperti sebelumnya
    //             $item = Bahan::find($cartItem->bahan_id);

    //             if ($item) {
    //                 if ($item->jenisBahan->nama === 'Produksi') {
    //                     $bahanSetengahjadiDetails = $item->bahanSetengahjadiDetails()
    //                         ->where('sisa', '>', 0)
    //                         ->with(['bahanSetengahjadi' => function ($query) {
    //                             $query->orderBy('tgl_masuk', 'asc');
    //                         }])->get();

    //                     $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
    //                     if ($requestedQty > $totalAvailable) {
    //                         $this->qty[$itemId] = $totalAvailable;
    //                     } elseif ($requestedQty < 0) {
    //                         $this->qty[$itemId] = null;
    //                     } else {
    //                         $this->qty[$itemId] = $requestedQty;
    //                     }
    //                 } elseif ($item->jenisBahan->nama !== 'Produksi') {
    //                     $purchaseDetails = $item->purchaseDetails()
    //                         ->where('sisa', '>', 0)
    //                         ->with(['purchase' => function ($query) {
    //                             $query->orderBy('tgl_masuk', 'asc');
    //                         }])->get();

    //                     $totalAvailable = $purchaseDetails->sum('sisa');
    //                     if ($requestedQty > $totalAvailable) {
    //                         $this->qty[$itemId] = $totalAvailable;
    //                     } elseif ($requestedQty < 0) {
    //                         $this->qty[$itemId] = null;
    //                     } else {
    //                         $this->qty[$itemId] = $requestedQty;
    //                     }
    //                 }
    //             }
    //         }
    //     }
    // }
    // public function updateQuantity($cartKey)
    // {
    //     $requestedQty = $this->qty[$cartKey] ?? 0;

    //     // Cari item di keranjang berdasarkan cart_key
    //     $cartItem = $this->cart[$cartKey] ?? null;

    //     if ($cartItem) {
    //         // ============================
    //         // Bahan Setengah Jadi
    //         // ============================
    //         if ($cartItem->type === 'setengahjadi' && !empty($cartItem->serial_number)) {
    //             $bahanSetengahJadiDetail = BahanSetengahjadiDetails::where('id', $cartItem->produk_id)
    //                 ->where('serial_number', $cartItem->serial_number)
    //                 ->first();

    //             if ($bahanSetengahJadiDetail) {
    //                 $totalAvailable = $bahanSetengahJadiDetail->sisa;

    //                 if ($requestedQty > $totalAvailable) {
    //                     $this->qty[$cartKey] = $totalAvailable;
    //                     session()->flash('error', 'Jumlah melebihi stok yang tersedia untuk serial number ini (bahan setengah jadi).');
    //                 } elseif ($requestedQty < 0) {
    //                     $this->qty[$cartKey] = null;
    //                 } else {
    //                     $this->qty[$cartKey] = $requestedQty;
    //                 }
    //             }
    //         }

    //         // ============================
    //         // Produk Jadi
    //         // ============================
    //         elseif ($cartItem->type === 'jadi' && !empty($cartItem->serial_number)) {
    //             $produkJadiDetail = ProdukJadiDetails::where('id', $cartItem->produk_jadis_id)
    //                 ->where('serial_number', $cartItem->serial_number)
    //                 ->first();

    //             if ($produkJadiDetail) {
    //                 $totalAvailable = $produkJadiDetail->sisa;

    //                 if ($requestedQty > $totalAvailable) {
    //                     $this->qty[$cartKey] = $totalAvailable;
    //                     session()->flash('error', 'Jumlah melebihi stok yang tersedia untuk serial number ini (produk jadi).');
    //                 } elseif ($requestedQty < 0) {
    //                     $this->qty[$cartKey] = null;
    //                 } else {
    //                     $this->qty[$cartKey] = $requestedQty;
    //                 }
    //             }
    //         }

    //         // ============================
    //         // Bahan Biasa
    //         // ============================
    //         else {
    //             $item = Bahan::find($cartItem->bahan_id);

    //             if ($item) {
    //                 if ($item->jenisBahan->nama === 'Produksi') {
    //                     $bahanSetengahjadiDetails = $item->bahanSetengahjadiDetails()
    //                         ->where('sisa', '>', 0)
    //                         ->get();

    //                     $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
    //                 } else {
    //                     $purchaseDetails = $item->purchaseDetails()
    //                         ->where('sisa', '>', 0)
    //                         ->get();

    //                     $totalAvailable = $purchaseDetails->sum('sisa');
    //                 }

    //                 if ($requestedQty > $totalAvailable) {
    //                     $this->qty[$cartKey] = $totalAvailable;
    //                 } elseif ($requestedQty < 0) {
    //                     $this->qty[$cartKey] = null;
    //                 } else {
    //                     $this->qty[$cartKey] = $requestedQty;
    //                 }
    //             }
    //         }
    //     }
    // }
    public function updateQuantity($cartKey)
{
    $requestedQty = $this->qty[$cartKey] ?? 0;

    // Cari item di keranjang berdasarkan cart_key
    $cartItem = $this->cart[$cartKey] ?? null;

    if ($cartItem) {
        if ($cartItem->type === 'setengahjadi' && !empty($cartItem->serial_number)) {
            $bahanSetengahJadiDetail = BahanSetengahjadiDetails::where('bahan_id', $cartItem->bahan_id)
                ->where('serial_number', $cartItem->serial_number)
                ->first();

            if ($bahanSetengahJadiDetail) {
                $totalAvailable = $bahanSetengahJadiDetail->sisa;
                $this->qty[$cartKey] = $this->validateQty($requestedQty, $totalAvailable, 'Jumlah melebihi stok yang tersedia untuk serial number ini (bahan setengah jadi).');
            }
        } elseif ($cartItem->type === 'jadi' && !empty($cartItem->serial_number)) {
            $produkJadiDetail = ProdukJadiDetails::where('id', $cartItem->produk_jadis_id)
                ->where('serial_number', $cartItem->serial_number)
                ->first();

            if ($produkJadiDetail) {
                $totalAvailable = $produkJadiDetail->sisa;
                $this->qty[$cartKey] = $this->validateQty($requestedQty, $totalAvailable, 'Jumlah melebihi stok yang tersedia untuk serial number ini (produk jadi).');
            }
        } else {
            $item = Bahan::find($cartItem->bahan_id);
            if ($item) {
                if ($item->jenisBahan->nama === 'Produksi') {
                    $totalAvailable = $item->bahanSetengahjadiDetails()->where('sisa', '>', 0)->sum('sisa');
                    $this->qty[$cartKey] = $this->validateQty($requestedQty, $totalAvailable);
                } else {
                    $totalAvailable = $item->purchaseDetails()->where('sisa', '>', 0)->sum('sisa');
                    $this->qty[$cartKey] = $this->validateQty($requestedQty, $totalAvailable);
                }
            }
        }
    }
}

private function validateQty($requestedQty, $totalAvailable, $errorMessage = null)
{
    if ($requestedQty > $totalAvailable) {
        if ($errorMessage) session()->flash('error', $errorMessage);
        return $totalAvailable;
    } elseif ($requestedQty < 0) {
        return null;
    } else {
        return $requestedQty;
    }
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

    // public function removeItem($itemId)
    // {
    //     // Hapus item dari keranjang
    //     $this->cart = collect($this->cart)->filter(function ($item) use ($itemId) {
    //         return $item->id !== $itemId;
    //     })->values()->all(); // Menggunakan collect untuk memfilter dan mengembalikan array
    //     // Hapus subtotal yang terkait dengan item yang dihapus
    //     unset($this->subtotals[$itemId]);
    //     // Hitung ulang total harga setelah penghapusan
    //     $this->calculateTotalHarga();
    //     $this->saveCartToSession();
    // }

    public function removeItem($itemKey)
    {
        unset($this->cart[$itemKey]);
        unset($this->qty[$itemKey]);
        unset($this->jml_bahan[$itemKey]);
        unset($this->subtotals[$itemKey]);

        $this->calculateTotalHarga();
        $this->saveCartToSession();
    }


    // public function getCartItemsForStorage()
    // {
    //     $items = [];

    //     foreach ($this->cart as $item) {
    //         $items[] = [
    //         'bahan_id' => $item->bahan_id ?? null,
    //         'produk_id' => $item->produk_id ?? null,
    //         'produk_jadis_id' => $item->produk_jadis_id ?? null,
    //         // Serial number bisa dipakai baik untuk setengah jadi maupun produk jadi
    //         'serial_number' => $item->serial_number ?? null,
    //         'qty' => $this->qty[$item->id] ?? 0,
    //         'jml_bahan' => $this->jml_bahan[$item->id] ?? 0,
    //         'details' => $this->details[$item->id] ?? [],
    //         'sub_total' => $this->subtotals[$item->id] ?? 0,
    //     ];
    //     }

    //     return $items;
    // }
    public function getCartItemsForStorage()
    {
        $items = [];

        foreach ($this->cart as $item) {
            $cartKey = $item->cart_key; // pakai cart_key unik
            $items[] = [
                'cart_key'        => $cartKey,
                'bahan_id'        => $item->bahan_id ?? null,
                'produk_id'       => $item->produk_id ?? null,
                'produk_jadis_id' => $item->produk_jadis_id ?? null,
                'serial_number'   => $item->serial_number ?? null,
                'qty'             => $this->qty[$cartKey] ?? 0,
                'jml_bahan'       => $this->jml_bahan[$cartKey] ?? 0,
                'details'         => $this->details[$cartKey] ?? [],
                'sub_total'       => $this->subtotals[$cartKey] ?? 0,
            ];
        }

        return $items;
    }


    public function render()
    {
        return view('livewire.komponen-projek-cart', [
            'cartItems' => $this->cart,
        ]);
    }
}
