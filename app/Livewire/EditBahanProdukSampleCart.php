<?php

namespace App\Livewire;

use App\Models\Bahan;
use App\Models\Projek;
use Livewire\Component;
use App\Models\Produksi;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use App\Models\ProdukSampleDetails;
use App\Models\PurchaseDetail;
use App\Models\BahanSetengahjadiDetails;
use App\Models\ProdukSample;

class EditBahanProdukSampleCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $jml_bahan = [];
    public $details = [];
    public $details_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;
    public $produkSampleId;
    public $produkSampleDetails = [];
    public $bahanRusak = [];
    public $bahanRetur = [];
    public $isFirstTimePengajuan = [];
    public $isBahanReturPending = [];
    public $pendingReturCount = [];
    public $isBahanRusakPending = [];
    public $pendingRusakCount = [];
    public $produkSampleStatus;

    protected $listeners = [
        'bahanSelected' => 'addToCart',
        'bahanSetengahJadiSelected' => 'addToCart'
    ];

    public $bahanKeluars = [];

    public function mount($produkSampleId)
    {
        $this->produkSampleId = $produkSampleId;
        $this->loadProduksi();
        $this->loadBahanKeluar();

        foreach ($this->produkSampleDetails as $detail) {
            // Simpan bahan_id jika tersedia
            if (!empty($detail['bahan_id'])) {
                $this->jml_bahan[$detail['bahan_id']] = $detail['jml_bahan'] ?? 0;
            }

            // Simpan produk_id jika tersedia
            if (!empty($detail['produk_id'])) {
                $this->jml_bahan[$detail['produk_id']] = $detail['jml_bahan'] ?? 0;
            }
        }
    }


    public function loadProduksi()
    {
        $produksi = ProdukSample::with('produkSampleDetails')->find($this->produkSampleId);

        if ($produksi) {
            $this->produkSampleStatus = $produksi->status;

            foreach ($produksi->produkSampleDetails as $detail) {

                $this->produkSampleDetails[] = [
                    'bahan_id' => $detail->bahan_id ?? null,
                    'produk_id' => $detail->produk_id ?? null,
                    'jml_bahan' => $detail->jml_bahan,
                    'used_materials' => $detail->used_materials ?? 0,
                    'sub_total' => $detail->sub_total,
                    'serial_number' => $detail->serial_number ?? null,
                    'details' => json_decode($detail->details, true),
                ];
            }
        }
    }

    public function loadBahanKeluar()
    {
        $existingBahanKeluar = BahanKeluar::where('produk_sample_id', $this->produkSampleId)->exists();
        $this->isFirstTimePengajuan = !$existingBahanKeluar;

        $this->bahanKeluars = BahanKeluar::with('bahanKeluarDetails.dataBahan')
            ->where('status', 'Belum disetujui')
            ->where('produk_sample_id', $this->produkSampleId)
            ->get();

        $this->pendingReturCount = BahanRetur::where('produk_sample_id', $this->produkSampleId)
            ->where('status', 'Belum disetujui')
            ->count();

        $this->pendingRusakCount = BahanRusak::where('produk_sample_id', $this->produkSampleId)
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

        // Tentukan ID yang akan digunakan
        $itemId = !empty($bahan->produk_id) ? $bahan->produk_id : $bahan->bahan_id;
        if (!$itemId) {
            session()->flash('error', 'ID bahan atau produk tidak ditemukan.');
            return;
        }

        // Cek apakah item sudah ada di produkSampleDetails
        $itemExists = collect($this->produkSampleDetails)->first(function ($item) use ($bahan) {
            return ($item['bahan_id'] ?? null) === ($bahan->bahan_id ?? null) &&
                ($item['produk_id'] ?? null) === ($bahan->produk_id ?? null);
        });

        if ($itemExists) {
            session()->flash('error', 'Bahan atau produk sudah ada di keranjang.');
            return;
        }

        // Periksa ketersediaan bahan
        $totalAvailable = 0;
        if (!empty($bahan->produk_id)) {
            // Cek di bahan setengah jadi details
            $bahanSetengahjadiDetails = BahanSetengahjadiDetails::where('id', $bahan->produk_id)
                ->where('sisa', '>', 0)
                ->with(['bahanSetengahjadi' => function ($query) {
                    $query->orderBy('tgl_masuk', 'asc');
                }])->get();
            $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
        } else {
            // Cek di purchase details untuk bahan biasa
            $purchaseDetails = PurchaseDetail::where('bahan_id', $bahan->bahan_id)
                ->where('sisa', '>', 0)
                ->with(['purchase' => function ($query) {
                    $query->orderBy('tgl_masuk', 'asc');
                }])->get();
            $totalAvailable = $purchaseDetails->sum('sisa');
        }

        if ($totalAvailable <= 0) {
            session()->flash('error', 'Sisa bahan tidak tersedia.');
            return;
        }

        // Tambahkan bahan ke keranjang
        $this->cart[] = (object)[
            'id' => $itemId,
            'bahan_id' => $bahan->bahan_id ?? null,
            'produk_id' => $bahan->produk_id ?? null,
            'serial_number' => $bahan->serial_number ?? null,
            'nama_bahan' => $bahan->nama,
            'stok' => $bahan->stok,
            'unit' => $bahan->unit,
            'newly_added' => true
        ];
        $this->qty[$itemId] = null;
        $this->subtotals[$itemId] = property_exists($bahan, 'unit_price') ? $bahan->unit_price : 0;

        // Tambahkan bahan ke `produkSampleDetails`
        $this->produkSampleDetails[] = [
            'bahan_id' => $bahan->bahan_id ?? null,
            'produk_id' => $bahan->produk_id ?? null,
            'serial_number' => $bahan->serial_number ?? null,
            'qty' => null,
            'jml_bahan' => 0,
            'sub_total' => 0,
            'details' => [],
            'newly_added' => true,
        ];

        // Hitung total harga
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

    public function updateQuantity($type, $itemId)
    {
        $requestedQty = $this->qty[$itemId] ?? 0;
        if ($type==='produk') {
            // Cek di bahan setengah jadi details
            $bahanSetengahjadiDetails = BahanSetengahjadiDetails::where('id', $itemId)
                ->where('sisa', '>', 0)
                ->with(['bahanSetengahjadi' => function ($query) {
                    $query->orderBy('tgl_masuk', 'asc');
                }])->get();

                $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
                if ($requestedQty > $totalAvailable) {
                    $this->qty[$itemId] = $totalAvailable;
                } else {
                    $this->qty[$itemId] = $requestedQty;
                }
        } else {
            // Cek di purchase details untuk bahan biasa
            $purchaseDetails = PurchaseDetail::where('bahan_id', $itemId)
                ->where('sisa', '>', 0)
                ->with(['purchase' => function ($query) {
                    $query->orderBy('tgl_masuk', 'asc');
                }])->get();

                $totalAvailable = $purchaseDetails->sum('sisa');
                if ($requestedQty > $totalAvailable) {
                    $this->qty[$itemId] = $totalAvailable;
                } else {
                    $this->qty[$itemId] = $requestedQty;
                }
        }
    }

    public function saveUnitPrice($itemId)
    {
        $this->formatToRupiah($itemId);
    }

    public function removeItem($itemId)
    {
        // Hapus dari cart
        $this->cart = array_filter($this->cart, function ($item) use ($itemId) {
            return $item->id != $itemId;
        });

        // Hapus dari produkSampleDetails
        $this->produkSampleDetails = array_filter($this->produkSampleDetails, function ($detail) use ($itemId) {
            return ($detail['bahan_id'] ?? $detail['produk_id']) != $itemId;
        });

        // Reset array index
        $this->cart = array_values($this->cart);
        $this->produkSampleDetails = array_values($this->produkSampleDetails);

        session()->flash('message', 'Item berhasil dihapus.');
    }

    // public function decreaseQuantityPerPrice($type, $itemId, $unitPrice)
    // {
    //     foreach ($this->produkSampleDetails as &$detail) {
    //         $bahanId = $detail['bahan_id'] ?? null;
    //         $produkId = $detail['produk_id'] ?? null;

    //         // Tentukan ID berdasarkan tipe (bahan atau produk)
    //         $currentId = ($type === 'bahan') ? $bahanId : (($type === 'produk') ? $produkId : null);
    //         // dd($currentId);
    //         // Jika tidak ada ID yang cocok, lanjutkan ke iterasi berikutnya
    //         if ($currentId !== $itemId) {
    //             continue;
    //         }

    //         foreach ($detail['details'] as &$d) {
    //             if ($d['unit_price'] === $unitPrice && $d['qty'] > 0) {
    //                 $found = false;

    //                 // Cek apakah item sudah ada dalam bahanRusak atau produkRusak
    //                 foreach ($this->bahanRusak as &$rusak) {
    //                     if (
    //                         (($type === 'bahan' && isset($rusak['bahan_id']) && $rusak['bahan_id'] === $itemId) ||
    //                             ($type === 'produk' && isset($rusak['produk_id']) && $rusak['produk_id'] === $itemId)) &&
    //                         $rusak['unit_price'] === $unitPrice
    //                     ) {
    //                         $rusak['qty'] += 1;
    //                         $found = true;
    //                         break;
    //                     }
    //                 }

    //                 // Jika belum ada, tambahkan ke bahanRusak
    //                 if (!$found) {
    //                     $this->bahanRusak[] = [
    //                         'bahan_id' => ($type === 'bahan') ? $itemId : null,
    //                         'produk_id' => ($type === 'produk') ? $itemId : null,
    //                         'serial_number' => $detail['serial_number'] ?? null,
    //                         'qty' => 1,
    //                         'unit_price' => $unitPrice,

    //                     ];
    //                 }

    //                 // Kurangi qty, pastikan tidak negatif
    //                 $d['qty'] = max(0, $d['qty'] - 1);
    //                 $detail['sub_total'] = max(0, $detail['sub_total'] - $unitPrice);

    //                 break; // Hentikan iterasi setelah menemukan dan memperbarui item
    //             }
    //         }
    //         break; // Hentikan iterasi setelah menemukan detail yang sesuai
    //     }

    //     $this->calculateTotalHarga();
    // }
    public function decreaseQuantityPerPrice($type, $itemId, $unitPrice)
    {
        foreach ($this->produkSampleDetails as &$detail) {
            $bahanId = $detail['bahan_id'] ?? null;
            $produkId = $detail['produk_id'] ?? null;

            $currentId = ($type === 'bahan') ? $bahanId : (($type === 'produk') ? $produkId : null);
            if ($currentId !== $itemId) {
                continue;
            }

            // ❌ Jika sudah ada di bahanRetur, jangan tambahkan ke bahanRusak
            foreach ($this->bahanRetur as $retur) {
                if (
                    ($type === 'bahan' && isset($retur['bahan_id']) && $retur['bahan_id'] === $itemId) ||
                    ($type === 'produk' && isset($retur['produk_id']) && $retur['produk_id'] === $itemId)
                ) {
                    session()->flash('error', 'Item ini sudah masuk daftar retur dan tidak bisa ditandai rusak.');
                    return; // langsung keluar
                }
            }

            // Cek apakah item sudah ada dalam bahanRusak
            $alreadyExists = false;
            foreach ($this->bahanRusak as $rusak) {
                if (
                    ($type === 'bahan' && isset($rusak['bahan_id']) && $rusak['bahan_id'] === $itemId) ||
                    ($type === 'produk' && isset($rusak['produk_id']) && $rusak['produk_id'] === $itemId)
                ) {
                    $alreadyExists = true;
                    break;
                }
            }

            // Jika belum ada, tambahkan ke bahanRusak
            if (!$alreadyExists) {
                $this->bahanRusak[] = [
                    'bahan_id' => ($type === 'bahan') ? $itemId : null,
                    'produk_id' => ($type === 'produk') ? $itemId : null,
                    'unit_price' => $unitPrice,
                    'serial_number' => $detail['serial_number'] ?? null,
                ];
            }

            break; // Hentikan iterasi setelah item ditemukan dan diproses
        }

        // Tidak perlu update qty atau hitung ulang harga
    }

    // public function returQuantityPerPrice($type, $itemId, $unitPrice)
    // {
    //     foreach ($this->produkSampleDetails as &$detail) {
    //         $bahanId = $detail['bahan_id'] ?? null;
    //         $produkId = $detail['produk_id'] ?? null;

    //         // Tentukan ID berdasarkan tipe (bahan atau produk)
    //         $currentId = ($type === 'bahan') ? $bahanId : (($type === 'produk') ? $produkId : null);
    //         // dd($currentId);
    //         // Jika tidak ada ID yang cocok, lanjutkan ke iterasi berikutnya
    //         if ($currentId !== $itemId) {
    //             continue;
    //         }

    //         foreach ($detail['details'] as &$d) {
    //             if ($d['unit_price'] === $unitPrice && $d['qty'] > 0) {
    //                 $found = false;

    //                 // Cek apakah item sudah ada dalam bahanRusak atau produkRusak
    //                 foreach ($this->bahanRetur as &$retur) {
    //                     if (
    //                         (($type === 'bahan' && isset($retur['bahan_id']) && $retur['bahan_id'] === $itemId) ||
    //                             ($type === 'produk' && isset($retur['produk_id']) && $retur['produk_id'] === $itemId)) &&
    //                         $retur['unit_price'] === $unitPrice
    //                     ) {
    //                         $retur['qty'] += 1;
    //                         $found = true;
    //                         break;
    //                     }
    //                 }

    //                 // Jika belum ada, tambahkan ke bahanRetur
    //                 if (!$found) {
    //                     $this->bahanRetur[] = [
    //                         'bahan_id' => ($type === 'bahan') ? $itemId : null,
    //                         'produk_id' => ($type === 'produk') ? $itemId : null,
    //                         'serial_number' => $detail['serial_number'] ?? null,
    //                         'qty' => 1,
    //                         'unit_price' => $unitPrice,

    //                     ];
    //                 }

    //                 // Kurangi qty, pastikan tidak negatif
    //                 $d['qty'] = max(0, $d['qty'] - 1);
    //                 $detail['sub_total'] = max(0, $detail['sub_total'] - $unitPrice);

    //                 break; // Hentikan iterasi setelah menemukan dan memperbarui item
    //             }
    //         }
    //         break; // Hentikan iterasi setelah menemukan detail yang sesuai
    //     }

    //     $this->calculateTotalHarga();
    // }
    public function returQuantityPerPrice($type, $itemId, $unitPrice)
    {
        foreach ($this->produkSampleDetails as &$detail) {
            $bahanId = $detail['bahan_id'] ?? null;
            $produkId = $detail['produk_id'] ?? null;

            $currentId = ($type === 'bahan') ? $bahanId : (($type === 'produk') ? $produkId : null);
            if ($currentId !== $itemId) {
                continue;
            }

            // ❌ Jika sudah ada di bahan rusak, jangan tambahkan ke bahan retur
            foreach ($this->bahanRusak as $rusak) {
                if (
                    ($type === 'bahan' && isset($rusak['bahan_id']) && $rusak['bahan_id'] === $itemId) ||
                    ($type === 'produk' && isset($rusak['produk_id']) && $rusak['produk_id'] === $itemId)
                ) {
                    session()->flash('error', 'Item ini sudah masuk daftar bahan rusak dan tidak bisa diretur.');
                    return; // keluar langsung
                }
            }

            // Cek apakah item sudah ada dalam bahanRetur
            foreach ($this->bahanRetur as $retur) {
                if (
                    ($type === 'bahan' && isset($retur['bahan_id']) && $retur['bahan_id'] === $itemId) ||
                    ($type === 'produk' && isset($retur['produk_id']) && $retur['produk_id'] === $itemId)
                ) {
                    return; // sudah ada di bahanRetur, tidak perlu ditambahkan lagi
                }
            }

            // Tambahkan ke bahanRetur
            $this->bahanRetur[] = [
                'bahan_id' => ($type === 'bahan') ? $itemId : null,
                'produk_id' => ($type === 'produk') ? $itemId : null,
                'unit_price' => $unitPrice,
                'serial_number' => $detail['serial_number'] ?? null,
            ];

            break; // hentikan setelah ditambahkan
        }
    }

    // public function returnToProduction($type, $itemId, $unitPrice)
    // {
    //     foreach ($this->bahanRusak as $key => &$rusak) {
    //         // Pastikan bahan_id atau produk_id sesuai dengan tipe yang diberikan
    //         if (
    //             (($type === 'bahan' && isset($rusak['bahan_id']) && $rusak['bahan_id'] === $itemId) ||
    //             ($type === 'produk' && isset($rusak['produk_id']) && $rusak['produk_id'] === $itemId)) &&
    //             $rusak['unit_price'] === $unitPrice
    //         ) {
    //             // Kurangi qty, hapus jika qty == 0
    //             $rusak['qty'] -= 1;
    //             if ($rusak['qty'] <= 0) {
    //                 unset($this->bahanRusak[$key]);
    //             }
    //             break;
    //         }
    //     }

    //     // Kembalikan qty ke produkSampleDetails
    //     foreach ($this->produkSampleDetails as &$detail) {
    //         $bahanId = $detail['bahan_id'] ?? null;
    //         $produkId = $detail['produk_id'] ?? null;
    //         $currentId = ($type === 'bahan') ? $bahanId : (($type === 'produk') ? $produkId : null);

    //         if ($currentId === $itemId) {
    //             foreach ($detail['details'] as &$d) {
    //                 if ($d['unit_price'] === $unitPrice) {
    //                     $d['qty'] += 1;
    //                     $detail['sub_total'] += $unitPrice;
    //                     break;
    //                 }
    //             }
    //             break;
    //         }
    //     }

    //     $this->calculateTotalHarga();
    // }
    public function returnToProduction($type, $itemId, $unitPrice)
    {
        foreach ($this->bahanRusak as $key => $rusak) {
            $isMatch = false;

            if ($type === 'bahan' && isset($rusak['bahan_id']) && $rusak['bahan_id'] === $itemId) {
                $isMatch = true;
            } elseif ($type === 'produk' && isset($rusak['produk_id']) && $rusak['produk_id'] === $itemId) {
                $isMatch = true;
            }

            if ($isMatch && isset($rusak['unit_price']) && $rusak['unit_price'] === $unitPrice) {
                unset($this->bahanRusak[$key]);
                break;
            }
        }

        // Tidak perlu update projekDetails atau qty
        $this->calculateTotalHarga();
    }

    // public function returnReturToProduction($type, $itemId, $unitPrice)
    // {
    //     foreach ($this->bahanRetur as $key => &$retur) {
    //         // Pastikan bahan_id atau produk_id sesuai dengan tipe yang diberikan
    //         if (
    //             (($type === 'bahan' && isset($retur['bahan_id']) && $retur['bahan_id'] === $itemId) ||
    //             ($type === 'produk' && isset($retur['produk_id']) && $retur['produk_id'] === $itemId)) &&
    //             $retur['unit_price'] === $unitPrice
    //         ) {
    //             // Kurangi qty, hapus jika qty == 0
    //             $retur['qty'] -= 1;
    //             if ($retur['qty'] <= 0) {
    //                 unset($this->bahanRetur[$key]);
    //             }
    //             break;
    //         }
    //     }

    //     // Kembalikan qty ke produkSampleDetails
    //     foreach ($this->produkSampleDetails as &$detail) {
    //         $bahanId = $detail['bahan_id'] ?? null;
    //         $produkId = $detail['produk_id'] ?? null;
    //         $currentId = ($type === 'bahan') ? $bahanId : (($type === 'produk') ? $produkId : null);

    //         if ($currentId === $itemId) {
    //             foreach ($detail['details'] as &$d) {
    //                 if ($d['unit_price'] === $unitPrice) {
    //                     $d['qty'] += 1;
    //                     $detail['sub_total'] += $unitPrice;
    //                     break;
    //                 }
    //             }
    //             break;
    //         }
    //     }

    //     $this->calculateTotalHarga();
    // }
    public function returnReturToProduction($type, $itemId, $unitPrice)
    {
        foreach ($this->bahanRetur as $key => $retur) {
            $isMatch = false;

            if ($type === 'bahan' && isset($retur['bahan_id']) && $retur['bahan_id'] === $itemId) {
                $isMatch = true;
            } elseif ($type === 'produk' && isset($retur['produk_id']) && $retur['produk_id'] === $itemId) {
                $isMatch = true;
            }

            if ($isMatch && isset($retur['unit_price']) && $retur['unit_price'] === $unitPrice) {
                unset($this->bahanRetur[$key]);
                break;
            }
        }

        // Tidak perlu update projekDetails atau qty
        $this->calculateTotalHarga();
    }

    public function getCartItemsForStorage()
    {
        $grandTotal = 0;
        $produkSampleDetails = [];

        foreach ($this->produkSampleDetails as $item) {
            // Ambil bahan_id atau produk_id langsung dari array
            $bahanId = $item['bahan_id'] ?? null;
            $produkId = $item['produk_id'] ?? null;

            // Pilih ID yang valid
            $itemId = $bahanId ?? $produkId;

            // Lewati jika tidak ada ID yang valid
            if (!$itemId) {
                continue;
            }

            $usedMaterials = $this->qty[$itemId] ?? 0;

            if ($usedMaterials <= 0) {
                continue;
            }

            $totalPrice = 0;
            $details = [];

            $produkSampleDetails[] = [
                // 'id' => $itemId,
                'bahan_id' => $bahanId,
                'produk_id' => $produkId,
                'qty' => $this->qty[$itemId],
                'jml_bahan' => $this->jml_bahan[$itemId] ?? 0,
                'details' => $details,
                'serial_number' => $item['serial_number'] ?? null,
                'sub_total' => $totalPrice,
            ];
        }
        return $produkSampleDetails;
    }

    public function getCartItemsForBahanRusak()
    {
        $bahanRusak = [];

        foreach ($this->bahanRusak as $rusak) {
            // Ambil ID berdasarkan apakah itu bahan atau produk
            $bahanId = $rusak['bahan_id'] ?? null;
            $produkId = $rusak['produk_id'] ?? null;

            // Jika keduanya null, lewati iterasi ini
            if ($bahanId === null && $produkId === null) {
                continue;
            }

            $bahanRusak[] = [
                'bahan_id' => $bahanId,
                'produk_id' => $produkId,
                'serial_number' => $rusak['serial_number'] ?? null,
                'qty' => $rusak['qty'] ?? 0,
                'unit_price' => $rusak['unit_price'] ?? 0,
                'sub_total' => ($rusak['qty'] ?? 0) * ($rusak['unit_price'] ?? 0),
            ];
        }

        return $bahanRusak;
    }

    public function getCartItemsForBahanRetur()
    {
        $bahanRetur = [];

        foreach ($this->bahanRetur as $retur) {
            // Ambil ID berdasarkan apakah itu bahan atau produk
            $bahanId = $retur['bahan_id'] ?? null;
            $produkId = $retur['produk_id'] ?? null;

            // Jika keduanya null, lewati iterasi ini
            if ($bahanId === null && $produkId === null) {
                continue;
            }

            $bahanRetur[] = [
                'bahan_id' => $bahanId,
                'produk_id' => $produkId,
                'serial_number' => $retur['serial_number'] ?? null,
                'qty' => $retur['qty'] ?? 0,
                'unit_price' => $retur['unit_price'] ?? 0,
                'sub_total' => ($retur['qty'] ?? 0) * ($retur['unit_price'] ?? 0),
            ];
        }

        return $bahanRetur;
    }

    public function updateRusakQty($id, $unitPrice, $newQty)
    {
        $parsedQty = floatval(str_replace(',', '.', $newQty));
        $maxQty = 0;

        // Loop produkSampleDetails untuk bahan_id / produk_id
        foreach ($this->produkSampleDetails as $detail) {
            $match = false;

            // Cek apakah item adalah bahan
            if (isset($detail['bahan_id']) && $detail['bahan_id'] == $id) {
                $match = true;
            }

            // Cek apakah item adalah produk
            if (isset($detail['produk_id']) && $detail['produk_id'] == $id) {
                $match = true;
            }

            if ($match) {
                foreach ($detail['details'] as $d) {
                    if ($d['unit_price'] == $unitPrice) {
                        $maxQty += $d['qty']; // Hanya total dari unit_price yang cocok
                    }
                }
                break;
            }
        }

        // Validasi agar tidak melebihi qty pengambilan
        if ($parsedQty > $maxQty) {
            $parsedQty = $maxQty;
            session()->flash('error', 'Qty rusak tidak boleh melebihi jumlah pengambilan.');
        }

        // Update qty di bahanRusak
        foreach ($this->bahanRusak as &$rusak) {
            $match = false;

            if (
                (isset($rusak['bahan_id']) && $rusak['bahan_id'] == $id) ||
                (isset($rusak['produk_id']) && $rusak['produk_id'] == $id)
            ) {
                if (isset($rusak['unit_price']) && $rusak['unit_price'] == $unitPrice) {
                    $match = true;
                }
            }

            if ($match) {
                $rusak['qty'] = max(0, $parsedQty);
                break;
            }
        }

        $this->calculateTotalHarga();
    }

    public function updateReturQty($id, $unitPrice, $newQty)
    {
        $parsedQty = floatval(str_replace(',', '.', $newQty));
        $maxQty = 0;

        // Loop produkSampleDetails untuk bahan_id / produk_id
        foreach ($this->produkSampleDetails as $detail) {
            $match = false;

            // Cek apakah item adalah bahan
            if (isset($detail['bahan_id']) && $detail['bahan_id'] == $id) {
                $match = true;
            }

            // Cek apakah item adalah produk
            if (isset($detail['produk_id']) && $detail['produk_id'] == $id) {
                $match = true;
            }

            if ($match) {
                foreach ($detail['details'] as $d) {
                    if ($d['unit_price'] == $unitPrice) {
                        $maxQty += $d['qty']; // Hanya total dari unit_price yang cocok
                    }
                }
                break;
            }
        }

        // Validasi agar tidak melebihi qty pengambilan
        if ($parsedQty > $maxQty) {
            $parsedQty = $maxQty;
            session()->flash('error', 'Qty retur tidak boleh melebihi jumlah pengambilan.');
        }

        // Update qty di bahanRetur
        foreach ($this->bahanRetur as &$retur) {
            $match = false;

            if (
                (isset($retur['bahan_id']) && $retur['bahan_id'] == $id) ||
                (isset($retur['produk_id']) && $retur['produk_id'] == $id)
            ) {
                if (isset($retur['unit_price']) && $retur['unit_price'] == $unitPrice) {
                    $match = true;
                }
            }

            if ($match) {
                $retur['qty'] = max(0, $parsedQty);
                break;
            }
        }

        $this->calculateTotalHarga();
    }

    public function render()
    {
        $produksiTotal = array_sum(array_column($this->produkSampleDetails, 'sub_total'));

        return view('livewire.edit-bahan-produk-sample-cart', [
            'cartItems' => $this->cart,
            'produkSampleDetails' => $this->produkSampleDetails,
            'produksiTotal' => $produksiTotal,
            'bahanRusak' => $this->bahanRusak,
            'bahanRetur' => $this->bahanRetur,
        ]);
    }
}
