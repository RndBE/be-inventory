<?php

namespace App\Livewire;

use App\Helpers\SatuanBahanHelper;
use App\Livewire\Concerns\MemilihSatuanBahan;
use App\Models\Bahan;
use Livewire\Component;

class BahanKeluarCart extends Component
{
    use MemilihSatuanBahan;

    public $cart = [];
    public $qty = [];
    public $details = [];
    public $details_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;

    protected $listeners = ['bahanSelected' => 'addToCart'];

    public function mount()
    {
        // Load cart items from session if they exist
        $this->loadCartFromSession();
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
            // Sisa stok, nama unit, dan panjang standar dilekatkan di item
            // keranjang supaya baris tabel bisa menampilkan batas dan label
            // satuannya tanpa query per render.
            //
            // `panjang_standar` diambil dari master bahan, bukan dari payload
            // yang dikirim pencarian. Payload-nya kebetulan memuatnya karena
            // SearchBahanMasuk mengirim model Bahan utuh, tapi komponen
            // pencarian lain merakit array sendiri dan tidak semuanya menyertakan
            // kolom ini. Bergantung padanya berarti pilihan satuannya hilang
            // tanpa jejak begitu halaman ini dipasangkan ke pencarian lain.
            $model = Bahan::with('dataUnit', 'purchaseDetails')->find($bahan->id);
            $panjangStandar = SatuanBahanHelper::panjangStandar($model);
            $bahan->stok = $model ? $model->purchaseDetails->sum('sisa') : 0;
            $bahan->unit = $model->dataUnit->nama ?? null;
            $bahan->panjang_standar = $panjangStandar;
            $bahan->stok_label = $model ? $model->formatQty($bahan->stok) : null;

            $this->cart[] = $bahan;
            $this->qty[$bahan->id] = null;
            $this->setelSatuanAwal($bahan->id, $panjangStandar);
        }

        // Save to session
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
                // Item keranjang dirakit ulang lengkap dengan panjang standarnya.
                // Tanpa itu, pilihan satuannya hilang begitu halaman dimuat ulang
                // dari sesi, dan angka yang tadinya diketik "2 batang" muncul
                // kembali sebagai 1.200 tanpa keterangan apa pun.
                $model = Bahan::with('dataUnit', 'purchaseDetails')->find($storedItem['id']);
                $panjangStandar = SatuanBahanHelper::panjangStandar($model);
                $stok = $model ? $model->purchaseDetails->sum('sisa') : 0;

                $this->cart[] = (object) [
                    'id' => $storedItem['id'],
                    'nama_bahan' => $model->nama_bahan ?? null,
                    'panjang_standar' => $panjangStandar,
                    'unit' => $model->dataUnit->nama ?? null,
                    'stok' => $stok,
                    'stok_label' => $model ? $model->formatQty($stok) : null,
                ];

                // Satuan dan angka apa adanya dipulihkan berpasangan. Sesi lama
                // yang belum punya kedua kunci itu jatuh ke angka satuan dasar,
                // sama seperti perilaku sebelumnya.
                $this->satuan[$storedItem['id']] = $storedItem['satuan_input']
                    ?? ($panjangStandar ? SatuanBahanHelper::SATUAN_BATANG : SatuanBahanHelper::SATUAN_DASAR);
                $this->qty[$storedItem['id']] = $storedItem['qty_input'] ?? $storedItem['qty'];
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


    public function increaseQuantity($itemId)
    {
        $item = Bahan::find($itemId); // Temukan item berdasarkan ID
        if ($item) {
            // Ambil total stok dari purchaseDetails berdasarkan sisa
            $totalStok = $item->purchaseDetails()->where('sisa', '>', 0)->sum('sisa');

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

            // Pembatasan dan alokasi lot dilakukan dalam satuan dasar: sisa stok
            // tersimpan dalam cm, sedangkan yang diketik bisa jadi jumlah batang.
            // `$this->qty` sendiri tetap menyimpan angka apa adanya yang diketik.
            $this->qty[$itemId] = $this->batasiQtyInput($itemId, $requestedQty, $totalAvailable);

            // Perbarui unit price dan hitung subtotal berdasarkan kuantitas
            $this->updateUnitPriceAndSubtotal($itemId, $this->qtyDasar($itemId), $purchaseDetails);
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
        $this->saveCartToSession();
    }


    public function getCartItemsForStorage()
    {
        $items = [];
        foreach ($this->cart as $item) {
            $itemId = $item->id;

            $items[] = [
                'id' => $itemId,
                // `qty` dikirim dalam satuan dasar karena inilah angka yang
                // dipotong dari stok. Angka apa adanya yang diketik user
                // disimpan terpisah untuk jejak dan tampilan riwayat.
                'qty' => $this->qtyDasar($itemId),
                'qty_input' => $this->qty[$itemId] ?? 0,
                'satuan_input' => $this->panjangStandarUntuk($itemId) ? $this->satuanUntuk($itemId) : null,
                'details' => isset($this->details[$itemId]) ? $this->details[$itemId] : [],
                'sub_total' => isset($this->subtotals[$itemId]) ? $this->subtotals[$itemId] : 0,
            ];
        }
        return $items;
    }


    public function render()
    {
        return view('livewire.bahan-keluar-cart', [
            'cartItems' => $this->cart,
        ]);
    }
}
