<?php

namespace App\Livewire;

use App\Helpers\SatuanBahanHelper;
use App\Livewire\Concerns\MemilihSatuanBahan;
use App\Livewire\Concerns\MemilihSatuanReturRusak;
use App\Models\Bahan;
use App\Models\Projek;
use Livewire\Component;
use App\Models\Produksi;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use App\Models\ProjekDetails;
use App\Models\PurchaseDetail;
use App\Models\ProdukJadiDetails;
use App\Models\BahanSetengahjadiDetails;

class EditKomponenProjekCart extends Component
{
    use MemilihSatuanReturRusak;

    // Nama asli dari trait disimpan supaya versi di kelas ini bisa memakainya
    // sebagai cadangan, setelah peta panjang standar diperiksa lebih dulu.
    use MemilihSatuanBahan {
        panjangStandarUntuk as panjangStandarDariKeranjang;
    }

    /**
     * Panjang standar per `cart_key`, termasuk baris yang sudah tersimpan.
     *
     * Trait MemilihSatuanBahan membaca panjang standar dari `$cart`, sedangkan
     * di halaman edit `$cart` hanya berisi baris yang baru ditambahkan pada
     * sesi ini — baris lama datang dari `$projekDetails`. Peta ini menutup
     * selisih itu supaya baris lama juga dapat pilihan satuan.
     */
    public $panjangStandarItem = [];

    public $cart = [];
    public $qty = [];
    public $jml_bahan = [];
    public $details = [];
    public $details_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;
    public $projekId;
    public $projekDetails = [];
    public $bahanRusak = [];
    public $bahanRetur = [];
    public $isFirstTimePengajuan = [];
    public $isBahanReturPending = [];
    public $pendingReturCount = [];
    public $isBahanRusakPending = [];
    public $pendingRusakCount = [];
    public $produksiStatus;

    protected $listeners = [
        'bahanSelected' => 'addToCart',
        'bahanSetengahJadiSelected' => 'addToCart',
        'produkJadiSelected' => 'addToCart'
    ];

    public $bahanKeluars = [];

    public function mount($projekId)
    {
        $this->projekId = $projekId;
        $this->loadProduksi();
        $this->loadBahanKeluar();

        foreach ($this->projekDetails as $detail) {
            // Simpan bahan_id jika tersedia
            if (!empty($detail['bahan_id'])) {
                $this->jml_bahan[$detail['bahan_id']] = $detail['jml_bahan'] ?? 0;
            }

            // Simpan produk_id jika tersedia
            if (!empty($detail['produk_id'])) {
                $this->jml_bahan[$detail['produk_id']] = $detail['jml_bahan'] ?? 0;
            }
        }
    }

    public function loadProduksi()
    {
        $produksi = Projek::with('projekDetails')->find($this->projekId);

        if ($produksi) {
            $this->produksiStatus = $produksi->status;

            foreach ($produksi->projekDetails as $detail) {

                // Tentukan cart_key unik
                if (!empty($detail->produk_jadis_id)) {
                    $cartKey = "jadi-{$detail->produk_jadis_id}-" . ($detail->serial_number ?? uniqid());
                } elseif (!empty($detail->produk_id)) {
                    $cartKey = "setengahjadi-{$detail->produk_id}-" . ($detail->serial_number ?? uniqid());
                } else {
                    $cartKey = "bahan-{$detail->bahan_id}";
                }

                $this->projekDetails[] = [
                    'cart_key'     => $cartKey,
                    'bahan_id'     => $detail->bahan_id ?? null,
                    'produk_id'    => $detail->produk_id ?? null,
                    'produk_jadis_id' => $detail->produk_jadis_id ?? null,
                    'jml_bahan'    => $detail->jml_bahan,
                    'used_materials' => $detail->used_materials ?? 0,
                    'sub_total'    => $detail->sub_total,
                    'serial_number'=> $detail->serial_number ?? null,
                    'details'      => json_decode($detail->details, true),
                    'newly_added'  => false,
                ];
            }

            $this->muatPanjangStandar();
        }
    }

    /**
     * Panjang standar item: peta dulu, keranjang sebagai cadangan.
     */
    public function panjangStandarUntuk($itemId): ?int
    {
        if (array_key_exists($itemId, $this->panjangStandarItem)) {
            return SatuanBahanHelper::panjangStandar($this->panjangStandarItem[$itemId]);
        }

        return $this->panjangStandarDariKeranjang($itemId);
    }

    /**
     * Isi peta panjang standar untuk semua baris bahan yang sudah tersimpan.
     *
     * Petanya dikunci `cart_key`, bukan id bahan, karena satu bahan bisa muncul
     * beberapa baris dengan serial number berbeda dan `$qty` juga dikunci
     * begitu. Baris produk setengah jadi dan produk jadi dihitung per unit,
     * jadi tidak ikut dipetakan.
     */
    protected function muatPanjangStandar(): void
    {
        $bahanIds = collect($this->projekDetails)
            ->pluck('bahan_id')
            ->filter()
            ->unique();

        if ($bahanIds->isEmpty()) {
            return;
        }

        $panjangPerBahan = Bahan::whereIn('id', $bahanIds)->pluck('panjang_standar', 'id');

        foreach ($this->projekDetails as $detail) {
            $cartKey = $detail['cart_key'] ?? null;
            $bahanId = $detail['bahan_id'] ?? null;

            if ($cartKey === null || $bahanId === null) {
                continue;
            }

            $panjangStandar = SatuanBahanHelper::panjangStandar($panjangPerBahan[$bahanId] ?? null);
            $this->panjangStandarItem[$cartKey] = $panjangStandar;

            if (! isset($this->satuan[$cartKey])) {
                $this->setelSatuanAwal($cartKey, $panjangStandar);
            }
        }
    }

    public function loadBahanKeluar()
    {
        $existingBahanKeluar = BahanKeluar::where('projek_id', $this->projekId)->exists();
        $this->isFirstTimePengajuan = !$existingBahanKeluar;

        $this->bahanKeluars = BahanKeluar::with(['bahanKeluarDetails.dataBahan','bahanKeluarDetails.dataProdukJadi'])
            ->where('status', 'Belum disetujui')
            ->where('projek_id', $this->projekId)
            ->get();

        $this->pendingReturCount = BahanRetur::where('projek_id', $this->projekId)
            ->where('status', 'Belum disetujui')
            ->count();

        $this->pendingRusakCount = BahanRusak::where('projek_id', $this->projekId)
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

        // Tentukan tipe item & buat cart_key unik
        if (!empty($bahan->produk_jadis_id)) {
            // Produk jadi → unik berdasarkan produk_jadis_id + serial
            $cartKey = "jadi-{$bahan->produk_jadis_id}-" . ($bahan->serial_number ?? uniqid());
        } elseif (!empty($bahan->produk_id)) {
            // Produk setengah jadi
            $cartKey = "setengahjadi-{$bahan->produk_id}-" . ($bahan->serial_number ?? uniqid());
        } else {
            // Bahan biasa
            $cartKey = "bahan-{$bahan->bahan_id}";
        }

        // Cek apakah item sudah ada di cart
        $itemExists = collect($this->projekDetails)->first(function ($item) use ($cartKey) {
            return ($item['cart_key'] ?? null) === $cartKey;
        });

        if ($itemExists) {
            session()->flash('error', 'Bahan atau produk sudah ada di keranjang.');
            return;
        }

        // Periksa ketersediaan stok
        $totalAvailable = 0;
        if (!empty($bahan->produk_jadis_id)) {
            // Produk jadi biasanya stok = 1 (serial number unik)
            $totalAvailable = $bahan->stok ?? 1;
        } elseif (!empty($bahan->produk_id)) {
            // Produk setengah jadi
            $bahanSetengahjadiDetails = BahanSetengahjadiDetails::where('id', $bahan->produk_id)
                ->where('sisa', '>', 0)
                ->with(['bahanSetengahjadi' => function ($query) {
                    $query->orderBy('tgl_masuk', 'asc');
                }])->get();
            $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
        } else {
            // Bahan biasa
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

        // Hanya bahan biasa yang bisa batangan; setengah jadi dan produk jadi
        // dihitung per unit sehingga panjang standarnya null. Nilainya
        // dilekatkan di item keranjang supaya baris tabel bisa memutuskan perlu
        // tidaknya pilihan satuan tanpa query ulang tiap render.
        $panjangStandar = empty($bahan->produk_id) && empty($bahan->produk_jadis_id)
            ? SatuanBahanHelper::panjangStandar(Bahan::find($bahan->bahan_id ?? null))
            : null;
        $this->panjangStandarItem[$cartKey] = $panjangStandar;

        // Tambahkan ke cart
        $this->cart[] = (object)[
            'cart_key' => $cartKey,
            'bahan_id' => $bahan->bahan_id ?? null,
            'produk_id' => $bahan->produk_id ?? null,
            'produk_jadis_id' => $bahan->produk_jadis_id ?? null,
            'serial_number' => $bahan->serial_number ?? null,
            'nama_bahan' => $bahan->nama,
            'stok' => $bahan->stok,
            'unit' => $bahan->unit,
            'panjang_standar' => $panjangStandar,
            'newly_added' => true
        ];

        $this->qty[$cartKey] = null;
        $this->setelSatuanAwal($cartKey, $panjangStandar);
        $this->subtotals[$cartKey] = property_exists($bahan, 'unit_price') ? $bahan->unit_price : 0;

        // Tambahkan juga ke projekDetails
        $this->projekDetails[] = [
            'cart_key' => $cartKey,
            'bahan_id' => $bahan->bahan_id ?? null,
            'produk_id' => $bahan->produk_id ?? null,
            'produk_jadis_id' => $bahan->produk_jadis_id ?? null,
            'serial_number' => $bahan->serial_number ?? null,
            'qty' => null,
            'jml_bahan' => 0,
            'sub_total' => 0,
            'details' => [],
            'newly_added' => true,
        ];

        // Update total harga
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
        // Harga ledger untuk bahan batangan adalah harga per cm, jadi yang
        // dikalikan harus angka satuan dasar, bukan jumlah batang yang diketik.
        $qty = $this->qtyDasar($itemId);
        $this->subtotals[$itemId] = $unitPrice * $qty;
        $this->calculateTotalHarga();
    }

    public function calculateTotalHarga()
    {
        $this->totalharga = array_sum($this->subtotals);
    }

    public function updateQuantity($type, $cartKey)
    {
        $requestedQty = $this->qty[$cartKey] ?? 0;

        // cari detail berdasarkan cart_key
        $detail = collect($this->projekDetails)->firstWhere('cart_key', $cartKey);
        if (!$detail) {
            return;
        }

        $itemId = $detail['bahan_id'] ?? $detail['produk_id'] ?? $detail['produk_jadis_id'];

        // dd($detail);

        if ($type === 'produk') {
            $bahanSetengahjadiDetails = BahanSetengahjadiDetails::where('id', $itemId)
                ->where('sisa', '>', 0)
                ->with(['bahanSetengahjadi' => function ($query) {
                    $query->orderBy('tgl_masuk', 'asc');
                }])->get();
            $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
        } elseif ($type === 'produk_jadi') {
            $produkJadiDetails = ProdukJadiDetails::where('id', $itemId)
                ->where('sisa', '>', 0)
                ->with(['ProdukJadis' => function ($query) {
                    $query->orderBy('tgl_masuk', 'asc');
                }])->get();
            $totalAvailable = $produkJadiDetails->sum('sisa');
        } else {
            $purchaseDetails = PurchaseDetail::where('bahan_id', $itemId)
                ->where('sisa', '>', 0)
                ->with(['purchase' => function ($query) {
                    $query->orderBy('tgl_masuk', 'asc');
                }])->get();
            $totalAvailable = $purchaseDetails->sum('sisa');
        }

        // Batasi qty sesuai stok. Perbandingannya di satuan dasar: sisa stok
        // bahan batangan tersimpan dalam cm, sedangkan yang diketik bisa jadi
        // jumlah batang. `$this->qty` tetap menyimpan angka apa adanya.
        $this->qty[$cartKey] = $this->batasiQtyInput($cartKey, $requestedQty, $totalAvailable);
    }


    public function formatToRupiah($itemKey)
    {
        $this->details[$itemKey] = intval(str_replace(['.', ' '], '', $this->details_raw[$itemKey]));
        $this->details_raw[$itemKey] = $this->details[$itemKey];
        $this->calculateSubTotal($itemKey);
        $this->editingItemId = null;
    }

    public function saveUnitPrice($itemId)
    {
        $this->formatToRupiah($itemId);
    }

    public function removeItem($cartKey)
    {
        // Hapus dari cart
        $this->cart = array_filter($this->cart, function ($item) use ($cartKey) {
            return ($item->cart_key ?? null) !== $cartKey;
        });
        // dd($cartKey);

        // Hapus dari projekDetails
        $this->projekDetails = array_filter($this->projekDetails, function ($detail) use ($cartKey) {
            return ($detail['cart_key'] ?? null) !== $cartKey;
        });

        // Reset array index
        $this->cart = array_values($this->cart);
        $this->projekDetails = array_values($this->projekDetails);

        session()->flash('message', 'Item berhasil dihapus.');
    }

    public function decreaseQuantityPerPrice($cartKey, $unitPrice)
    {
        foreach ($this->projekDetails as &$detail) {
            if (($detail['cart_key'] ?? null) !== $cartKey) {
                continue;
            }

            // Cek apakah sudah ada di retur
            foreach ($this->bahanRetur as $retur) {
                if (($retur['cart_key'] ?? null) === $cartKey) {
                    session()->flash('error', 'Item ini sudah masuk daftar retur dan tidak bisa ditandai rusak.');
                    return;
                }
            }

            // Cek apakah sudah ada di rusak
            foreach ($this->bahanRusak as $rusak) {
                if (($rusak['cart_key'] ?? null) === $cartKey) {
                    return; // sudah ada, jangan double
                }
            }

            // Tambahkan
            $this->bahanRusak[] = [
                'cart_key'      => $cartKey,
                'bahan_id'      => $detail['bahan_id'] ?? null,
                'produk_id'     => $detail['produk_id'] ?? null,
                'produk_jadis_id'=> $detail['produk_jadis_id'] ?? null,
                'unit_price'    => $unitPrice,
                'serial_number' => $detail['serial_number'] ?? null,
            
                // cm sebagai default: yang dikembalikan dari proyek umumnya
                // potongan sisa, bukan batang utuh.
                'satuan' => $this->satuanAwalBaris(null),
            ];

            break;
        }
    }

    public function returQuantityPerPrice($cartKey, $unitPrice)
    {
        foreach ($this->projekDetails as &$detail) {
            if (($detail['cart_key'] ?? null) !== $cartKey) {
                continue;
            }

            // Cek apakah sudah ada di rusak
            foreach ($this->bahanRusak as $rusak) {
                if (($rusak['cart_key'] ?? null) === $cartKey) {
                    session()->flash('error', 'Item ini sudah ditandai rusak dan tidak bisa diretur.');
                    return;
                }
            }

            // Cek apakah sudah ada di retur
            foreach ($this->bahanRetur as $retur) {
                if (($retur['cart_key'] ?? null) === $cartKey) {
                    return; // sudah ada
                }
            }

            // Tambahkan
            $this->bahanRetur[] = [
                'cart_key'      => $cartKey,
                'bahan_id'      => $detail['bahan_id'] ?? null,
                'produk_id'     => $detail['produk_id'] ?? null,
                'produk_jadis_id'=> $detail['produk_jadis_id'] ?? null,
                'unit_price'    => $unitPrice,
                'serial_number' => $detail['serial_number'] ?? null,
            
                // cm sebagai default: yang dikembalikan dari proyek umumnya
                // potongan sisa, bukan batang utuh.
                'satuan' => $this->satuanAwalBaris(null),
            ];

            break;
        }
    }


    public function returnToProduction($type, $itemId, $unitPrice)
    {
        // dd($itemId);
        foreach ($this->bahanRusak as $key => $rusak) {
            $isMatch = false;

            if ($type === 'bahan' && isset($rusak['bahan_id']) && $rusak['bahan_id'] === $itemId) {
                $isMatch = true;
            } elseif ($type === 'produk' && isset($rusak['produk_id']) && $rusak['produk_id'] === $itemId) {
                $isMatch = true;
            }elseif ($type === 'produk_jadi' && isset($rusak['produk_jadis_id']) && $rusak['produk_jadis_id'] === $itemId) {
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
            }elseif ($type === 'produk_jadi' && isset($retur['produk_jadis_id']) && $retur['produk_jadis_id'] === $itemId) {
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
        $projekDetails = [];

        foreach ($this->projekDetails as $item) {
            $cartKey = $item['cart_key'] ?? null;
            if (!$cartKey) {
                continue;
            }

            $bahanId     = $item['bahan_id'] ?? null;
            $produkId    = $item['produk_id'] ?? null;
            $produkJadiId = $item['produk_jadis_id'] ?? null;

            // Angka yang dipotong dari stok selalu satuan dasar, sedangkan
            // `$this->qty` menyimpan angka apa adanya yang diketik user.
            $usedMaterials = $this->qtyDasar($cartKey);
            if ($usedMaterials <= 0) {
                continue;
            }

            // Hitung subtotal (kalau details ada isinya pakai itu, fallback ke qty * harga default)
            $totalPrice = 0;
            $details = [];

            $projekDetails[] = [
                'cart_key'       => $cartKey,
                'bahan_id'       => $bahanId,
                'produk_id'      => $produkId,
                'produk_jadis_id'=> $produkJadiId,
                'qty'            => $usedMaterials,
                // Jejak satuan input untuk riwayat dan cetakan. Tidak ada
                // perhitungan stok yang boleh mengambil angka dari sini.
                'qty_input'      => $this->qty[$cartKey] ?? 0,
                'satuan_input'   => $this->panjangStandarUntuk($cartKey) ? $this->satuanUntuk($cartKey) : null,
                'jml_bahan'      => $this->jml_bahan[$cartKey] ?? 0,
                'details'        => $details,
                'serial_number'  => $item['serial_number'] ?? null,
                'sub_total'      => $totalPrice,
                'newly_added'    => $item['newly_added'] ?? false,
            ];
        }

        return $projekDetails;
    }




    public function getCartItemsForBahanRusak()
    {
        $bahanRusak = [];

        foreach ($this->bahanRusak as $index => $rusak) {
            // Ambil ID berdasarkan apakah itu bahan atau produk
            $bahanId = $rusak['bahan_id'] ?? null;
            $produkId = $rusak['produk_id'] ?? null;
            $produkJadiId = $rusak['produk_jadis_id'] ?? null;

            // Jika keduanya null, lewati iterasi ini
            if ($bahanId === null && $produkId === null && $produkJadiId === null) {
                continue;
            }

            $bahanRusak[] = [
                'bahan_id' => $bahanId,
                'produk_id' => $produkId,
                'produk_jadis_id' => $produkJadiId,
                'serial_number' => $rusak['serial_number'] ?? null,
                // `qty` dalam satuan dasar karena `unit_price` baris ini
                // harga per satuan dasar. Angka apa adanya yang diketik ikut
                // dikirim sebagai jejak, bukan untuk perhitungan.
                'qty' => $this->qtyDasarBarisRusak($index),
                'qty_input' => $rusak['qty'] ?? 0,
                'satuan_input' => $this->satuanTersimpanRusak($index),
                'unit_price' => $rusak['unit_price'] ?? 0,
                'sub_total' => $this->qtyDasarBarisRusak($index) * floatval(str_replace(',', '.', $rusak['unit_price'] ?? 0)),
            ];
        }

        return $bahanRusak;
    }

    public function getCartItemsForBahanRetur()
    {
        $bahanRetur = [];

        foreach ($this->bahanRetur as $index => $retur) {
            // Ambil ID berdasarkan apakah itu bahan atau produk
            $bahanId = $retur['bahan_id'] ?? null;
            $produkId = $retur['produk_id'] ?? null;
            $produkJadiId = $retur['produk_jadis_id'] ?? null;

            // Jika keduanya null, lewati iterasi ini
            if ($bahanId === null && $produkId === null && $produkJadiId === null) {
                continue;
            }

            $bahanRetur[] = [
                'bahan_id' => $bahanId,
                'produk_id' => $produkId,
                'produk_jadis_id' => $produkJadiId,
                'serial_number' => $retur['serial_number'] ?? null,
                // `qty` dalam satuan dasar karena `unit_price` baris ini
                // harga per satuan dasar. Angka apa adanya yang diketik ikut
                // dikirim sebagai jejak, bukan untuk perhitungan.
                'qty' => $this->qtyDasarBarisRetur($index),
                'qty_input' => $retur['qty'] ?? 0,
                'satuan_input' => $this->satuanTersimpanRetur($index),
                'unit_price' => $retur['unit_price'] ?? 0,
                // 'sub_total' => $this->qtyDasarBarisRetur($index) * ($retur['unit_price'] ?? 0),
                'sub_total' => $this->qtyDasarBarisRetur($index) * floatval(str_replace(',', '.', $retur['unit_price'] ?? 0)),
            ];
        }

        return $bahanRetur;
    }

    public function updateRusakQty($id, $unitPrice, $newQty)
    {
        $parsedQty = floatval(str_replace(',', '.', $newQty));
        $maxQty = 0;

        // Loop projekDetails untuk bahan_id / produk_id
        foreach ($this->projekDetails as $detail) {
            $match = false;

            // Cek apakah item adalah bahan
            if (isset($detail['bahan_id']) && $detail['bahan_id'] == $id) {
                $match = true;
            }

            // Cek apakah item adalah produk
            if (isset($detail['produk_id']) && $detail['produk_id'] == $id) {
                $match = true;
            }

            if (isset($detail['produk_jadis_id']) && $detail['produk_jadis_id'] == $id) {
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
        // Perbandingannya di satuan dasar: `$maxQty` dijumlahkan dari alokasi
        // lot yang tersimpan dalam cm, sedangkan yang diketik bisa jadi jumlah
        // batang. Angka yang disimpan kembali tetap dalam satuan input.
        $index = $this->indexRusak($id, $unitPrice);

        if ($index !== null && $this->qtyDasarBarisRusak($index, $parsedQty) > $maxQty) {
            $parsedQty = $this->maksInputRusak($index, $maxQty);
            session()->flash('error', 'Qty rusak tidak boleh melebihi jumlah pengambilan.');
        }

        // Update qty di bahanRusak
        foreach ($this->bahanRusak as &$rusak) {
            $match = false;

            if (
                (isset($rusak['bahan_id']) && $rusak['bahan_id'] == $id) ||
                (isset($rusak['produk_id']) && $rusak['produk_id'] == $id) ||
                (isset($rusak['produk_jadis_id']) && $rusak['produk_jadis_id'] == $id)
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
        foreach ($this->projekDetails as $detail) {
            $match = false;

            // Cek apakah item adalah bahan
            if (isset($detail['bahan_id']) && $detail['bahan_id'] == $id) {
                $match = true;
            }

            // Cek apakah item adalah produk
            if (isset($detail['produk_id']) && $detail['produk_id'] == $id) {
                $match = true;
            }

            if (isset($detail['produk_jadis_id']) && $detail['produk_jadis_id'] == $id) {
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
        // Perbandingannya di satuan dasar: `$maxQty` dijumlahkan dari alokasi
        // lot yang tersimpan dalam cm, sedangkan yang diketik bisa jadi jumlah
        // batang. Angka yang disimpan kembali tetap dalam satuan input.
        $index = $this->indexRetur($id, $unitPrice);

        if ($index !== null && $this->qtyDasarBarisRetur($index, $parsedQty) > $maxQty) {
            $parsedQty = $this->maksInputRetur($index, $maxQty);
            session()->flash('error', 'Qty retur tidak boleh melebihi jumlah pengambilan.');
        }

        // Update qty di bahanRetur
        foreach ($this->bahanRetur as &$retur) {
            $match = false;

            if (
                (isset($retur['bahan_id']) && $retur['bahan_id'] == $id) ||
                (isset($retur['produk_id']) && $retur['produk_id'] == $id) ||
                (isset($retur['produk_jadis_id']) && $retur['produk_jadis_id'] == $id)
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
        $produksiTotal = array_sum(array_column($this->projekDetails, 'sub_total'));

        return view('livewire.edit-komponen-projek-cart', [
            'cartItems' => $this->cart,
            'projekDetails' => $this->projekDetails,
            'produksiTotal' => $produksiTotal,
            'bahanRusak' => $this->bahanRusak,
            'bahanRetur' => $this->bahanRetur,
        ]);
    }
}
