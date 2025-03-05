<?php

namespace App\Livewire;

use session;
use App\Models\Bahan;
use Livewire\Component;
use App\Models\ProdukProduksi;
use App\Models\PurchaseDetail;
use App\Models\ProdukProduksiDetail;
use App\Models\BahanSetengahjadiDetails;

class BahanProjekCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $jml_bahan = [];
    public $details = [];
    public $details_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;

    protected $listeners = ['bahanSelected' => 'addToCart', 'bahanSetengahJadiSelected' => 'addToCart'];

    public function mount()
    {
        // Load cart items from session if they exist
        // $this->loadCartFromSession();
    }

    public function addToCart($bahan)
    {
        if (is_array($bahan)) {
            $bahan = (object) $bahan;
        }
        // Pilih ID yang benar: Produk ID jika ada, jika tidak gunakan Bahan ID
        if (!empty($bahan->produk_id)) {
            $itemId = $bahan->produk_id;
            $item = BahanSetengahjadiDetails::find($itemId);
        } else {
            $itemId = $bahan->bahan_id;
            $item = Bahan::find($itemId);
        }
        if (!$itemId) {
            session()->flash('error', 'ID bahan tidak ditemukan.');
            return;
        }

        // Cek apakah item sudah ada di keranjang
        $existingItemKey = array_search($itemId, array_column($this->cart, 'id'));
        if ($existingItemKey !== false) {
            $this->updateQuantity($itemId);
            return;
        }

        // Periksa sisa bahan berdasarkan jenisnya
        if (isset($bahan->produk_id)) {
            // Cek di bahan setengah jadi details
            $bahanSetengahjadiDetails = BahanSetengahjadiDetails::where('id', $bahan->produk_id)
                ->where('sisa', '>', 0)
                ->with(['bahanSetengahjadi' => function ($query) {
                    $query->orderBy('tgl_masuk', 'asc');
                }])->get();

            $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
            if ($totalAvailable <= 0) {
                session()->flash('error', 'Sisa bahan tidak ada di bahan setengah jadi.');
                return;
            }
        } else {
            // Cek di purchase details untuk bahan biasa
            $purchaseDetails = PurchaseDetail::where('bahan_id', $bahan->bahan_id)
                ->where('sisa', '>', 0)
                ->with(['purchase' => function ($query) {
                    $query->orderBy('tgl_masuk', 'asc');
                }])->get();

            $totalAvailable = $purchaseDetails->sum('sisa');
            if ($totalAvailable <= 0) {
                session()->flash('error', 'Sisa bahan tidak ada di purchase details.');
                return;
            }
        }

        // Tambahkan item ke keranjang
        $item = (object)[
            'id' => $itemId,
            'bahan_id' => $bahan->bahan_id ?? null,
            'produk_id' => $bahan->produk_id ?? null,
            'serial_number' => $bahan->serial_number ?? null,
            'nama_bahan' => $bahan->nama ?? 'Tanpa Nama',
            'stok' => $bahan->stok ?? 0,
            'unit' => $bahan->unit ?? 'Pcs',
        ];

        $this->cart[] = $item;
        $this->qty[$itemId] = null;
        $this->jml_bahan[$itemId] = null;

        $this->saveCartToSession();
        $this->calculateSubTotal($itemId);
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
                $this->jml_bahan[$storedItem['id']] = $storedItem['jml_bahan'];
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
        $requestedQty = $this->qty[$itemId] ?? 0;

        // Cari item di keranjang berdasarkan itemId
        $cartItem = collect($this->cart)->firstWhere('id', $itemId);

        if ($cartItem) {
            // Cek apakah ini bahan setengah jadi berdasarkan serial number
            if (!empty($cartItem->serial_number)) {
                // Jika bahan setengah jadi, cek sisa stok berdasarkan serial number
                $bahanSetengahJadiDetail = BahanSetengahjadiDetails::where('bahan_id', $cartItem->bahan_id)
                    ->where('serial_number', $cartItem->serial_number)
                    ->first();

                if ($bahanSetengahJadiDetail) {
                    $totalAvailable = $bahanSetengahJadiDetail->sisa;

                    // Batasi qty tidak boleh melebihi stok serial number
                    if ($requestedQty > $totalAvailable) {
                        $this->qty[$itemId] = $totalAvailable;
                        session()->flash('error', 'Jumlah melebihi stok yang tersedia untuk serial number ini.');
                    } elseif ($requestedQty < 0) {
                        $this->qty[$itemId] = null;
                    } else {
                        $this->qty[$itemId] = $requestedQty;
                    }
                }
            } else {
                // Untuk bahan non-setengah jadi, gunakan logika seperti sebelumnya
                $item = Bahan::find($cartItem->bahan_id);

                if ($item) {
                    if ($item->jenisBahan->nama === 'Produksi') {
                        $bahanSetengahjadiDetails = $item->bahanSetengahjadiDetails()
                            ->where('sisa', '>', 0)
                            ->with(['bahanSetengahjadi' => function ($query) {
                                $query->orderBy('tgl_masuk', 'asc');
                            }])->get();

                        $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
                        if ($requestedQty > $totalAvailable) {
                            $this->qty[$itemId] = $totalAvailable;
                        } elseif ($requestedQty < 0) {
                            $this->qty[$itemId] = null;
                        } else {
                            $this->qty[$itemId] = $requestedQty;
                        }
                    } elseif ($item->jenisBahan->nama !== 'Produksi') {
                        $purchaseDetails = $item->purchaseDetails()
                            ->where('sisa', '>', 0)
                            ->with(['purchase' => function ($query) {
                                $query->orderBy('tgl_masuk', 'asc');
                            }])->get();

                        $totalAvailable = $purchaseDetails->sum('sisa');
                        if ($requestedQty > $totalAvailable) {
                            $this->qty[$itemId] = $totalAvailable;
                        } elseif ($requestedQty < 0) {
                            $this->qty[$itemId] = null;
                        } else {
                            $this->qty[$itemId] = $requestedQty;
                        }
                    }
                }
            }
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
            // Tentukan apakah item merupakan bahan setengah jadi berdasarkan serial_number
            $isSetengahJadi = !empty($item->serial_number);

            $items[] = [
                'bahan_id' => $isSetengahJadi ? null : ($item->bahan_id ?? $item->id),
                'produk_id' => $isSetengahJadi ? ($item->produk_id ?? $item->id) : null,
                'serial_number' => $isSetengahJadi ? $item->serial_number : null,
                'qty' => $this->qty[$item->id] ?? 0,
                'jml_bahan' => $this->jml_bahan[$item->id] ?? 0,
                'details' => $this->details[$item->id] ?? [],
                'sub_total' => $this->subtotals[$item->id] ?? 0,
            ];
        }

        return $items;
    }


    public function render()
    {
        return view('livewire.bahan-projek-cart', [
            'cartItems' => $this->cart,
        ]);
    }
}
