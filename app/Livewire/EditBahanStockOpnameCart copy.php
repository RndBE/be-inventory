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
    public $editingItemAuditId;

    public $tersedia_sistem = [];
    public $tersedia_fisik = [];
    public $tersedia_fisik_audit = [];
    public $selisih = [];
    public $selisih_audit = [];
    public $tersedia_fisik_raw = [];
    public $tersedia_fisik_audit_raw = [];
    public $keterangan_raw = [];
    public $keterangan = [];
    public $status_selesai;
    public $editingItemIdket = null;

    protected $listeners = ['bahanSelected' => 'addToCart', 'bahanSetengahJadiSelected' => 'addToCart'];

    public function mount($stockOpnameId = null)
    {
        $this->stockOpnameId = $stockOpnameId;

        if ($this->stockOpnameId) {
            $this->loadCartItems();
        } else {
            $this->cart = [];
        }

        $stockOpname = StockOpname::findOrFail($stockOpnameId);
        $this->status_selesai = $stockOpname->status_selesai;
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
                'tersedia_fisik_audit' => $detail->tersedia_fisik_audit ?? 0,
                'selisih' => $detail->selisih ?? 0,
                'selisih_audit' => $detail->selisih_audit ?? 0,
                'keterangan' => $detail->keterangan ?? null,
            ];
            $this->tersedia_sistem[$detail->dataBahan->id] = $detail->tersedia_sistem ?? 0;
            $this->tersedia_fisik[$detail->dataBahan->id] = $detail->tersedia_fisik ?? 0;
            $this->tersedia_fisik_audit[$detail->dataBahan->id] = $detail->tersedia_fisik_audit ?? 0;
            $this->tersedia_fisik_raw[$detail->dataBahan->id] = $detail->tersedia_fisik ?? 0;
            $this->tersedia_fisik_audit_raw[$detail->dataBahan->id] = $detail->tersedia_fisik_audit ?? 0;
            $this->keterangan[$detail->dataBahan->id] = $detail->keterangan ?? null;
            $this->keterangan_raw[$detail->dataBahan->id] = $detail->keterangan ?? null;
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
                'tersedia_fisik_audit' => 0,
                'selisih' => 0,
                'selisih_audit' => 0,
                'keterangan' => null,
            ];

            $this->tersedia_sistem[$bahan->id] = $bahan->purchaseDetails->sum('sisa');
            $this->tersedia_fisik[$bahan->id] = 0;
            $this->tersedia_fisik_audit[$bahan->id] = 0;
            $this->tersedia_fisik_raw[$bahan->id] = 0;
            $this->tersedia_fisik_audit_raw[$bahan->id] = 0;
            $this->selisih[$bahan->id] = 0;
            $this->selisih_audit[$bahan->id] = 0;
            $this->keterangan[$bahan->id] = null;
        }
    }

    public function updateSession()
    {
        Session::put('cart', $this->cart);
    }

    public function updatedTersediaFisikRaw($value, $itemId)
    {
        $this->tersedia_fisik_raw[$itemId] = $value;
        $this->selisih[$itemId] = $this->getSelisih($itemId);
    }

    public function updatedTersediaFisikAuditRaw($value, $itemId)
    {
        $this->tersedia_fisik_audit_raw[$itemId] = $value;
        $this->selisih_audit[$itemId] = $this->getSelisihAudit($itemId);
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

    public function editItemAudit($itemId)
    {
        $this->editingItemAuditId = $itemId;
        if (isset($this->tersedia_fisik_audit[$itemId])) {
            $this->tersedia_fisik_audit_raw[$itemId] = $this->tersedia_fisik_audit[$itemId];
        } else {
            $this->tersedia_fisik_audit_raw[$itemId] = null;
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
        // Ambil inputan dari user dan bersihkan dari karakter yang tidak diperlukan
        $rawValue = $this->tersedia_fisik_raw[$itemId] ?? '0';

        // Ubah format ke angka yang bisa diproses (contoh: "2.066.698,20" -> "2066698.20")
        $cleanValue = str_replace(['.', ','], ['', '.'], $rawValue);

        // Pastikan hanya angka valid yang diproses
        $this->tersedia_fisik[$itemId] = is_numeric($cleanValue) ? floatval($cleanValue) : 0;

        // Format ulang ke format Rupiah dengan 2 desimal (ribuan pake titik, desimal pake koma)
        $this->tersedia_fisik_raw[$itemId] = number_format($this->tersedia_fisik[$itemId], 2, ',', '.');

        $this->selisih[$itemId] = $this->getSelisih($itemId);

        $existingItemKey = array_search($itemId, array_column($this->cart, 'id'));
        if ($existingItemKey !== false) {
            $this->cart[$existingItemKey]['tersedia_fisik'] = $this->tersedia_fisik[$itemId];
            $this->cart[$existingItemKey]['selisih'] = $this->selisih[$itemId];
            session()->put('cart', $this->cart);
        }

        $this->editingItemId = null;
    }


    public function formatAudit($itemId)
    {
        // Ambil inputan dari user dan bersihkan dari karakter yang tidak diperlukan
        $rawValue = $this->tersedia_fisik_audit_raw[$itemId] ?? '0';

        // Ubah format ke angka yang bisa diproses (contoh: "2.066.698,20" -> "2066698.20")
        $cleanValue = str_replace(['.', ','], ['', '.'], $rawValue);

        // Pastikan hanya angka valid yang diproses
        $this->tersedia_fisik_audit[$itemId] = is_numeric($cleanValue) ? floatval($cleanValue) : 0;

        // Format ulang ke format Rupiah dengan 2 desimal (ribuan pake titik, desimal pake koma)
        $this->tersedia_fisik_audit_raw[$itemId] = number_format($this->tersedia_fisik_audit[$itemId], 2, ',', '.');

        $this->selisih[$itemId] = $this->getSelisihAudit($itemId);

        $existingItemKey = array_search($itemId, array_column($this->cart, 'id'));
        if ($existingItemKey !== false) {
            $this->cart[$existingItemKey]['tersedia_fisik_audit'] = $this->tersedia_fisik_audit[$itemId];
            $this->cart[$existingItemKey]['selisih_audit'] = $this->selisih_audit[$itemId];
            session()->put('cart', $this->cart);
        }

        $this->editingItemAuditId = null;
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
        $rawValue = $this->tersedia_fisik_raw[$itemId] ?? '0';
        $rawValue = str_replace(',', '.', $rawValue);
        $rawValueFloat = (float) $rawValue;

        $tersediaSistem = isset($this->tersedia_sistem[$itemId]) ? (float) $this->tersedia_sistem[$itemId] : 0;

        $selisih = $rawValueFloat - $tersediaSistem;

        return $selisih;
    }



    public function getSelisihAudit($itemId)
    {
        $rawValue = $this->tersedia_fisik_audit_raw[$itemId] ?? '0';
        $rawValue = str_replace(',', '.', $rawValue);
        $rawValueFloat = (float) $rawValue;

        $tersediaSistem = isset($this->tersedia_sistem[$itemId]) ? (float) $this->tersedia_sistem[$itemId] : 0;

        $selisih = $rawValueFloat - $tersediaSistem;

        return $selisih;
    }


    public function render()
    {
        return view('livewire.edit-bahan-stock-opname-cart', [
            'cartItems' => $this->cart,
        ]);
    }
}
