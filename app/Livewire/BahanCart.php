<?php

namespace App\Livewire;

use App\Models\Bahan;
use App\Models\ProdukProduksiDetail;
use Livewire\Component;

class BahanCart extends Component
{
    public $cart = [];
    public $produkProduksisId; // untuk menangkap id produk produksi dalam mode edit

    protected $listeners = ['bahanSelected' => 'addToCart'];

    public function mount($produkProduksisId = null)
    {
        // Jika dalam mode edit, ambil bahan terkait produk produksi
        $this->produkProduksisId = $produkProduksisId;

        if ($this->produkProduksisId) {
            $this->loadCartItems();
        } else {
            // Inisialisasi keranjang dari sesi atau mulai dengan array kosong
            $this->cart = session()->get('cart', []);
        }
    }

    public function loadCartItems()
    {
        // Ambil bahan terkait produk produksi dari database
        $details = ProdukProduksiDetail::where('produk_produksis_id', $this->produkProduksisId)
            ->with('dataBahan')
            ->get();

        // Konversi ke format yang sesuai untuk keranjang
        foreach ($details as $detail) {
            $this->cart[] = [
                'id' => $detail->dataBahan->id,
                'nama_bahan' => $detail->dataBahan->nama_bahan,
            ];
        }
    }

    public function addToCart($bahan)
    {
        // Konversi objek menjadi array
        $bahan = (array) $bahan;

        // Periksa apakah bahan sudah ada di keranjang
        if (!collect($this->cart)->contains('id', $bahan['id'])) {
            // Tambahkan bahan ke keranjang
            $this->cart[] = $bahan;

            // Simpan ke sesi
            session()->put('cart', $this->cart);
        }
    }

    public function removeItem($bahanId)
    {
        // Filter menggunakan koleksi
        $this->cart = collect($this->cart)->reject(function ($item) use ($bahanId) {
            return isset($item['id']) && $item['id'] == $bahanId;
        })->toArray();  // Konversi kembali ke array setelah reject

        // Simpan pembaruan ke sesi
        session()->put('cart', $this->cart);
    }

    public function render()
    {
        return view('livewire.bahan-cart', [
            'cartItems' => $this->cart,
        ]);
    }
}

