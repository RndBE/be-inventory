<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use App\Models\StockOpname;
use App\Models\PurchaseDetail;
use App\Models\StockOpnameDetails;
use Illuminate\Support\Facades\Session;
use App\Models\BahanSetengahjadiDetails;

class EditBahanStockOpnameCart extends Component
{
    public $cart = [];
    public $subtotals = [];
    public $stockOpnameId;
    public $editingItemId;
    public $editingItemAuditId;
    public $qty = [];
    public $unit_price = [];
    public $unit_price_raw = [];

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
            ->with('dataBahan.dataUnit', 'dataProduk')
            ->get();

        foreach ($details as $detail) {
            // Ambil data dari bahan biasa atau bahan setengah jadi
            $bahan = $detail->dataBahan ?? $detail->dataProduk;

            if (!$bahan) {
                continue; // lewati jika kedua relasi kosong
            }

            $id = $bahan->id;
            $nama_bahan = $bahan->nama_bahan ?? $bahan->nama_produk ?? 'Unknown';
            // $kode_bahan = $bahan->kode_bahan ?? $bahan->serial_number ?? 'Unknown';

            $kode_bahan = null;

            if (isset($bahan->kode_bahan)) {
                $kode_bahan = $bahan->kode_bahan;
            } elseif (isset($bahan->serial_number)) {
                $kode_bahan = $bahan->serial_number;
            } else {
                $kode_bahan = 'Unknown';
            }

            $satuan = $bahan->dataUnit->nama ?? $bahan->satuan ?? 'Pcs';

            $this->cart[] = [
                'id' => $id,
                'bahan_id' => $detail->bahan_id ?? null,  // ini contoh asumsi, sesuaikan dengan field di model detail
                'produk_id' => $detail->produk_id ?? null,
                'nama_bahan' => $nama_bahan,
                'kode_bahan' => $kode_bahan,
                'serial_number' => $detail->serial_number ?? null,
                'satuan' => $satuan,
                'tersedia_sistem' => $detail->tersedia_sistem ?? 0,
                'tersedia_fisik' => $detail->tersedia_fisik ?? 0,
                'tersedia_fisik_audit' => $detail->tersedia_fisik_audit ?? 0,
                'selisih' => $detail->selisih ?? 0,
                'selisih_audit' => $detail->selisih_audit ?? 0,
                'keterangan' => $detail->keterangan ?? null,
            ];

            $this->tersedia_sistem[$id] = $detail->tersedia_sistem ?? 0;
            $this->tersedia_fisik[$id] = $detail->tersedia_fisik ?? 0;
            $this->tersedia_fisik_audit[$id] = $detail->tersedia_fisik_audit ?? 0;
            $this->tersedia_fisik_raw[$id] = $detail->tersedia_fisik ?? 0;
            $this->tersedia_fisik_audit_raw[$id] = $detail->tersedia_fisik_audit ?? 0;
            $this->keterangan[$id] = $detail->keterangan ?? null;
            $this->keterangan_raw[$id] = $detail->keterangan ?? null;
        }
        usort($this->cart, function($a, $b) {
            return strcmp($a['nama_bahan'], $b['nama_bahan']);
        });
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
                ->with(['bahanSetengahjadi' => function ($query) {
                    $query->orderBy('tgl_masuk', 'asc');
                }])->get();

            $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
        } else {
            // Cek di purchase details untuk bahan biasa
            $purchaseDetails = PurchaseDetail::where('bahan_id', $bahan->bahan_id)
                ->with(['purchase' => function ($query) {
                    $query->orderBy('tgl_masuk', 'asc');
                }])->get();

            $totalAvailable = $purchaseDetails->sum('sisa');
        }

        // Tambahkan item ke keranjang
        $item = [
            'id' => $itemId,
            'kode_bahan' => $item->kode_bahan ?? '',
            'bahan_id' => $bahan->bahan_id ?? null,
            'produk_id' => $bahan->produk_id ?? null,
            'serial_number' => $bahan->serial_number ?? null,
            'kode_transaksi' => $item->kode_transaksi ?? null,
            'nama_bahan' => $bahan->nama ?? 'Tanpa Nama',
            'stok' => $bahan->stok ?? 0,
            'satuan' => $bahan->unit ?? 'Pcs',
            'tersedia_sistem' => $totalAvailable,
            'tersedia_fisik' => 0,
            'tersedia_fisik_audit' => 0,
            'selisih' => 0,
            'selisih_audit' => 0,
            'keterangan' => null,
        ];
        // dd($item);
        $this->cart[] = $item;
        $this->qty[$itemId] = 1;
        $this->unit_price_raw[$itemId] = null;
        $this->unit_price[$itemId] = null;
        $this->tersedia_sistem[$itemId] = $totalAvailable;
        $this->tersedia_fisik[$itemId] = '';
        $this->keterangan[$itemId] = '';
        $this->tersedia_fisik_audit[$itemId] = '';
        $this->tersedia_fisik_raw[$itemId] = 0;
        $this->tersedia_fisik_audit_raw[$itemId] = 0;
        $this->selisih[$itemId] = 0;
        $this->selisih_audit[$itemId] = 0;

    }

    public function updateQuantity($itemId)
    {
        $requestedQty = $this->qty[$itemId] ?? 0;

        $cartItem = collect($this->cart)->firstWhere('id', $itemId);

        if ($cartItem) {
            $totalAvailable = 0;

            if (!empty($cartItem['serial_number'])) {
                $bahanSetengahJadiDetail = BahanSetengahjadiDetails::where('bahan_id', $cartItem['bahan_id'])
                    ->where('serial_number', $cartItem['serial_number'])
                    ->first();

                $totalAvailable = $bahanSetengahJadiDetail?->sisa ?? 0;
            } else {
                $item = Bahan::find($cartItem['bahan_id']);

                if ($item) {
                    if ($item->jenisBahan->nama === 'Produksi') {
                        // Ambil semua termasuk sisa 0
                        $bahanSetengahjadiDetails = $item->bahanSetengahjadiDetails()
                            ->with(['bahanSetengahjadi' => function ($query) {
                                $query->orderBy('tgl_masuk', 'asc');
                            }])->get();

                        $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
                    } else {
                        // Ambil semua termasuk sisa 0
                        $purchaseDetails = $item->purchaseDetails()
                            ->with(['purchase' => function ($query) {
                                $query->orderBy('tgl_masuk', 'asc');
                            }])->get();

                        $totalAvailable = $purchaseDetails->sum('sisa');
                    }
                }
            }

            // Tetap izinkan pengguna memasukkan qty meskipun sisa 0
            if ($requestedQty < 0) {
                $this->qty[$itemId] = null;
            } else {
                $this->qty[$itemId] = $requestedQty;
            }

            // Optional: kalau ingin memberi peringatan jika qty > sisa
            if ($requestedQty > $totalAvailable) {
                session()->flash('warning', 'Jumlah melebihi stok yang tersedia. Periksa kembali data fisik.');
            }
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

    // public function removeItem($bahanId)
    // {
    //     $this->cart = collect($this->cart)->reject(function ($item) use ($bahanId) {
    //         return isset($item['id']) && $item['id'] == $bahanId;
    //     })->toArray();

    //     session()->put('cart', $this->cart);
    // }

    public function removeItem($bahanId)
    {
        $this->cart = array_values(collect($this->cart)->reject(function ($item) use ($bahanId) {
            return isset($item['id']) && $item['id'] == $bahanId;
        })->toArray());

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

        $this->selisih_audit[$itemId] = $this->getSelisihAudit($itemId);

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
