<?php

namespace App\Livewire;

use App\Helpers\SatuanBahanHelper;
use App\Models\Bahan;
use Livewire\Component;
use App\Models\ProdukProduksi;
use App\Models\ProdukProduksiDetail;
use App\Models\BahanSetengahjadiDetails;

class BahanPengambilanCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $jml_bahan = [];

    /**
     * Satuan input per bahan: 'batang' atau 'cm'.
     *
     * Hanya dipakai bahan batangan. Bahan lain tidak menampilkan dropdown-nya
     * dan nilainya diabaikan, karena konversi untuk bahan tanpa panjang
     * standar selalu identitas.
     */
    public $satuan = [];
    public $details = [];
    public $details_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;

    protected $listeners = ['bahanSelected' => 'addToCart'];

    public function mount()
    {

    }

    public function addToCart($bahan)
    {
        if (is_array($bahan)) {
            $bahan = (object) $bahan;
        }

        $existingItemKey = array_search($bahan->bahan_id, array_column($this->cart, 'id'));

        if ($existingItemKey !== false) {
            $this->updateQuantity($bahan->bahan_id);
        } else {
            $item = Bahan::with('purchaseDetails')->find($bahan->bahan_id);

            if (!$item) {
                session()->flash('error', 'Bahan tidak ditemukan.');
                return;
            }

            $totalAvailable = $item->purchaseDetails->sum('sisa');
            if ($totalAvailable <= 0) {
                session()->flash('error', 'Sisa bahan tidak tersedia.');
                return;
            }

            // Tambahkan item ke keranjang
            $cartItem = (object)[
                'id' => $item->id,
                'nama_bahan' => $item->nama_bahan,
                'stok' => $totalAvailable,
                'unit' => $bahan->unit ?? ($item->dataUnit->nama ?? '-'),
                // Dibawa di item keranjang supaya baris tabel bisa memutuskan
                // perlu-tidaknya dropdown satuan tanpa query ulang per render.
                'panjang_standar' => SatuanBahanHelper::panjangStandar($item),
                'stok_label' => $item->formatQty($totalAvailable),
            ];

            $this->cart[] = $cartItem;
            $this->qty[$item->id] = null;
            $this->jml_bahan[$item->id] = null;
            // Default batang: pengambilan satu batang utuh jauh lebih sering
            // daripada potongan, jadi itu yang dijadikan pilihan awal.
            $this->satuan[$item->id] = $cartItem->panjang_standar
                ? SatuanBahanHelper::SATUAN_BATANG
                : SatuanBahanHelper::SATUAN_DASAR;
        }

        $this->saveCartToSession();
        $this->calculateSubTotal($bahan->bahan_id);
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
                $this->details[$storedItem['id']] = $storedItem['details'];
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

    /**
     * Satuan input yang sedang aktif untuk satu bahan.
     */
    public function satuanUntuk($itemId): string
    {
        return SatuanBahanHelper::normalkanSatuan($this->satuan[$itemId] ?? null);
    }

    /**
     * Panjang standar bahan di keranjang, atau null kalau bukan bahan batangan.
     */
    public function panjangStandarUntuk($itemId): ?int
    {
        foreach ($this->cart as $item) {
            if ($item->id == $itemId) {
                return $item->panjang_standar ?? null;
            }
        }

        return null;
    }

    /**
     * Ganti satuan input, lalu sesuaikan angka yang sudah diketik.
     *
     * Angkanya tidak dipertahankan begitu saja: "5" yang tadinya berarti 5
     * batang jelas tidak boleh diam-diam jadi 5 cm. Nilainya dikosongkan supaya
     * user mengisi ulang sesuai satuan barunya.
     */
    public function updateSatuan($itemId)
    {
        $this->qty[$itemId] = null;
        $this->saveCartToSession();
    }

    /**
     * Batas atas angka yang boleh diketik, dalam satuan input yang aktif.
     *
     * Untuk satuan batang hasilnya dibulatkan ke bawah: kalau sisa stok 2040 cm
     * pada batang 600 cm, yang bisa diambil sebagai batang cuma 3 — sisa 240 cm
     * harus diambil dengan memilih satuan cm.
     */
    public function maksInput($itemId): float
    {
        $stok = 0;

        foreach ($this->cart as $item) {
            if ($item->id == $itemId) {
                $stok = (float) $item->stok;
                break;
            }
        }

        $panjangStandar = $this->panjangStandarUntuk($itemId);
        $maks = SatuanBahanHelper::dariSatuanDasar($stok, $this->satuanUntuk($itemId), $panjangStandar);

        if ($panjangStandar && $this->satuanUntuk($itemId) === SatuanBahanHelper::SATUAN_BATANG) {
            return floor($maks);
        }

        return $maks;
    }

    public function updateQuantity($itemId)
    {
        $requestedQty = $this->qty[$itemId] ?? 0;
        $item = Bahan::find($itemId);

        if ($item) {
            if ($item->jenisBahan->nama === 'Produksi') {
                $bahanSetengahjadiDetails = $item->bahanSetengahjadiDetails()
                    ->where('sisa', '>', 0)
                    ->with(['bahanSetengahjadi' => function ($query) {
                        $query->orderBy('tgl_masuk', 'asc');
                    }])->get();

                $totalAvailable = $bahanSetengahjadiDetails->sum('sisa');
                $this->qty[$itemId] = $this->batasiQty($itemId, $requestedQty, $totalAvailable);
                // $this->updateUnitPriceAndSubtotalBahanSetengahJadi($itemId, $this->qty[$itemId], $bahanSetengahjadiDetails);
            } elseif ($item->jenisBahan->nama !== 'Produksi') {
                $purchaseDetails = $item->purchaseDetails()
                    ->where('sisa', '>', 0)
                    ->with(['purchase' => function ($query) {
                        $query->orderBy('tgl_masuk', 'asc');
                    }])->get();

                $totalAvailable = $purchaseDetails->sum('sisa');
                $this->qty[$itemId] = $this->batasiQty($itemId, $requestedQty, $totalAvailable);
                // $this->updateUnitPriceAndSubtotal($itemId, $this->qty[$itemId], $purchaseDetails);
            }
        }
    }

    /**
     * Angka yang diketik dibatasi sisa stok.
     *
     * Perbandingannya dilakukan dalam satuan dasar, bukan satuan input: sisa
     * stok tersimpan dalam cm, sedangkan yang diketik bisa jadi jumlah batang.
     */
    private function batasiQty($itemId, $requestedQty, $totalAvailable)
    {
        if ($requestedQty === null || $requestedQty === '' || (float) $requestedQty < 0) {
            return null;
        }

        $panjangStandar = $this->panjangStandarUntuk($itemId);
        $satuan = $this->satuanUntuk($itemId);
        $diminta = SatuanBahanHelper::keSatuanDasar($requestedQty, $satuan, $panjangStandar);

        if ($diminta <= $totalAvailable) {
            return $requestedQty;
        }

        return $this->maksInput($itemId);
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
            $qtyInput = $this->qty[$itemId] ?? 0;
            $satuan = $this->satuanUntuk($itemId);
            $panjangStandar = $item->panjang_standar ?? null;

            $items[] = [
                'id' => $itemId,
                // `qty` selalu dalam satuan dasar karena inilah angka yang
                // masuk ke ledger stok. Angka yang diketik user disimpan
                // terpisah di `qty_input`/`satuan_input` untuk jejak dan
                // tampilan, bukan untuk dihitung.
                'qty' => SatuanBahanHelper::keSatuanDasar($qtyInput, $satuan, $panjangStandar),
                'qty_input' => $qtyInput,
                'satuan_input' => $panjangStandar ? $satuan : null,
                'jml_bahan' => isset($this->jml_bahan[$itemId]) ? $this->jml_bahan[$itemId] : 0,
                'details' => isset($this->details[$itemId]) ? $this->details[$itemId] : [],
                'sub_total' => isset($this->subtotals[$itemId]) ? $this->subtotals[$itemId] : 0,
            ];
        }
        return $items;
    }

    public function render()
    {
        return view('livewire.bahan-pengambilan-cart', [
            'cartItems' => $this->cart,
        ]);
    }
}
