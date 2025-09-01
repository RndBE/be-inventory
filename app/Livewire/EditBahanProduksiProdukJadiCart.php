<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use App\Models\PurchaseDetail;
use App\Models\ProduksiProdukJadi;
use App\Models\BahanSetengahjadiDetails;

class EditBahanProduksiProdukJadiCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $jml_bahan = [];
    public $details = [];
    public $details_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;
    public $produksiProdukJadiId;
    public $produksiProdukJadiDetails = [];
    public $bahanRusak = [];
    public $bahanRetur = [];
    public $isFirstTimePengajuan = [];
    public $isBahanReturPending = [];
    public $pendingReturCount = [];
    public $isBahanRusakPending = [];
    public $pendingRusakCount = [];
    public $produksiProdukJadisStatus;
    public $produksiStatus;

    protected $listeners = [
        'bahanSelected' => 'addToCart',
        'bahanSetengahJadiSelected' => 'addToCart'
    ];

    public $bahanKeluars = [];

    public function mount($produksiProdukJadiId)
    {
        $this->produksiProdukJadiId = $produksiProdukJadiId;
        $this->loadProduksi();
        $this->loadBahanKeluar();
    }

    public function loadProduksi()
    {
        $produksiProdukJadis = ProduksiProdukJadi::with('produksiProdukJadiDetails')->find($this->produksiProdukJadiId);

        if ($produksiProdukJadis) {
            $this->produksiStatus = $produksiProdukJadis->status;

            foreach ($produksiProdukJadis->produksiProdukJadiDetails as $detail) {

                $this->produksiProdukJadiDetails[] = [
                    'bahan_id' => $detail->bahan_id ?? null,
                    'produk_id' => $detail->produk_id ?? null,
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
        $existingBahanKeluar = BahanKeluar::where('produksi_produk_jadi_id', $this->produksiProdukJadiId)->exists();
        $this->isFirstTimePengajuan = !$existingBahanKeluar;

        $this->bahanKeluars = BahanKeluar::with('bahanKeluarDetails.dataBahan')
            ->where('status', 'Belum disetujui')
            ->where('produksi_produk_jadi_id', $this->produksiProdukJadiId)
            ->get();

        $this->pendingReturCount = BahanRetur::where('produksi_produk_jadi_id', $this->produksiProdukJadiId)
            ->where('status', 'Belum disetujui')
            ->count();

        $this->pendingRusakCount = BahanRusak::where('produksi_produk_jadi_id', $this->produksiProdukJadiId)
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
        // Cek apakah item sudah ada di projekDetails
        $itemExists = collect($this->produksiProdukJadiDetails)->first(function ($item) use ($bahan) {
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

        // Tambahkan bahan ke `projekDetails`
        $this->produksiProdukJadiDetails[] = [
            'bahan_id' => $bahan->bahan_id ?? null,
            'produk_id' => $bahan->produk_id ?? null,
            'serial_number' => $bahan->serial_number ?? null,
            'qty' => null,
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

        // Hapus dari projekDetails
        $this->produksiProdukJadiDetails = array_filter($this->produksiProdukJadiDetails, function ($detail) use ($itemId) {
            return ($detail['bahan_id'] ?? $detail['produk_id']) != $itemId;
        });

        // Reset array index
        $this->cart = array_values($this->cart);
        $this->produksiProdukJadiDetails = array_values($this->produksiProdukJadiDetails);

        session()->flash('message', 'Item berhasil dihapus.');
    }

    public function decreaseQuantityPerPrice($type, $itemId, $unitPrice)
    {
        foreach ($this->produksiProdukJadiDetails as &$detail) {
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
    }

    public function returQuantityPerPrice($type, $itemId, $unitPrice)
    {
        foreach ($this->produksiProdukJadiDetails as &$detail) {
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
        $produksiProdukJadiDetails = [];

        foreach ($this->produksiProdukJadiDetails as $item) {
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

            $produksiProdukJadiDetails[] = [
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
        return $produksiProdukJadiDetails;
    }

    public function getCartItemsForBahanRusak()
    {
        $bahanRusak = [];

        foreach ($this->bahanRusak as $rusak) {
            $bahanId = $rusak['bahan_id'] ?? null;
            $produkId = $rusak['produk_id'] ?? null;

            if ($bahanId === null && $produkId === null) {
                continue;
            }

            $qty = (float)($rusak['qty'] ?? 0);
            $unitPrice = (float)($rusak['unit_price'] ?? 0);

            $bahanRusak[] = [
                'bahan_id'      => $bahanId,
                'produk_id'     => $produkId,
                'serial_number' => $rusak['serial_number'] ?? null,
                'qty'           => $qty,
                'unit_price'    => $unitPrice,
                'sub_total'     => $qty * $unitPrice,
            ];
        }

        return $bahanRusak;
    }

    public function getCartItemsForBahanRetur()
    {
        $bahanRetur = [];

        foreach ($this->bahanRetur as $retur) {
            $bahanId = $retur['bahan_id'] ?? null;
            $produkId = $retur['produk_id'] ?? null;

            if ($bahanId === null && $produkId === null) {
                continue;
            }

            $qty = (float)($retur['qty'] ?? 0);
            $unitPrice = (float)($retur['unit_price'] ?? 0);

            $bahanRetur[] = [
                'bahan_id'      => $bahanId,
                'produk_id'     => $produkId,
                'serial_number' => $retur['serial_number'] ?? null,
                'qty'           => $qty,
                'unit_price'    => $unitPrice,
                'sub_total'     => $qty * $unitPrice,
            ];
        }

        return $bahanRetur;
    }


    public function updateRusakQty($id, $unitPrice, $newQty)
    {
        $parsedQty = floatval(str_replace(',', '.', $newQty));
        $maxQty = 0;

        // Loop projekDetails untuk bahan_id / produk_id
        foreach ($this->produksiProdukJadiDetails as $detail) {
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

        // Loop projekDetails untuk bahan_id / produk_id
        foreach ($this->produksiProdukJadiDetails as $detail) {
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
        $produksiProdukJadisTotal = array_sum(array_column($this->produksiProdukJadiDetails, 'sub_total'));

        return view('livewire.edit-bahan-produksi-produk-jadi-cart', [
            'cartItems' => $this->cart,
            'projekDetails' => $this->produksiProdukJadiDetails,
            'produksiTotal' => $produksiProdukJadisTotal,
            'bahanRusak' => $this->bahanRusak,
            'bahanRetur' => $this->bahanRetur,
        ]);
    }
}
