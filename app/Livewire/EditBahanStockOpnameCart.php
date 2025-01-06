<?php

namespace App\Livewire;

use App\Models\Bahan;
use App\Models\StockOpname;
use App\Models\StockOpnameDetails;
use Livewire\Component;
use Illuminate\Support\Facades\Session;

class EditBahanStockOpnameCart extends Component
{
    public $cart = [];
    public $subtotals = [];
    public $stockOpnameId;
    public $editingItemId;
    public $editingItemIdket;

    public $tersedia_sistem = [];
    public $tersedia_fisik = [];
    public $selisih = [];
    public $tersedia_fisik_raw = [];
    public $keterangan_raw = [];
    public $keterangan = [];
    public $status_direktur;

    protected $listeners = ['bahanSelected' => 'addToCart'];

    public function mount($stockOpnameId = null)
    {
        $this->stockOpnameId = $stockOpnameId;

        if ($this->stockOpnameId) {
            $this->loadCartItems();
        } else {
            $this->cart = [];
        }

        $stockOpname = StockOpname::findOrFail($stockOpnameId);
        $this->status_direktur = $stockOpname->status_direktur;
    }

    public function loadCartItems()
    {
        $details = StockOpnameDetails::where('stock_opname_id', $this->stockOpnameId)
            ->with('dataBahan.dataUnit') // Ensure dataUnit is loaded
            ->get();

        foreach ($details as $detail) {
            $this->cart[] = [
                'id' => $detail->dataBahan->id,
                'nama_bahan' => $detail->dataBahan->nama_bahan,
                'kode_bahan' => $detail->dataBahan->kode_bahan,
                // Use default value if dataUnit is missing
                'satuan' => $detail->dataBahan->dataUnit->nama ?? 'Unknown',
                'tersedia_sistem' => $detail->tersedia_sistem ?? 0,
                'tersedia_fisik' => $detail->tersedia_fisik ?? 0,
                'selisih' => $detail->selisih ?? 0,
                'keterangan' => $detail->keterangan ?? 0,
            ];
            $this->tersedia_sistem[$detail->dataBahan->id] = $detail->tersedia_sistem ?? 0;
            $this->tersedia_fisik[$detail->dataBahan->id] = $detail->tersedia_fisik ?? 0;
            $this->tersedia_fisik_raw[$detail->dataBahan->id] = $detail->tersedia_fisik ?? 0;
            $this->keterangan[$detail->dataBahan->id] = $detail->keterangan ?? 0;
            $this->keterangan_raw[$detail->dataBahan->id] = $detail->keterangan ?? 0;
        }
    }

    public function addToCart($bahan)
    {
        if (is_array($bahan)) {
            $bahan = (object) $bahan;
        }

        $bahan = Bahan::with('dataUnit')->find($bahan->id);
        $existingItemKey = array_search($bahan->id, array_column($this->cart, 'id'));

        if ($existingItemKey === false) {
            $this->cart[] = [
                'id' => $bahan->id,
                'nama_bahan' => $bahan->nama_bahan,
                'kode_bahan' => $bahan->kode_bahan,
                'satuan' => $bahan->dataUnit->nama ?? 'Unknown',
                'tersedia_sistem' => $bahan->purchaseDetails->sum('sisa'),
                'tersedia_fisik' => 0,
                'selisih' => 0,
                'keterangan' => 0,
            ];

            $this->tersedia_sistem[$bahan->id] = $bahan->purchaseDetails->sum('sisa');
            $this->tersedia_fisik[$bahan->id] = 0;
            $this->tersedia_fisik_raw[$bahan->id] = 0;
            $this->selisih[$bahan->id] = 0;
            $this->keterangan[$bahan->id] = 0;
        }
    }

    public function updateSession()
    {
        Session::put('cart', $this->cart);
    }

    public function updatedTersediaFisikRaw($value, $itemId)
    {
        $value = intval(str_replace(['.', ' '], '', $value));
        $this->tersedia_fisik_raw[$itemId] = $value;
        $this->selisih[$itemId] = $this->getSelisih($itemId);
    }


    public function editItem($itemId)
    {
        $this->editingItemId = $itemId;
        if (isset($this->tersedia_fisik[$itemId])) {
            $this->tersedia_fisik_raw[$itemId] = $this->tersedia_fisik[$itemId];
        } else {
            $this->tersedia_fisik_raw[$itemId] = null;
        }
    }
    public function editItemKet($itemId)
    {
        $this->editingItemIdket = $itemId;
        if (isset($this->keterangan[$itemId])) {
            $this->keterangan_raw[$itemId] = $this->keterangan[$itemId];
        } else {
            $this->keterangan_raw[$itemId] = null;
        }
    }

    public function removeItem($bahanId)
    {
        $this->cart = collect($this->cart)->reject(function ($item) use ($bahanId) {
            return isset($item['id']) && $item['id'] == $bahanId;
        })->toArray();

        session()->put('cart', $this->cart);
    }

    public function format($itemId)
    {
        // Update physical stock from raw input
        $this->tersedia_fisik[$itemId] = intval(str_replace(['.', ' '], '', $this->tersedia_fisik_raw[$itemId]));
        $this->tersedia_fisik_raw[$itemId] = $this->tersedia_fisik[$itemId]; // Reflect the value back

        $this->selisih[$itemId] = $this->getSelisih($itemId);
        $existingItemKey = array_search($itemId, array_column($this->cart, 'id'));
        if ($existingItemKey !== false) {
            $this->cart[$existingItemKey]['tersedia_fisik'] = $this->tersedia_fisik[$itemId];
            $this->cart[$existingItemKey]['selisih'] = $this->selisih[$itemId];
            session()->put('cart', $this->cart);
        }

        $this->editingItemId = null;
    }

    public function formatKet($itemId)
    {
        $this->keterangan[$itemId] = $this->keterangan_raw[$itemId];
        $this->keterangan_raw[$itemId] = $this->keterangan[$itemId];
        $existingItemKey = array_search($itemId, array_column($this->cart, 'id'));
        if ($existingItemKey !== false) {
            $this->cart[$existingItemKey]['keterangan'] = $this->keterangan[$itemId];

            session()->put('cart', $this->cart);
        }

        $this->editingItemIdket = null;
    }

    public function getSelisih($itemId)
    {
        $tersediaSistem = isset($this->tersedia_sistem[$itemId]) ? $this->tersedia_sistem[$itemId] : 0;
        $tersediaFisikRaw = isset($this->tersedia_fisik_raw[$itemId]) ? intval(str_replace(['.', ' '], '', $this->tersedia_fisik_raw[$itemId])) : 0;

        // Calculate selisih as the difference between system stock and physical stock
        return $tersediaFisikRaw - $tersediaSistem;
    }


    public function render()
    {
        return view('livewire.edit-bahan-stock-opname-cart', [
            'cartItems' => $this->cart,
        ]);
    }
}
