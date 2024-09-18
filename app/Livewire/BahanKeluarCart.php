<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;

class BahanKeluarCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $unit_price = [];
    public $unit_price_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = 0;

    protected $listeners = ['bahanSelected' => 'addToCart'];

    public function addToCart($bahan)
    {
        if (is_array($bahan)) {
            $bahan = (object) $bahan;
        }
        $bahanData = Bahan::find($bahan->id); 
        if ($bahanData) {
            if ($bahanData->total_stok <= 0) {
                return;
            }
        } else {
            return;
        }
        // Cek apakah bahan sudah ada di keranjang
        $existingItemKey = array_search($bahan->id, array_column($this->cart, 'id'));
        if ($existingItemKey !== false) {
            // Jika bahan sudah ada, tingkatkan kuantitas
            $this->qty[$bahan->id]++;
        } else {
            // Jika bahan belum ada, tambahkan ke keranjang
            $this->cart[] = $bahan;
            $this->qty[$bahan->id] = 0; // Set kuantitas menjadi 1
            $this->unit_price_raw[$bahan->id] = 0;
            $this->unit_price[$bahan->id] = 0;
        }
        // Hitung subtotal untuk item yang ditambahkan atau diperbarui
        $this->calculateSubTotal($bahan->id);
    }


    public function calculateSubTotal($itemId)
    {
        $unitPrice = isset($this->unit_price[$itemId]) ? intval($this->unit_price[$itemId]) : 0;
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
        $item = Bahan::find($itemId); // Ganti dengan model yang sesuai
        if ($item && $item->total_stok > 0) {
            // Cek apakah kuantitas saat ini kurang dari total_stok
            if (!isset($this->qty[$itemId]) || $this->qty[$itemId] < $item->total_stok) {
                $this->qty[$itemId] = isset($this->qty[$itemId]) ? $this->qty[$itemId] + 1 : 1;
                $this->calculateSubTotal($itemId); // Panggil untuk menghitung subtotal
            }
        }
    }


    public function decreaseQuantity($itemId)
    {
        if (isset($this->qty[$itemId]) && $this->qty[$itemId] > 1) {
            $this->qty[$itemId]--;
            $this->calculateSubTotal($itemId); // Panggil untuk menghitung subtotal
        }
    }

    public function formatToRupiah($itemId)
    {
        // Pastikan untuk menghapus 'Rp.' dan mengonversi ke integer
        $this->unit_price[$itemId] = intval(str_replace(['.', ' '], '', $this->unit_price_raw[$itemId]));
        $this->unit_price_raw[$itemId] = $this->unit_price[$itemId];
        $this->calculateSubTotal($itemId); // Hitung subtotal setelah format
        $this->editingItemId = null; // Reset ID setelah selesai
    }

    public function updateQuantity($itemId)
    {
        $item = Bahan::find($itemId);
        if ($item) {
            if ($this->qty[$itemId] > $item->total_stok) {
                $this->qty[$itemId] = $item->total_stok;
            } elseif ($this->qty[$itemId] < 0) {
                $this->qty[$itemId] = 0;
            }
            $this->calculateSubTotal($itemId);
        }
    }

    public function editItem($itemId)
    {
        $this->editingItemId = $itemId; // Set ID item yang sedang diedit
        $this->unit_price_raw[$itemId] = $this->unit_price[$itemId]; // Ambil nilai untuk diedit
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
    }

    public function getCartItemsForStorage()
    {
        $items = [];
        foreach ($this->cart as $item) {
            $items[] = [
                'id' => $item->id,
                'qty' => $this->qty[$item->id],
                'unit_price' => $this->unit_price_raw[$item->id],
                'sub_total' => $this->subtotals[$item->id],
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
