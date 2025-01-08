<?php

namespace App\Livewire;

use App\Models\Bahan;
use App\Models\Pengajuan;
use Livewire\Component;
use App\Models\Produksi;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;

class EditBahanPengajuanCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $unit_price = [];
    public $unit_price_usd = [];
    public $new_unit_price = [];
    public $new_unit_price_usd = [];
    public $jml_bahan = [];
    public $details = [];
    public $new_details = [];
    public $details_raw = [];
    public $ongkir = [];
    public $ongkir_raw = [];
    public $asuransi = [];
    public $asuransi_raw = [];
    public $layanan = [];
    public $layanan_raw = [];
    public $jasa_aplikasi = [];
    public $jasa_aplikasi_raw = [];
    public $shipping_cost = [];
    public $shipping_cost_raw = [];
    public $full_amount_fee = [];
    public $full_amount_fee_raw = [];
    public $value_today_fee = [];
    public $value_today_fee_raw = [];
    public $new_shipping_cost = [];
    public $new_shipping_cost_raw = [];
    public $new_full_amount_fee = [];
    public $new_full_amount_fee_raw = [];
    public $new_value_today_fee = [];
    public $new_value_today_fee_raw = [];
    public $shipping_cost_usd = [];
    public $shipping_cost_usd_raw = [];
    public $full_amount_fee_usd = [];
    public $full_amount_fee_usd_raw = [];
    public $value_today_fee_usd = [];
    public $value_today_fee_usd_raw = [];
    public $new_shipping_cost_usd = [];
    public $new_shipping_cost_usd_raw = [];
    public $new_full_amount_fee_usd = [];
    public $new_full_amount_fee_usd_raw = [];
    public $new_value_today_fee_usd = [];
    public $new_value_today_fee_usd_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;
    public $pengajuanId;
    public $pengajuanDetails = [];
    public $bahanRusak = [];
    public $bahanRetur = [];
    public $isFirstTimePengajuan = [];
    public $isBahanReturPending = [];
    public $pendingReturCount = [];
    public $isBahanRusakPending = [];
    public $pendingRusakCount = [];
    public $produksiStatus;
    public $jenis_pengajuan;

    protected $listeners = [
        'bahanSelected' => 'addToCart',
        'bahanSetengahJadiSelected' => 'addToCart'
    ];

    public $bahanKeluars = [];

    public function mount($pengajuanId)
    {
        $this->pengajuanId = $pengajuanId;
        $this->loadProduksi();
        // $this->loadBahanKeluar();

        foreach ($this->pengajuanDetails as $detail) {
            $bahanId = $detail['bahan']->id;
            $this->jml_bahan[$bahanId] = $detail['jml_bahan'] ?? 0;
        }
        $pengajuan = Pengajuan::findOrFail($pengajuanId);
        $this->jenis_pengajuan = $pengajuan->jenis_pengajuan;
        $this->ongkir = $pengajuan->ongkir;
        $this->asuransi = $pengajuan->asuransi ?? 0;
        $this->layanan = $pengajuan->layanan ?? 0;
        $this->jasa_aplikasi = $pengajuan->jasa_aplikasi ?? 0;
        $this->shipping_cost = $pengajuan->shipping_cost ?? 0;
        $this->full_amount_fee = $pengajuan->full_amount_fee ?? 0;
        $this->value_today_fee = $pengajuan->value_today_fee ?? 0;
        $this->shipping_cost_usd = $pengajuan->shipping_cost_usd ?? 0;
        $this->full_amount_fee_usd = $pengajuan->full_amount_fee_usd ??0;
        $this->value_today_fee_usd = $pengajuan->value_today_fee_usd ??0;
        $this->new_shipping_cost = $pengajuan->new_shipping_cost ?? 0;
        $this->new_full_amount_fee = $pengajuan->new_full_amount_fee ?? 0;
        $this->new_value_today_fee = $pengajuan->new_value_today_fee ?? 0;
        $this->new_shipping_cost_usd = $pengajuan->new_shipping_cost_usd ?? 0;
        $this->new_full_amount_fee_usd = $pengajuan->new_full_amount_fee_usd ?? 0;
        $this->new_value_today_fee_usd = $pengajuan->new_value_today_fee_usd ?? 0;
    }

    public function loadProduksi()
    {
        $produksi = Pengajuan::with('pengajuanDetails')->find($this->pengajuanId);

        if ($produksi) {
            $this->produksiStatus = $produksi->status;
            foreach ($produksi->pengajuanDetails as $detail) {
                $decodedDetails = json_decode($detail->details, true);
                $decodedDetailsUSD = json_decode($detail->details_usd, true);

                $unitPrice = $decodedDetails[0]['unit_price'] ?? 0;
                $unitPriceUSD = $decodedDetailsUSD[0]['unit_price_usd'] ?? 0;

                $newdecodedDetails = json_decode($detail->new_details, true);
                $newdecodedDetailsUSD = json_decode($detail->new_details_usd, true);
                $newUnitPrice = $newdecodedDetails[0]['unit_price'] ?? 0;
                $newUnitPriceUSD = $newdecodedDetailsUSD[0]['unit_price_usd'] ?? 0;

                $this->unit_price[$detail->bahan_id] = $unitPrice;
                $this->unit_price_usd[$detail->bahan_id] = $unitPriceUSD;
                $this->new_unit_price[$detail->bahan_id] = $newUnitPrice;
                $this->new_unit_price_usd[$detail->bahan_id] = $newUnitPriceUSD;

                $this->pengajuanDetails[] = [
                    'bahan' => Bahan::find($detail->bahan_id), // Ensure this returns an object
                    // 'qty' => $detail->qty,
                    'jml_bahan' => $detail->jml_bahan,
                    'used_materials' => $detail->used_materials ?? 0,
                    'sub_total' => $detail->sub_total,
                    'sub_total_usd' => $detail->sub_total_usd,
                    'details' => $decodedDetails,
                    'details_usd' => $decodedDetailsUSD,
                    'new_sub_total' => $detail->new_sub_total,
                    'new_sub_total_usd' => $detail->new_sub_total_usd,
                    'new_details' => $newdecodedDetails,
                    'new_details_usd' => $newdecodedDetailsUSD,
                    'keterangan_pembayaran' => $detail->keterangan_pembayaran,
                    'spesifikasi' => $detail->spesifikasi,
                    'alasan' => $detail->alasan,
                    'penanggungjawabaset' => $detail->penanggungjawabaset,
                ];
            }
        }
    }


    // public function loadBahanKeluar()
    // {
    //     $existingBahanKeluar = BahanKeluar::where('pengajuan_id', $this->pengajuanId)->exists();
    //     $this->isFirstTimePengajuan = !$existingBahanKeluar;

    //     $this->bahanKeluars = BahanKeluar::with('bahanKeluarDetails.dataBahan')
    //         ->where('status', 'Belum disetujui')
    //         ->where('pengajuan_id', $this->pengajuanId)
    //         ->get();

    //     $this->pendingReturCount = BahanRetur::where('pengajuan_id', $this->pengajuanId)
    //         ->where('status', 'Belum disetujui')
    //         ->count();

    //     $this->pendingRusakCount = BahanRusak::where('pengajuan_id', $this->pengajuanId)
    //         ->where('status', 'Belum disetujui')
    //         ->count();

    //     $this->isBahanReturPending = $this->pendingReturCount > 0;
    //     $this->isBahanRusakPending = $this->pendingRusakCount > 0;
    // }

    // public function addToCart($bahan)
    // {
    //     if (is_array($bahan)) {
    //         $bahan = (object)$bahan;
    //     }

    //     if (!$bahan instanceof Bahan) {
    //         $bahanModel = Bahan::find($bahan->id);
    //         if ($bahanModel) {
    //             $bahan = $bahanModel;
    //         } else {
    //             return;
    //         }
    //     }

    //     // Check if the item already exists in pengajuanDetails
    //     $bahanExistsInPengajuan = false;
    //     foreach ($this->pengajuanDetails as $detail) {
    //         if ($detail['bahan']->id === $bahan->id) {
    //             $bahanExistsInPengajuan = true;
    //             break;
    //         }
    //     }
    //     if ($bahanExistsInPengajuan) {
    //         return;
    //     }

    //     // Check if the item is already in the cart
    //     $existingItemKey = array_search($bahan->id, array_column($this->cart, 'id'));
    //     $unitPrice = property_exists($bahan, 'unit_price') ? $bahan->unit_price : 0;

    //     if ($existingItemKey !== false) {
    //         // Update quantity and subtotal if already exists in the cart
    //         $this->qty[$bahan->id]++;
    //         $this->subtotals[$bahan->id] += $unitPrice;
    //     } else {
    //         // Add new item to the cart
    //         $this->cart[] = (object)[
    //             'id' => $bahan->id,
    //             'nama_bahan' => $bahan->nama,
    //             'stok' => $bahan->stok,
    //             'unit' => $bahan->unit,
    //             'newly_added' => true // Flag for newly added item
    //         ];
    //         $this->qty[$bahan->id] = null;
    //         $this->subtotals[$bahan->id] = $unitPrice;

    //         // Add the detail to pengajuanDetails
    //         $this->pengajuanDetails[] = [
    //             'bahan' => $bahan,
    //             'qty' => null,
    //             'jml_bahan' => 0,
    //             'sub_total' => 0,
    //             'details' => [],
    //             'newly_added' => true // Keep track of newly added item
    //         ];
    //     }

    //     // Calculate total price
    //     $this->totalharga = array_sum($this->subtotals);
    //     $this->saveCartToSession();
    // }

    // protected function saveCartToSession()
    // {
    //     session()->put('cartItems', $this->getCartItemsForStorage());
    // }

    // public function calculateSubTotal($itemId)
    // {
    //     $unitPrice = isset($this->details[$itemId]) ? intval($this->details[$itemId]) : 0;
    //     $qty = isset($this->qty[$itemId]) ? intval($this->qty[$itemId]) : 0;
    //     $this->subtotals[$itemId] = $unitPrice * $qty;
    //     $this->calculateTotalHarga();
    // }


    // public function calculateTotalHarga()
    // {
    //     $this->totalharga = array_sum($this->subtotals);
    // }

    // public function formatToRupiah($itemId)
    // {
    //     $this->details[$itemId] = intval(str_replace(['.', ' '], '', $this->details_raw[$itemId]));
    //     $this->details_raw[$itemId] = $this->details[$itemId];
    //     $this->calculateSubTotal($itemId);
    //     $this->editingItemId = null;
    // }

    // public function updateQuantity($itemId)
    // {
    //     $requestedQty = $this->qty[$itemId] ?? 0;
    //     $item = Bahan::find($itemId);

    //     if ($item) {
    //         if ($item->jenisBahan->nama === 'Produksi') {
    //             // Ambil data stok bahan setengah jadi
    //             $bahanSetengahjadiDetails = $item->bahanSetengahjadiDetails()
    //                 ->where('sisa', '>', 0)
    //                 ->with(['bahanSetengahjadi' => function ($query) {
    //                     $query->orderBy('tgl_masuk', 'asc');
    //                 }])->get();

    //             // Jumlah stok tersedia dari bahan setengah jadi
    //             $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
    //             if ($requestedQty > $totalAvailable) {
    //                 $this->qty[$itemId] = $totalAvailable;
    //             } else {
    //                 $this->qty[$itemId] = $requestedQty;
    //             }

    //             // Perbarui harga unit dan subtotal untuk bahan setengah jadi
    //             $this->updateUnitPriceAndSubtotalBahanSetengahJadi($itemId, $this->qty[$itemId], $bahanSetengahjadiDetails);

    //         } else {
    //             // Ambil data stok dari purchase details
    //             $purchaseDetails = $item->purchaseDetails()
    //                 ->where('sisa', '>', 0)
    //                 ->with(['purchase' => function ($query) {
    //                     $query->orderBy('tgl_masuk', 'asc');
    //                 }])->get();

    //             // Jumlah stok tersedia dari purchase details
    //             $totalAvailable = $purchaseDetails->sum('sisa');
    //             if ($requestedQty > $totalAvailable) {
    //                 $this->qty[$itemId] = $totalAvailable;
    //             } else {
    //                 $this->qty[$itemId] = $requestedQty;
    //             }

    //             // Perbarui harga unit dan subtotal untuk purchase details
    //             $this->updateUnitPriceAndSubtotal($itemId, $this->qty[$itemId], $purchaseDetails);
    //         }
    //     }
    // }

    // protected function updateUnitPriceAndSubtotalBahanSetengahJadi($itemId, $qty, $bahanSetengahjadiDetails)
    // {
    //     $remainingQty = $qty;
    //     $totalPrice = 0;
    //     $this->details_raw[$itemId] = [];
    //     $this->details[$itemId] = [];

    //     foreach ($bahanSetengahjadiDetails as $bahanSetengahjadiDetail) {
    //         if ($remainingQty <= 0) break;

    //         $availableQty = $bahanSetengahjadiDetail->sisa;

    //         if ($availableQty > 0) {
    //             $toTake = min($availableQty, $remainingQty);
    //             $totalPrice += $toTake * $bahanSetengahjadiDetail->unit_price;

    //             $this->details[$itemId][] = [
    //                 'kode_transaksi' => $bahanSetengahjadiDetail->kode_transaksi,
    //                 'qty' => $toTake,
    //                 'unit_price' => $bahanSetengahjadiDetail->unit_price
    //             ];
    //             $remainingQty -= $toTake;
    //         }
    //     }

    //     $this->subtotals[$itemId] = $totalPrice;
    //     $this->calculateTotalHarga();
    // }

    // protected function updateUnitPriceAndSubtotal($itemId, $qty, $purchaseDetails)
    // {
    //     $remainingQty = $qty;
    //     $totalPrice = 0;
    //     $this->details_raw[$itemId] = [];
    //     $this->details[$itemId] = [];

    //     foreach ($purchaseDetails as $purchaseDetail) {
    //         if ($remainingQty <= 0) break;

    //         $availableQty = $purchaseDetail->sisa;

    //         if ($availableQty > 0) {
    //             $toTake = min($availableQty, $remainingQty);
    //             $totalPrice += $toTake * $purchaseDetail->unit_price;

    //             $this->details[$itemId][] = [
    //                 'kode_transaksi' => $purchaseDetail->purchase->kode_transaksi,
    //                 'qty' => $toTake,
    //                 'unit_price' => $purchaseDetail->unit_price
    //             ];
    //             $remainingQty -= $toTake;
    //         }
    //     }

    //     $this->subtotals[$itemId] = $totalPrice;
    //     $this->calculateTotalHarga();
    // }

    // public function saveUnitPrice($itemId)
    // {
    //     $this->formatToRupiah($itemId);
    // }

    // public function removeItem($itemId)
    // {
    //     $this->cart = collect($this->cart)->filter(function ($item) use ($itemId) {
    //         return $item->id !== $itemId;
    //     })->values()->all();
    //     unset($this->subtotals[$itemId]);
    //     $this->pengajuanDetails = collect($this->pengajuanDetails)->filter(function ($detail) use ($itemId) {
    //         return $detail['bahan']->id !== $itemId;
    //     })->values()->all();
    //     $this->calculateTotalHarga();
    // }



    // public function decreaseQuantityPerPrice($itemId, $unitPrice)
    // {
    //     foreach ($this->pengajuanDetails as &$detail) {
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

    // public function returQuantityPerPrice($itemId, $unitPrice)
    // {
    //     foreach ($this->pengajuanDetails as &$detail) {
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

    // public function returnToProduction($itemId, $unitPrice, $qty)
    // {
    //     foreach ($this->bahanRusak as $key => $rusak) {
    //         if ($rusak['id'] === $itemId && $rusak['unit_price'] === $unitPrice) {
    //             $this->bahanRusak[$key]['qty'] -= $qty;
    //             if ($this->bahanRusak[$key]['qty'] <= 0) {
    //                 unset($this->bahanRusak[$key]);
    //             }
    //             $foundInDetails = false;
    //             foreach ($this->pengajuanDetails as &$detail) {
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

    // public function returnReturToProduction($itemId, $unitPrice, $qty)
    // {
    //     foreach ($this->bahanRetur as $key => $retur) {
    //         if ($retur['id'] === $itemId && $retur['unit_price'] === $unitPrice) {
    //             $this->bahanRetur[$key]['qty'] -= $qty;
    //             if ($this->bahanRetur[$key]['qty'] <= 0) {
    //                 unset($this->bahanRetur[$key]);
    //             }
    //             $foundInDetails = false;
    //             foreach ($this->pengajuanDetails as &$detail) {
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

    public function getCartItemsForStorage()
    {
        $grandTotal = 0;
        $pengajuanDetails = [];

        // foreach ($this->pengajuanDetails as $item) {
        //     $bahanId = $item['bahan']->id;
        //     $usedMaterials = $this->qty[$bahanId] ?? 0;

        //     if ($usedMaterials <= 0) {
        //         continue;
        //     }

        //     $totalPrice = 0;
        //     $details = [];

        //     if ($item['bahan']->jenisBahan->nama === 'Produksi') {
        //         $bahanSetengahjadiDetails = $item['bahan']->bahanSetengahjadiDetails()
        //             ->where('sisa', '>', 0)
        //             ->with(['bahanSetengahjadi' => function ($query) {
        //                 $query->orderBy('tgl_masuk', 'asc');
        //             }])->get();
        //         foreach ($bahanSetengahjadiDetails as $detail) {
        //             if ($usedMaterials <= 0) break;

        //             $availableQty = $detail->sisa;
        //             if ($availableQty > 0) {
        //                 $toTake = min($availableQty, $usedMaterials);
        //                 $details[] = [
        //                     'kode_transaksi' => $detail->bahanSetengahjadi->kode_transaksi,
        //                     'qty' => $toTake,
        //                     'unit_price' => $detail->unit_price,
        //                 ];

        //                 $totalPrice += $toTake * $detail->unit_price;
        //                 $usedMaterials -= $toTake;
        //             }
        //         }
        //     } else {
        //         $purchaseDetails = $item['bahan']->purchaseDetails()
        //             ->where('sisa', '>', 0)
        //             ->with(['purchase' => function ($query) {
        //                 $query->orderBy('tgl_masuk', 'asc');
        //             }])->get();

        //         foreach ($purchaseDetails as $detail) {
        //             if ($usedMaterials <= 0) break;

        //             $availableQty = $detail->sisa;
        //             if ($availableQty > 0) {
        //                 $toTake = min($availableQty, $usedMaterials);

        //                 // Add this detail
        //                 $details[] = [
        //                     'kode_transaksi' => $detail->purchase->kode_transaksi,
        //                     'qty' => $toTake,
        //                     'unit_price' => $detail->unit_price,
        //                 ];
        //                 $totalPrice += $toTake * $detail->unit_price;
        //                 $usedMaterials -= $toTake;
        //             }
        //         }
        //     }
        //     $pengajuanDetails[] = [
        //         'id' => $bahanId,
        //         'qty' => $this->qty[$bahanId],
        //         'jml_bahan' => $this->jml_bahan[$bahanId] ?? 0,
        //         'details' => $details,
        //         'sub_total' => $totalPrice,
        //     ];
        // }
        return $pengajuanDetails;
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

    public function render()
    {
        $produksiTotal = array_sum(array_column($this->pengajuanDetails, 'sub_total'));

        return view('livewire.edit-bahan-pengajuan-cart', [
            'cartItems' => $this->cart,
            'pengajuanDetails' => $this->pengajuanDetails,
            'produksiTotal' => $produksiTotal,
            'bahanRusak' => $this->bahanRusak,
            'bahanRetur' => $this->bahanRetur,
        ]);
    }
}
