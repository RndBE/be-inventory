<?php

namespace App\Livewire;

use App\Models\Bahan;
use App\Models\ProdukProduksiDetail;
use Livewire\Component;

class BahanCart extends Component
{
    public $cart = [];
    public $produkProduksisId;

    protected $listeners = ['bahanSelected' => 'addToCart']; // hapus removeItem dari sini

    public function mount($produkProduksisId = null)
    {
        $this->produkProduksisId = $produkProduksisId;

        if ($this->produkProduksisId) {
            $this->loadCartItems();
        }
    }

    public function loadCartItems()
    {
        $details = ProdukProduksiDetail::where('produk_produksis_id', $this->produkProduksisId)
            ->with('dataBahan')
            ->get();

        $this->cart = $details->map(fn($d) => [
            'id' => $d->dataBahan->id,
            'nama_bahan' => $d->dataBahan->nama_bahan,
            'jml_bahan' => $d->jml_bahan ?? 0,
        ])->sortBy('nama_bahan')->values()->toArray();
    }

    // public function addToCart($bahan)
    // {
    //     $bahan = (array) $bahan;

    //     if (!collect($this->cart)->contains('id', $bahan['id'])) {
    //         $this->cart[] = $bahan;
    //         session()->put('cart', $this->cart);
    //     }
    // }
    public function addToCart($bahan)
    {
        $bahan = (array) $bahan;
        $id = $bahan['id'] ?? $bahan['bahan_id']; // ✅ fallback ke bahan_id

        if (!collect($this->cart)->contains('id', $id)) {
            $this->cart[] = [
                'id' => $id,
                'nama_bahan' => $bahan['nama'] ?? $bahan['nama_bahan'] ?? '-',
                'jml_bahan' => 0,
            ];

            session()->put('cart', $this->cart);
        }
    }


    public function removeItem($bahanId)
    {
        $this->cart = collect($this->cart)
            ->reject(fn($item) => $item['id'] == $bahanId)
            ->values()
            ->toArray();

        session()->put('cart', $this->cart);
    }

    public function render()
    {
        return view('livewire.bahan-cart', [
            'cartItems' => $this->cart,
        ]);
    }
}



