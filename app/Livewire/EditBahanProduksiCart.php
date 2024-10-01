<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use App\Models\Produksi;

class EditBahanProduksiCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $kode_transaksi = [];
    public $details = [];
    public $details_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;
    public $produksiId, $status;
    public $bahanRusak = []; // Menyimpan bahan yang rusak


    protected $listeners = ['bahanSelected' => 'addToCart'];

    public function mount($produksiId)
    {
        $this->produksiId = $produksiId;
        // $this->loadCartFromSession();
        $this->loadMaterials();
    }

    public function loadMaterials()
    {
        $produksi = Produksi::with('produksiDetails.dataBahan')->find($this->produksiId);

        if ($produksi) {
            $this->status = $produksi->status;
            $this->details = []; // Reset details

            foreach ($produksi->produksiDetails as $detail) {
                $this->cart[] = $detail->dataBahan; // Mengambil data bahan
                $this->kode_transaksi[$detail->dataBahan->id] = $detail->kode_transaksi;
                $this->qty[$detail->dataBahan->id] = $detail->qty;
                $this->subtotals[$detail->bahan_id] = $detail->sub_total;

                // Decode the details JSON string
                $decodedDetails = json_decode($detail->details, true); // Decode JSON to associative array

                // Tambahkan details berdasarkan bahan_id
                if (is_array($decodedDetails)) {
                    foreach ($decodedDetails as $item) {
                        // Periksa apakah item memiliki kode_transaksi dan unit_price
                        if (isset($item['kode_transaksi'], $item['qty'], $item['unit_price'])) {
                            // Simpan details berdasarkan bahan_id
                            $this->details[$detail->bahan_id][] = [
                                'kode_transaksi' => $item['kode_transaksi'],
                                'qty' => $item['qty'],
                                'unit_price' => $item['unit_price'],
                            ];
                        }
                    }
                }
            }

            // Menghitung total harga
            $this->totalharga = array_sum($this->subtotals);
        }
    }



    public function addToCart($bahan)
    {
        if (is_array($bahan)) {
            $bahan = (object) $bahan;
        }

        $existingItemKey = array_search($bahan->id, array_column($this->cart, 'id'));
        if ($existingItemKey !== false) {
            $this->qty[$bahan->id]++;
        } else {
            $this->cart[] = $bahan;
            $this->qty[$bahan->id] = null;
        }

        // Save to session
        // $this->saveCartToSession();
        $this->calculateSubTotal($bahan->id);
    }
    // protected function saveCartToSession()
    // {
    //     session()->put('cartItems', $this->getCartItemsForStorage());
    // }

    // protected function loadCartFromSession()
    // {
    //     if (session()->has('cartItems')) {
    //         $storedItems = session()->get('cartItems');
    //         foreach ($storedItems as $storedItem) {
    //             $this->cart[] = (object) ['id' => $storedItem['id'], 'nama_bahan' => Bahan::find($storedItem['id'])->nama_bahan];
    //             $this->qty[$storedItem['id']] = $storedItem['qty'];
    //             $this->subtotals[$storedItem['id']] = $storedItem['sub_total'];
    //         }
    //         $this->calculateTotalHarga();
    //     }
    // }


    public function calculateSubTotal($itemId)
    {
        $subTotal = 0;

        // Hitung subtotal berdasarkan semua details untuk item tersebut
        if (isset($this->details[$itemId])) {
            foreach ($this->details[$itemId] as $detail) {
                $subTotal += $detail['qty'] * $detail['unit_price'];
            }
        }

        $this->subtotals[$itemId] = $subTotal;
        $this->calculateTotalHarga();
    }



    public function calculateTotalHarga()
    {
        $this->totalharga = array_sum($this->subtotals);
    }


    public function increaseQuantity($itemId)
    {
        $item = Bahan::find($itemId); // Temukan item berdasarkan ID
        if ($item) {
            // Ambil total stok dari purchaseDetails berdasarkan sisa
            $totalStok = $item->purchaseDetails()->where('sisa', '>', 0)->sum('sisa');
            // dd($totalStok);
            // Cek apakah ada stok yang tersedia dan apakah kuantitas yang diminta lebih kecil dari total stok
            if ($totalStok > 0 && (!isset($this->qty[$itemId]) || $this->qty[$itemId] < $totalStok)) {
                // Tambah kuantitas jika belum melebihi stok yang tersedia
                $this->qty[$itemId] = isset($this->qty[$itemId]) ? $this->qty[$itemId] + 1 : 1;
                $this->updateQuantity($itemId); // Panggil updateQuantity untuk menghitung ulang subtotal dan total harga
            }
        }
    }

    public function decreaseQuantity($itemId)
    {
        // Cek apakah kuantitas untuk item tersebut sudah diatur dan lebih besar dari 1
        if (isset($this->qty[$itemId]) && $this->qty[$itemId] > 1) {
            $this->qty[$itemId]--; // Kurangi kuantitas sebesar 1
            $this->updateQuantity($itemId); // Panggil updateQuantity untuk memperbarui subtotal dan total harga
        } elseif (isset($this->qty[$itemId]) && $this->qty[$itemId] == 1) {
            // Jika kuantitas adalah 1, setel ke nol
            $this->qty[$itemId] = 0;
            $this->updateQuantity($itemId); // Tetap panggil updateQuantity untuk mengupdate subtotal
        }
    }

    public function decreaseQuantityPerPrice($itemId, $unitPrice)
    {
        // Periksa apakah item ada dalam details
        if (isset($this->details[$itemId])) {
            foreach ($this->details[$itemId] as $key => $detail) {
                if ($detail['unit_price'] == $unitPrice) {
                    // Cek apakah qty lebih dari 1 sebelum menguranginya
                    if ($detail['qty'] > 1) {
                        // Kurangi qty
                        $this->details[$itemId][$key]['qty']--;

                        // Cek apakah bahan rusak sudah ada
                        $found = false;
                        foreach ($this->bahanRusak as &$rusak) {
                            if ($rusak['id'] == $itemId && $rusak['unit_price'] == $unitPrice) {
                                // Jika ada, tambahkan qty
                                $rusak['qty']++;
                                $found = true;
                                break;
                            }
                        }

                        // Jika tidak ditemukan, tambahkan ke bahan rusak
                        if (!$found) {
                            $this->bahanRusak[] = [
                                'id' => $itemId,
                                'unit_price' => $unitPrice,
                                'qty' => 1 // Tambahkan 1 ke bahan rusak
                            ];
                        }
                    } else {
                        // Jika qty 1 atau lebih kecil, hapus item dari details
                        unset($this->details[$itemId][$key]);

                        // Cek apakah bahan rusak sudah ada
                        $found = false;
                        foreach ($this->bahanRusak as &$rusak) {
                            if ($rusak['id'] == $itemId && $rusak['unit_price'] == $unitPrice) {
                                // Jika ada, tambahkan qty
                                $rusak['qty'] += 1; // Tambahkan 1 ke bahan rusak
                                $found = true;
                                break;
                            }
                        }

                        // Jika tidak ditemukan, tambahkan ke bahan rusak
                        if (!$found) {
                            $this->bahanRusak[] = [
                                'id' => $itemId,
                                'unit_price' => $unitPrice,
                                'qty' => 1 // Tambahkan 1 ke bahan rusak
                            ];
                        }
                    }
                    break; // Keluar dari loop setelah menemukan detail yang sesuai
                }
            }

            // Jika sudah tidak ada details, hapus dari array utama
            if (empty($this->details[$itemId])) {
                unset($this->details[$itemId]);
            }

            // Update qty di array qty berdasarkan details yang tersisa
            $this->qty[$itemId] = isset($this->details[$itemId])
                ? array_sum(array_column($this->details[$itemId], 'qty'))
                : 0; // Atur ke 0 jika tidak ada details

            // Hitung subtotal setelah pengurangan qty
            $this->calculateSubTotal($itemId);
        }
    }

    public function returnToProduction($itemId, $unitPrice)
    {
        // Tambahkan kembali 1 unit bahan ke cart bahan produksi
        if (!isset($this->details[$itemId])) {
            $this->details[$itemId] = []; // Inisialisasi jika belum ada
        }

        // Cek jika item sudah ada, tambahkan qty
        $found = false;
        foreach ($this->details[$itemId] as &$detail) {
            if ($detail['unit_price'] == $unitPrice) {
                $detail['qty']++; // Tambahkan 1 unit
                $found = true;
                break;
            }
        }

        // Jika item belum ada, tambahkan sebagai item baru
        if (!$found) {
            $this->details[$itemId][] = [
                'unit_price' => $unitPrice,
                'qty' => 1 // Tambahkan 1 unit
            ];
        }

        // Hapus dari bahan rusak dan update qty dan unit price jika perlu
        foreach ($this->bahanRusak as $key => &$rusak) {
            if ($rusak['id'] == $itemId) {
                // Update unit price jika berbeda
                if ($rusak['unit_price'] != $unitPrice) {
                    $rusak['unit_price'] = $unitPrice; // Update unit price
                }
                // Kurangi qty bahan rusak
                $rusak['qty']--;
                // Jika qty menjadi 0, hapus dari bahan rusak
                if ($rusak['qty'] <= 0) {
                    unset($this->bahanRusak[$key]);
                }
                break; // Keluar setelah menghapus
            }
        }

        // Update qty di array qty berdasarkan details yang tersisa
        $this->qty[$itemId] = isset($this->details[$itemId])
            ? array_sum(array_column($this->details[$itemId], 'qty'))
            : 0; // Atur ke 0 jika tidak ada details

        // Hitung subtotal setelah pengembalian
        $this->calculateSubTotal($itemId);
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
        $item = Bahan::find($itemId);
        if ($item) {
            $requestedQty = $this->qty[$itemId];

            // Ambil semua purchase details yang memiliki sisa > 0 untuk item ini
            $purchaseDetails = $item->purchaseDetails()
                ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
                ->where('sisa', '>', 0)
                ->orderBy('purchases.tgl_masuk', 'asc')
                ->select('purchase_details.*', 'purchases.kode_transaksi') // Include kode_transaksi_masuk
                ->get();


            $totalAvailable = $purchaseDetails->sum('sisa');

            // Jika permintaan melebihi total sisa yang tersedia
            if ($requestedQty > $totalAvailable) {
                $this->qty[$itemId] = $totalAvailable; // Atur kuantitas ke total sisa
            } elseif ($requestedQty < 0) {
                $this->qty[$itemId] = null; // Atur kuantitas ke 0
            } else {
                // Kuantitas yang diminta valid, biarkan seperti itu
                $this->qty[$itemId] = $requestedQty;
            }

            // Perbarui unit price dan hitung subtotal berdasarkan kuantitas
            $this->updateUnitPriceAndSubtotal($itemId, $this->qty[$itemId], $purchaseDetails);
        }
    }

    protected function updateUnitPriceAndSubtotal($itemId, $qty, $purchaseDetails)
    {
        $remainingQty = $qty;
        $totalPrice = 0;
        $this->details_raw[$itemId] = []; // Reset for item
        $this->details[$itemId] = []; // Reset array details for this item

        foreach ($purchaseDetails as $purchaseDetail) {
            if ($remainingQty <= 0) break;

            $availableQty = $purchaseDetail->sisa;

            if ($availableQty > 0) {
                $toTake = min($availableQty, $remainingQty);
                $totalPrice += $toTake * $purchaseDetail->unit_price;

                // Store unit price as [kode_transaksi_masuk, qty, details]
                $this->details[$itemId][] = [
                    'kode_transaksi' => $purchaseDetail->kode_transaksi, // Assuming this is the column name
                    'qty' => $toTake,
                    'unit_price' => $purchaseDetail->unit_price
                ];
                $remainingQty -= $toTake;
            }
        }

        $this->subtotals[$itemId] = $totalPrice;
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
        // $this->saveCartToSession();
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
        return view('livewire.edit-bahan-produksi-cart', [
            'cartItems' => $this->cart,
            'bahanRusak' => $this->bahanRusak,
        ]);
    }
}
