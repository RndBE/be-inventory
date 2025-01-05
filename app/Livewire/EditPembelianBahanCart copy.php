<?php

namespace App\Livewire;

use App\Models\Bahan;
use App\Models\Pengajuan;
use Livewire\Component;
use App\Models\Produksi;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use App\Models\PembelianBahan;

class EditPembelianBahanCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $jml_bahan = [];
    public $details = [];
    public $details_raw = [];
    public $unit_price = [];
    public $ongkir = [];
    public $ongkir_raw = [];
    public $asuransi = [];
    public $asuransi_raw = [];
    public $layanan = [];
    public $layanan_raw = [];
    public $jasa_aplikasi = [];
    public $jasa_aplikasi_raw = [];
    public $unit_price_raw = [];
    public $subtotals = [];
    public $totalharga = 0;
    public $editingItemId = null;
    public $pembelianBahanId;
    public $pembelianBahanDetails = [];
    public $bahanRusak = [];
    public $bahanRetur = [];
    public $isFirstTimePengajuan = [];
    public $isBahanReturPending = [];
    public $pendingReturCount = [];
    public $isBahanRusakPending = [];
    public $pendingRusakCount = [];
    public $produksiStatus,$status,$status_finance;
    public $keterangan_pembayaran = [];
    public $pembelianBahans = [];


    public function mount($pembelianBahanId)
    {
        $this->pembelianBahanId = $pembelianBahanId;
        $pembelianBahan = PembelianBahan::findOrFail($pembelianBahanId);
        $this->status_finance = $pembelianBahan->status_finance;

        $this->loadProduksi();
    }

    public function loadProduksi()
    {
        $pembelianBahan = PembelianBahan::with('pembelianBahanDetails')->find($this->pembelianBahanId);

        if ($pembelianBahan) {
            $ongkir = $pembelianBahan->ongkir ?? 0;
            $asuransi = $pembelianBahan->asuransi ?? 0;
            $layanan = $pembelianBahan->layanan ?? 0;
            $jasa_aplikasi = $pembelianBahan->jasa_aplikasi ?? 0;

            $this->ongkir = $ongkir;
            $this->asuransi = $asuransi;
            $this->layanan = $layanan;
            $this->jasa_aplikasi = $jasa_aplikasi;

            foreach ($pembelianBahan->pembelianBahanDetails as $detail) {
                $decodedDetails = json_decode($detail->details, true);
                $unitPrice = $decodedDetails['unit_price'] ?? 0;
                $this->keterangan_pembayaran[$detail->bahan_id] = $detail->keterangan_pembayaran ?? '';

                $this->pembelianBahanDetails[] = [
                    'bahan' => Bahan::find($detail->bahan_id),
                    'jml_bahan' => $detail->jml_bahan,
                    'used_materials' => $detail->used_materials ?? 0,
                    'sub_total' => $detail->sub_total,
                    'details' => $decodedDetails,
                    'keterangan_pembayaran' => $this->keterangan_pembayaran[$detail->bahan_id],
                    'spesifikasi' => $detail->spesifikasi ?? '',
                ];
                $this->unit_price[$detail->bahan_id] = $unitPrice;
            }

        }
    }

    protected function saveCartToSession()
    {
        session()->put('cartItems', $this->getCartItemsForStorage());
    }

    public function calculateSubTotal($itemId)
    {
        $detail = collect($this->pembelianBahanDetails)->firstWhere('id', $itemId);

        $jml_bahan = isset($detail['jml_bahan']) ? intval($detail['jml_bahan']) : 0;
        $unit_price = isset($this->unit_price[$itemId]) ? intval($this->unit_price[$itemId]) : 0;

        $this->subtotals[$itemId] = $jml_bahan * $unit_price;
        $this->calculateTotalHarga();
    }

    public function calculateTotalHarga()
    {
        $this->totalharga = array_sum($this->subtotals);
    }

    // Fungsi umum untuk memformat ke Rupiah
    public function formatToRupiah($item)
    {
        // Memproses format input ke angka
        $this->$item = intval(str_replace(['.', 'Rp'], '', $this->{$item . '_raw'}));
        $this->{$item . '_raw'} = number_format($this->$item, 0, ',', '.');

        // Jika item memiliki subtotal, hitung ulang
        if ($item === 'unit_price') {
            $this->calculateSubTotal($this->editingItemId);
        }

        // Reset item yang sedang diedit
        $this->editingItemId = null;
    }

    // Fungsi umum untuk mengedit item
    public function editItem($item)
    {
        $this->editingItemId = $item; // Tandai item yang sedang diedit
        $this->{$item . '_raw'} = $this->$item ?? null; // Ambil nilai saat ini
    }


    // public function formatToRupiah($itemId)
    // {
    //     $this->unit_price[$itemId] = intval(str_replace(['.', 'Rp'], '', $this->unit_price_raw[$itemId]));
    //     $this->unit_price_raw[$itemId] = number_format($this->unit_price[$itemId], 0, ',', '.');

    //     $this->calculateSubTotal($itemId);
    //     $this->editingItemId = null;
    // }
    // public function editItem($itemId)
    // {
    //     $this->editingItemId = $itemId;
    //     if (isset($this->unit_price[$itemId])) {
    //         $this->unit_price_raw[$itemId] = $this->unit_price[$itemId];
    //     } else {
    //         $this->unit_price_raw[$itemId] = null;
    //     }
    // }

    // public function formatToRupiahOngkir()
    // {
    //     $this->ongkir = intval(str_replace(['.', 'Rp'], '', $this->ongkir_raw));
    //     $this->ongkir_raw = number_format($this->ongkir, 0, ',', '.');
    //     $this->editingItemId = null; // Reset editing
    // }

    // public function formatToRupiahAsuransi()
    // {
    //     $this->asuransi = intval(str_replace(['.', 'Rp'], '', $this->asuransi_raw));
    //     $this->asuransi_raw = number_format($this->asuransi, 0, ',', '.');
    //     $this->editingItemId = null; // Reset editing
    // }

    // public function formatToRupiahLayanan()
    // {
    //     $this->layanan = intval(str_replace(['.', 'Rp'], '', $this->layanan_raw));
    //     $this->layanan_raw = number_format($this->layanan, 0, ',', '.');
    //     $this->editingItemId = null; // Reset editing
    // }

    // public function formatToRupiahJasaAplikasi()
    // {
    //     $this->jasa_aplikasi = intval(str_replace(['.', 'Rp'], '', $this->jasa_aplikasi_raw));
    //     $this->jasa_aplikasi_raw = number_format($this->jasa_aplikasi, 0, ',', '.');
    //     $this->editingItemId = null; // Reset editing
    // }

    // public function editItemOngkir()
    // {
    //     $this->editingItemId = 'ongkir';
    //     $this->ongkir_raw = $this->ongkir; // Ambil nilai saat ini
    // }

    // public function editItemAsuransi()
    // {
    //     $this->editingItemId = 'asuransi';
    //     $this->asuransi_raw = $this->asuransi; // Ambil nilai saat ini
    // }

    // public function editItemLayanan()
    // {
    //     $this->editingItemId = 'layanan';
    //     $this->layanan_raw = $this->layanan; // Ambil nilai saat ini
    // }
    // public function editItemJasaAplikasi()
    // {
    //     $this->editingItemId = 'jasa_aplikasi';
    //     $this->jasa_aplikasi_raw = $this->jasa_aplikasi; // Ambil nilai saat ini
    // }



    public function changeKeterangan($itemId)
    {
        $requestedQty = $this->keterangan_pembayaran[$itemId] ?? 0;
    }
    public function getCartItemsForStorage()
    {
        $pembelianBahanDetails = [];
        $ongkir = [];

        // dd($this->keterangan_pembayaran);
        foreach ($this->pembelianBahanDetails as $item) {
            $bahanId = $item['bahan']->id;
            $unitPrice = $this->unit_price[$bahanId] ?? 0;
            $subTotal = $item['jml_bahan'] * $unitPrice;
            $keteranganPembayaran = $this->keterangan_pembayaran[$bahanId] ?? '';
            $pembelianBahanDetails[] = [
                'id' => $bahanId,
                'qty' => $this->qty[$bahanId] ?? 0,
                'jml_bahan' => $item['jml_bahan'],
                'details' => [
                    'unit_price' => $unitPrice,
                ],
                'sub_total' => $subTotal,
                'keterangan_pembayaran' => $keteranganPembayaran,
            ];
        }
        $ongkir = $this->ongkir;
        return $pembelianBahanDetails;
        return $ongkir;
    }

    public function getCartItemsForStorageBiaya()
    {
        return [
            'ongkir' => $this->ongkir,
            'asuransi' => $this->asuransi,
            'layanan' => $this->layanan,
            'jasa_aplikasi' => $this->jasa_aplikasi,
        ];
    }



    public function render()
    {
        $produksiTotal = array_sum(array_column($this->pembelianBahanDetails, 'sub_total'));

        return view('livewire.edit-pembelian-bahan-cart', [
            'cartItems' => $this->cart,
            'pembelianBahanDetails' => $this->pembelianBahanDetails,
            'produksiTotal' => $produksiTotal,
        ]);
    }
}
