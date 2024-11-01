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
    public $subtotals = [];
    public $totalharga = 0;
    public $existingBahanIds = [];
    public $editingItemId = 0;
    public $details = [];
    public $details_raw = [];
    public $projekId;
    public $projekDetails = [];
    public $bahanRusak = [];
    public $bahanRetur = [];
    public $produksiStatus;
    public $grandTotal = 0;
    public $qtyInput = [];

    protected $listeners = ['bahanSelected' => 'addToCart', 'bahanSetengahJadiSelected' => 'addToCart'];

    public function mount($projekId)
    {
        // $this->loadCartFromSession(); // Memuat cart dari sesi
        $this->loadExistingBahan($projekId); // Memuat bahan yang sudah ada untuk proyek
    }

    protected function loadExistingBahan($projekId)
{
    // Ambil proyek berdasarkan ID
    $projek = Projek::with('projekDetails.dataBahan')->findOrFail($projekId);

    // Mengisi cart dengan bahan yang sudah ada di projek
    foreach ($projek->projekDetails as $detail) {
        $this->cart[] = (object) [
            'id' => $detail->dataBahan->id,
            'nama_bahan' => $detail->dataBahan->nama_bahan,
            'stok' => $detail->dataBahan->stok,
            'unit' => $detail->dataBahan->unit,
            'details' => json_decode($detail->details) // Decode JSON ke array
        ];

        // Menyimpan kuantitas dan subtotal untuk bahan yang sudah ada
        $this->qty[$detail->dataBahan->id] = $detail->qty; // Asumsikan ada kolom qty di projekDetails
        // $this->subtotals[$detail->dataBahan->id] = $detail->sub_total; // Asumsikan ada kolom sub_total di projekDetails
    }

    // Hitung total harga berdasarkan bahan yang sudah ada
    $this->calculateTotalHarga();
}



public function addToCart($bahan)
{
    if (is_array($bahan)) {
        $bahan = (object) $bahan;
    }
    // Periksa apakah properti 'type' ada sebelum mengaksesnya
    $isSetengahJadi = isset($bahan->type) && $bahan->type === 'setengahjadi';

    $existingItemKey = array_search($bahan->id, array_column($this->cart, 'id'));

    if ($existingItemKey !== false) {
        // Jika item sudah ada di cart, update quantity
        $this->updateQuantity($bahan->id);
    } else {
        // Buat objek item baru
        $item = (object)[
            'id' => $bahan->id,
            'nama_bahan' => $isSetengahJadi ? $bahan->nama : Bahan::find($bahan->id)->nama_bahan,
            'stok' => $bahan->stok,
            'unit' => $bahan->unit,
        ];

        // Tambahkan item ke keranjang
        $this->cart[] = $item;

        // Kosongkan qty dan details untuk item baru
        $this->qty[$bahan->id] = 0; // Atur qty menjadi 0
        $this->details[$bahan->id] = []; // Kosongkan details
        $this->subtotals[$bahan->id] = 0; // Reset subtotal juga jika perlu
    }

    // Simpan ke sesi
    $this->saveCartToSession();
    $this->calculateSubTotal($bahan->id);
}




    protected function saveCartToSession()
    {
        session()->put('cartItems', $this->getCartItemsForStorage());
    }

    protected function loadCartFromSession()
    {
        if (session()->has('cartItems')) {
            $storedItems = session()->get('cartItems');
            foreach ($storedItems as $storedItem) {
                $this->cart[] = (object) ['id' => $storedItem['id'], 'nama_bahan' => Bahan::find($storedItem['id'])->nama_bahan];
                $this->qty[$storedItem['id']] = $storedItem['qty'];
                $this->subtotals[$storedItem['id']] = $storedItem['sub_total'];
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

    public function updateQuantity($itemId)
    {
        $requestedQty = $this->qtyInput[$itemId] ?? 0;
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
                    $this->qtyInput[$itemId] = $totalAvailable;
                } elseif ($requestedQty < 0) {
                    $this->qtyInput[$itemId] = null;
                } else {
                    $this->qtyInput[$itemId] = $requestedQty;
                }
                $this->updateUnitPriceAndSubtotalBahanSetengahJadi($itemId, $this->qtyInput[$itemId], $bahanSetengahjadiDetails);
            } elseif ($item->jenisBahan->nama !== 'Produksi') {
                $purchaseDetails = $item->purchaseDetails()
                    ->where('sisa', '>', 0)
                    ->with(['purchase' => function ($query) {
                        $query->orderBy('tgl_masuk', 'asc');
                    }])->get();

                $totalAvailable = $purchaseDetails->sum('sisa');
                if ($requestedQty > $totalAvailable) {
                    $this->qtyInput[$itemId] = $totalAvailable;
                } elseif ($requestedQty < 0) {
                    $this->qtyInput[$itemId] = null;
                } else {
                    $this->qtyInput[$itemId] = $requestedQty;
                }
                $this->updateUnitPriceAndSubtotal($itemId, $this->qtyInput[$itemId], $purchaseDetails);
            }
        }
    }

    protected function updateUnitPriceAndSubtotalBahanSetengahJadi($itemId, $qtyInput, $bahanSetengahjadiDetails)
    {
        $remainingQty = $qtyInput;
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

    protected function updateUnitPriceAndSubtotal($itemId, $qtyInput, $purchaseDetails)
    {

        $remainingQty = $qtyInput;
        $totalPrice = 0;
        $this->details_raw[$itemId] = [];
        $this->details[$itemId] = [];

        foreach ($purchaseDetails as $purchaseDetail) {
            if ($remainingQty <= 0) break;
            dd(('Checking detail:', ['bahan_id' => $detail['bahan']->id, 'itemId' => $itemId]));

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

    public function returQuantityPerPrice($itemId, $unitPrice)
    {
        foreach ($this->projekDetails as &$detail) {
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

    public function editItem($itemId)
    {
        $this->editingItemId = $itemId; // Set ID item yang sedang diedit
        $this->details_raw[$itemId] = $this->details[$itemId]; // Ambil nilai untuk diedit
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
        $this->saveCartToSession();
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

    public function render()
    {

        return view('livewire.edit-bahan-projek-cart', [
            'cartItems' => $this->cart,
        ]);
    }
}
