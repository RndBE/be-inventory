<?php

namespace App\Livewire;

use App\Models\Bahan;
use App\Models\ProdukProduksiDetail;
use Livewire\Component;

class BahanCart extends Component
{
    public $cart = [];
    public $produkProduksisId;

    protected $listeners = ['bahanSelected' => 'addToCart'];

    public function mount($produkProduksisId = null)
    {
        $this->produkProduksisId = $produkProduksisId;

        if ($this->produkProduksisId) {
            $this->loadCartItems();
        } else {
            $this->cart = session()->get('cart', []);
        }
    }

    public function loadCartItems()
    {
        $details = ProdukProduksiDetail::where('produk_produksis_id', $this->produkProduksisId)
            ->with('dataBahan')
            ->get();

        foreach ($details as $detail) {
            $this->cart[] = [
                'id' => $detail->dataBahan->id,
                'nama_bahan' => $detail->dataBahan->nama_bahan,
            ];
        }
    }

    public function addToCart($bahan)
    {
        $bahan = (array) $bahan;

        if (!collect($this->cart)->contains('id', $bahan['id'])) {
            $this->cart[] = $bahan;

            session()->put('cart', $this->cart);
        }
    }

    public function removeItem($bahanId)
    {
        $this->cart = collect($this->cart)->reject(function ($item) use ($bahanId) {
            return isset($item['id']) && $item['id'] == $bahanId;
        })->toArray();

        session()->put('cart', $this->cart);
    }

    public function render()
    {
        return view('livewire.bahan-cart', [
            'cartItems' => $this->cart,
        ]);
    }
}


