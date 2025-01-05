<?php

namespace App\Livewire;

use App\Models\Bahan;
use App\Models\Pengajuan;
use Livewire\Component;
use App\Models\Produksi;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;

class EditBahanKeluarCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $jml_bahan = [];
    public $details = [];
    public $details_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;
    public $bahanKeluarId;
    public $bahanKeluarDetails = [];
    public $bahanRusak = [];
    public $bahanRetur = [];
    public $isFirstTimePengajuan = [];
    public $isBahanReturPending = [];
    public $pendingReturCount = [];
    public $isBahanRusakPending = [];
    public $pendingRusakCount = [];
    public $produksiStatus,$status;

    protected $listeners = [
        'bahanSelected' => 'addToCart',
        'bahanSetengahJadiSelected' => 'addToCart'
    ];

    public $bahanKeluars = [];

    public function mount($bahanKeluarId)
    {
        $this->bahanKeluarId = $bahanKeluarId;
        $bahanKeluar = BahanKeluar::findOrFail($bahanKeluarId);
        $this->status = $bahanKeluar->status;
        $this->loadProduksi();

        foreach ($this->bahanKeluarDetails as $detail) {
            $bahanId = $detail['bahan']->id;
            $requestedQty = $detail['qty'] ?? 0;

            // Ambil data bahan untuk menghitung stok dan subtotal
            $item = Bahan::find($bahanId);

            if ($item) {
                if ($item->jenisBahan->nama === 'Produksi') {
                    // Stok bahan setengah jadi
                    $bahanSetengahjadiDetails = $item->bahanSetengahjadiDetails()
                        ->where('sisa', '>', 0)
                        ->with(['bahanSetengahjadi' => function ($query) {
                            $query->orderBy('tgl_masuk', 'asc');
                        }])->get();

                    $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
                    $this->qty[$bahanId] = $totalAvailable > 0
                    ? min($requestedQty, $totalAvailable)
                    : $requestedQty;

                    // $this->qty[$bahanId] = min($requestedQty, $totalAvailable);
                    $this->updateUnitPriceAndSubtotalBahanSetengahJadi($bahanId, $this->qty[$bahanId], $bahanSetengahjadiDetails);

                } else {
                    // Stok dari purchase details
                    // $purchaseDetails = $item->purchaseDetails()
                    //     ->where('sisa', '>', 0)
                    //     ->with(['purchase' => function ($query) {
                    //         $query->orderBy('tgl_masuk', 'asc');
                    //     }])->get();
                    $purchaseDetails = $item->purchaseDetails()
                    ->where('sisa', '>', 0)
                    ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
                    ->orderBy('purchases.tgl_masuk', 'asc')
                    ->select('purchase_details.*', 'purchases.tgl_masuk')
                    ->get();


                    $totalAvailable = $purchaseDetails->sum('sisa');
                    // Jika stok tersedia 0, gunakan requestedQty
                    $this->qty[$bahanId] = $totalAvailable > 0
                    ? min($requestedQty, $totalAvailable)
                    : $requestedQty;

                    // $this->qty[$bahanId] = min($requestedQty, $totalAvailable);
                    $this->updateUnitPriceAndSubtotal($bahanId, $this->qty[$bahanId], $purchaseDetails);
                }
            }
        }

        $this->calculateTotalHarga();
    }


    public function loadProduksi()
    {
        $produksi = BahanKeluar::with('bahanKeluarDetails')->find($this->bahanKeluarId);

        if ($produksi) {
            $this->produksiStatus = $produksi->status;
            foreach ($produksi->bahanKeluarDetails as $detail) {
                $this->bahanKeluarDetails[] = [
                    'bahan' => Bahan::find($detail->bahan_id), // Ensure this returns an object
                    'qty' => $detail->qty,
                    'jml_bahan' => $detail->jml_bahan,
                    'used_materials' => $detail->used_materials ?? 0,
                    'sub_total' => $detail->sub_total,
                    'details' => json_decode($detail->details, true),
                ];
            }
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

    protected function updateUnitPriceAndSubtotalBahanSetengahJadi($itemId, $qty, $bahanSetengahjadiDetails)
    {
        $remainingQty = $qty;
        $totalPrice = 0;
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
    }

    protected function updateUnitPriceAndSubtotal($itemId, $qty, $purchaseDetails)
    {
        $remainingQty = $qty;
        $totalPrice = 0;
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
    }

    public function saveUnitPrice($itemId)
    {
        $this->formatToRupiah($itemId);
    }


    public function getCartItemsForStorage()
    {
        $grandTotal = 0;
        $bahanKeluarDetails = [];

        foreach ($this->bahanKeluarDetails as $item) {
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
                // $purchaseDetails = $item['bahan']->purchaseDetails()
                //     ->where('sisa', '>', 0)
                //     ->with(['purchase' => function ($query) {
                //         $query->orderBy('tgl_masuk', 'asc');
                //     }])->get();
                $purchaseDetails = $item['bahan']->purchaseDetails()
                ->where('sisa', '>', 0)
                ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
                ->orderBy('purchases.tgl_masuk', 'asc')
                ->select('purchase_details.*', 'purchases.tgl_masuk')
                ->get();


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
            $bahanKeluarDetails[] = [
                'id' => $bahanId,
                'qty' => $this->qty[$bahanId],
                'jml_bahan' => $item['jml_bahan'],
                'details' => $details,
                'sub_total' => $totalPrice,
            ];
        }
        return $bahanKeluarDetails;
    }


    public function render()
    {
        $produksiTotal = array_sum(array_column($this->bahanKeluarDetails, 'sub_total'));

        return view('livewire.edit-bahan-keluar-cart', [
            'cartItems' => $this->cart,
            'bahanKeluarDetails' => $this->bahanKeluarDetails,
            'produksiTotal' => $produksiTotal,
        ]);
    }
}
