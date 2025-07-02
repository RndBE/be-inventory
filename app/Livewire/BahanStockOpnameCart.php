<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use App\Models\PurchaseDetail;
use Illuminate\Support\Facades\Session;
use App\Models\BahanSetengahjadiDetails;

class BahanStockOpnameCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $unit_price = [];
    public $unit_price_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = null;

    public $tersedia_sistem = []; // Holds the system stock values
    public $tersedia_fisik = [];  // Holds the physical stock values
    public $selisih = [];         // Holds the difference values
    public $tersedia_fisik_raw = [];
    public $keterangan = [];
    protected $listeners = ['bahanSelected' => 'addToCart', 'bahanSetengahJadiSelected' => 'addToCart'];

    public function mount()
    {
        $this->qty = Session::get('qty', []);
        $this->subtotals = Session::get('subtotals', []);
        $this->totalharga = array_sum($this->subtotals);
        $this->tersedia_sistem = [];
        $this->tersedia_fisik = [];
        $this->selisih = [];
        $this->keterangan = [];

        foreach ($this->cart as $item) {
            $this->keterangan[$item->id] = $this->keterangan[$item->id] ?? '';  // Default ke string kosong jika belum ada
        }
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
            'kode_bahan' => $item->kode_bahan ?? '',
            'bahan_id' => $bahan->bahan_id ?? null,
            'produk_id' => $bahan->produk_id ?? null,
            'serial_number' => $bahan->serial_number ?? null,
            'kode_transaksi' => $item->kode_transaksi ?? null,
            'nama_bahan' => $bahan->nama ?? 'Tanpa Nama',
            'stok' => $bahan->stok ?? 0,
            'unit' => $bahan->unit ?? 'Pcs',
        ];
        $this->cart[] = $item;
        $this->qty[$itemId] = 1;
        $this->unit_price_raw[$itemId] = null;
        $this->unit_price[$itemId] = null;
        $this->tersedia_sistem[$itemId] = $totalAvailable;
        $this->tersedia_fisik[$itemId] = 0;
        $this->keterangan[$itemId] = '';

        $this->saveCartToSession();
        $this->calculateSubTotal($itemId);
    }

    protected function saveCartToSession()
    {
        session()->put('cartItems', $this->getCartItemsForStorage());
    }

    public function updateSession()
    {
        Session::put('cart', $this->cart);
        Session::put('qty', $this->qty);
        Session::put('subtotals', $this->subtotals);
        Session::put('totalharga', $this->totalharga);
        Session::put('keterangan', $this->keterangan);
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
        $unitPrice = isset($this->unit_price[$itemId]) ? intval($this->unit_price[$itemId]) : 0;
        $qty = isset($this->qty[$itemId]) ? intval($this->qty[$itemId]) : 0;

        $this->subtotals[$itemId] = $unitPrice * $qty;
        $this->calculateTotalHarga();
    }

    public function calculateTotalHarga()
    {
        $this->totalharga = array_sum($this->subtotals);
    }

    public function format($itemId)
    {
        $this->tersedia_fisik[$itemId] = intval(str_replace(['.', ' '], '', $this->tersedia_fisik_raw[$itemId]));
        $this->tersedia_fisik_raw[$itemId] = $this->tersedia_fisik[$itemId];
        $this->calculateSubTotal($itemId);
        $this->editingItemId = null;
    }

    public function updatedTersediaFisikRaw($value, $itemId)
    {
        // Parse the raw stock value (remove formatting if necessary)
        $value = intval(str_replace(['.', ' '], '', $value));

        // Update the physical stock value
        $this->tersedia_fisik_raw[$itemId] = $value;

        // Calculate and update selisih
        $this->selisih[$itemId] = $this->getSelisih($itemId);
    }

    public function editItem($itemId)
    {
        $this->editingItemId = $itemId;
        if (isset($this->unit_price[$itemId])) {
            $this->tersedia_fisik_raw[$itemId] = $this->tersedia_fisik[$itemId];
        } else {
            $this->tersedia_fisik_raw[$itemId] = null;
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
        $this->calculateTotalHarga();
        $this->updateSession();
    }

    public function getSelisih($itemId)
    {
        // Ensure both the system stock and physical stock values are set
        $tersediaSistem = isset($this->tersedia_sistem[$itemId]) ? $this->tersedia_sistem[$itemId] : 0;
        $tersediaFisikRaw = isset($this->tersedia_fisik_raw[$itemId]) ? intval(str_replace(['.', ' '], '', $this->tersedia_fisik_raw[$itemId])) : 0;

        // Calculate and return the difference
        return  $tersediaFisikRaw - $tersediaSistem;
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

    public function getCartItemsForStorage()
    {
        $items = [];

        foreach ($this->cart as $item) {
            $isSetengahJadi = !empty($item->serial_number);

            $items[] = [
                'bahan_id' => $isSetengahJadi ? null : ($item->bahan_id ?? $item->id),
                'produk_id' => $isSetengahJadi ? ($item->produk_id ?? $item->id) : null,
                'serial_number' => $isSetengahJadi ? $item->serial_number : null,
                'tersedia_sistem' => isset($this->tersedia_sistem[$item->id]) ? $this->tersedia_sistem[$item->id] : 0,
                'tersedia_fisik' => isset($this->tersedia_fisik_raw[$item->id]) ? $this->tersedia_fisik_raw[$item->id] : 0,
                'selisih' => $this->getSelisih($item->id),
                'keterangan' => isset($this->keterangan[$item->id]) ? $this->keterangan[$item->id] : '',
            ];
        }

        return $items;
    }


    public function render()
    {
        return view('livewire.bahan-stock-opname-cart', [
            'cartItems' => $this->cart,
        ]);
    }
}
