<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use Illuminate\Support\Facades\Session;

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
    protected $listeners = ['bahanSelected' => 'addToCart'];

    public function mount()
    {
        $this->qty = Session::get('qty', []);
        $this->subtotals = Session::get('subtotals', []);
        $this->totalharga = array_sum($this->subtotals);
        $this->tersedia_sistem = []; // Initialize system stock
        $this->tersedia_fisik = [];  // Initialize physical stock
        $this->selisih = [];         // Initialize selisih
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
        $bahan = Bahan::with('dataUnit')->find($bahan->id);
        $existingItemKey = array_search($bahan->id, array_column($this->cart, 'id'));
        if ($existingItemKey !== false) {
            $this->qty[$bahan->id]++;
        } else {
            $this->cart[] = $bahan;
            $this->qty[$bahan->id] = null;
            $this->unit_price_raw[$bahan->id] = null;
            $this->unit_price[$bahan->id] = null;
            $this->tersedia_sistem[$bahan->id] = $bahan->purchaseDetails->sum('sisa'); // Initialize system stock from purchase details
            $this->tersedia_fisik[$bahan->id] = 0; // Default physical stock to 0 initially
            $this->keterangan[$bahan->id] = '';
        }
        $this->calculateSubTotal($bahan->id);

        $this->updateSession();
    }

    public function updateSession()
    {
        Session::put('cart', $this->cart);
        Session::put('qty', $this->qty);
        Session::put('subtotals', $this->subtotals);
        Session::put('totalharga', $this->totalharga);
        Session::put('keterangan', $this->keterangan);
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

    // public function updateQuantity($itemId)
    // {
    //     if (isset($this->qty[$itemId])) {
    //         $this->qty[$itemId] = max(0, intval($this->qty[$itemId]));
    //         $this->calculateSubTotal($itemId);
    //     }
    // }

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
        $requestedQty = $this->jml_bahan[$itemId] ?? 0;
        $item = Bahan::find($itemId);
    }


    public function getCartItemsForStorage()
    {
        $items = [];
        foreach ($this->cart as $item) {
            $itemId = $item->id; // Store the item ID for reuse
            $items[] = [
                'id' => $itemId,
                'tersedia_sistem' => isset($this->tersedia_sistem[$itemId]) ? $this->tersedia_sistem[$itemId] : 0,
                'tersedia_fisik' => isset($this->tersedia_fisik_raw[$itemId]) ? $this->tersedia_fisik_raw[$itemId] : 0,
                'selisih' => $this->getSelisih($itemId),
                'keterangan' => isset($this->keterangan[$itemId]) ? $this->keterangan[$itemId] : 0,
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
