<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Bahan;
use Livewire\Component;
use App\Models\Produksi;
use App\Models\Pengajuan;
use App\Helpers\LogHelper;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use App\Models\PembelianBahan;
use App\Models\PengajuanDetails;
use App\Models\PembelianBahanDetails;
use App\Jobs\SendWhatsAppNotification;

class EditPembelianBahanCart extends Component
{
    public $cart = [];
    public $qty = [];
    public $jml_bahan = [];
    public $qty_pengajaun = [];
    public $details = [];
    public $details_raw = [];
    public $unit_price = [];
    public $unit_price_aset = [];
    public $unit_price_raw = [];
    public $editingJmlId = null;
    public $jml_bahan_raw = [];
    public $unit_price_usd = [];
    public $unit_price_usd_raw = [];
    public $ongkir = [];
    public $ongkir_raw = [];
    public $asuransi = [];
    public $asuransi_raw = [];
    public $layanan = [];
    public $ppn = [];
    public $ppn_raw = [];
    public $layanan_raw = [];
    public $jasa_aplikasi = [];
    public $jasa_aplikasi_raw = [];
    public $shipping_cost = [];
    public $shipping_cost_raw = [];
    public $shipping_cost_usd = [];
    public $shipping_cost_usd_raw = [];
    public $full_amount_fee = [];
    public $full_amount_fee_raw = [];
    public $full_amount_fee_usd = [];
    public $full_amount_fee_usd_raw = [];
    public $value_today_fee = [];
    public $value_today_fee_raw = [];
    public $value_today_fee_usd = [];
    public $value_today_fee_usd_raw = [];
    public $subtotals = [];
    public $subtotals_usd = [];
    public $totalharga = 0;
    public $totalhargausd = 0;
    public $editingItemId = null;
    public $editingItemBahan = null;
    public $pembelianBahanId;
    public $pembelianBahanDetails = [];
    public $bahanRusak = [];
    public $bahanRetur = [];
    public $isFirstTimePengajuan = [];
    public $isBahanReturPending = [];
    public $pendingReturCount = [];
    public $isBahanRusakPending = [];
    public $pendingRusakCount = [];
    public $produksiStatus, $status, $status_finance;
    public $keterangan_pembayaran = [];
    public $pembelianBahans = [];
    public $jenis_pengajuan, $editingCurrency;
    public $statusPembelianBahan = [];
    public $checkedItems = [];
    public $bukti_pembelian, $pembelianId;
    public $unit_price_usd_aset = [];



    public function mount($pembelianBahanId)
    {
        $this->pembelianBahanId = $pembelianBahanId;
        $pembelianBahan = PembelianBahan::findOrFail($pembelianBahanId);
        $this->status_finance = $pembelianBahan->status_finance;
        $this->jenis_pengajuan = $pembelianBahan->jenis_pengajuan;

        $this->loadProduksi();
    }

    // Saat user klik untuk edit jumlah bahan
    public function editItemJmlBahan($id)
    {
        $this->editingJmlId = $id;
        $this->jml_bahan_raw[$id] = $this->jml_bahan[$id] ?? 0;
    }

    public function updateJmlBahan($pembelianId, $bahanId)
    {
        $this->validate([
            "jml_bahan_raw.$bahanId" => 'nullable|numeric|min:0'
        ]);

        $newValue = floatval($this->jml_bahan_raw[$bahanId] ?? 0);

        // Ambil detail pembelian
        $detail = PembelianBahanDetails::where('pembelian_bahan_id', $pembelianId)
            ->where('bahan_id', $bahanId)
            ->first();

        if (!$detail) {
            return session()->flash('error', 'Detail pembelian tidak ditemukan.');
        }

        // Update jumlah bahan di pembelian details
        $detail->jml_bahan = $newValue;
        $detail->save();

        // Update data lokal (agar langsung tampil di UI)
        $this->jml_bahan[$bahanId] = $newValue;

        // Update juga di tabel pengajuan_details yang terkait
        $pembelian = PembelianBahan::find($pembelianId);
        if ($pembelian && $pembelian->pengajuan_id) {
            PengajuanDetails::where('pengajuan_id', $pembelian->pengajuan_id)
                ->where(function ($q) use ($detail) {
                    if ($detail->bahan_id) {
                        $q->where('bahan_id', $detail->bahan_id);
                    } else {
                        $q->where('nama_bahan', $detail->nama_bahan);
                    }
                })
                ->update(['jml_bahan' => $newValue]);
        }

        // Tutup mode edit
        $this->editingJmlId = null;

        // Notifikasi sukses
        session()->flash('success', 'Jumlah bahan berhasil diperbarui.');
    }


    public function updateStatusPembelian($pembelianId, $bahanId = null, $namaBahan = null)
    {
        // Ambil detail bahan berdasarkan pembelian_id
        $query = PembelianBahanDetails::where('pembelian_bahan_id', $pembelianId);

        if (!is_null($bahanId)) {
            $query->where('bahan_id', $bahanId);
        } elseif (!is_null($namaBahan)) {
            $query->where('nama_bahan', $namaBahan);
        }

        $bahan = $query->first();

        if ($bahan) {
            // Cari transaksi pembelian untuk mendapatkan pengajuan_id
            $pembelian = PembelianBahan::where('id', $pembelianId)->first();

            if ($pembelian) {
                // Toggle status_pembelian (1 = dicentang, 0 = tidak dicentang)
                $bahan->status_pembelian = $bahan->status_pembelian ? 0 : 1;
                $bahan->save();

                // Update status di PengajuanDetails hanya untuk pengajuan terkait
                // PengajuanDetails::where('pengajuan_id', $pembelian->pengajuan_id)
                //     ->where(function ($query) use ($bahan) {
                //         if ($bahan->bahan_id) {
                //             $query->where('bahan_id', $bahan->bahan_id);
                //         } else {
                //             $query->where('nama_bahan', $bahan->nama_bahan);
                //         }
                //     })
                //     ->update(['status_pembelian' => $bahan->status_pembelian]);

                PembelianBahanDetails::where('pembelian_bahan_id', $pembelian->id)
                    ->where(function ($query) use ($bahan) {
                        if ($bahan->bahan_id) {
                            $query->where('bahan_id', $bahan->bahan_id);
                        } else {
                            $query->where('nama_bahan', $bahan->nama_bahan);
                        }
                    })
                    ->update([
                        'status_pembelian' => $bahan->status_pembelian
                    ]);

                // Kirim Notifikasi WhatsApp hanya jika status_pembelian === 1
                if ($bahan->status_pembelian === 1) {
                    // Ambil kode_pengajuan dari tabel Pengajuan
                    $pengajuan = Pengajuan::where('id', $pembelian->pengajuan_id)->first();
                    $kode_pengajuan = $pengajuan ? $pengajuan->kode_pengajuan : '-';
                    if (is_null($bahan->nama_bahan) && $bahan->bahan_id) {
                        $namaBahanDariDB = Bahan::where('id', $bahan->bahan_id)->value('nama_bahan');
                        $bahanInfo = $namaBahanDariDB ?? $bahan->bahan_id;
                    } else {
                        $bahanInfo = $bahan->nama_bahan ?? $bahan->bahan_id;
                    }
                    $pengaju = User::where('id', $pembelian->pengaju)->first();

                    if ($pengaju && $pengaju->telephone) {
                        $targetPhone = $pengaju->telephone;
                        $recipientName = $pengaju->name;

                        $message = "Halo {$recipientName},\n\nPengajuan pembelian *$bahanInfo* dengan kode pengajuan *$kode_pengajuan* sudah dibeli dan berada di gudang.\n\nPesan Otomatis:\nhttps://inventory.beacontelemetry.com/\n\n";

                        // Dispatch Job
                        SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
                    }
                }
            }
        }
    }







    public function loadProduksi()
    {
        $pembelianBahan = PembelianBahan::with('pembelianBahanDetails')->find($this->pembelianBahanId);

        if ($pembelianBahan) {
            $ongkir = $pembelianBahan->ongkir ?? 0;
            $asuransi = $pembelianBahan->asuransi ?? 0;
            $layanan = $pembelianBahan->layanan ?? 0;
            $jasa_aplikasi = $pembelianBahan->jasa_aplikasi ?? 0;
            $ppn = $pembelianBahan->ppn ?? 0;
            $shipping_cost = $pembelianBahan->shipping_cost ?? 0;
            $full_amount_fee = $pembelianBahan->full_amount_fee ?? 0;
            $value_today_fee = $pembelianBahan->value_today_fee ?? 0;
            $shipping_cost_usd = $pembelianBahan->shipping_cost_usd ?? 0;
            $full_amount_fee_usd = $pembelianBahan->full_amount_fee_usd ?? 0;
            $value_today_fee_usd = $pembelianBahan->value_today_fee_usd ?? 0;

            $this->ongkir = $ongkir;
            $this->asuransi = $asuransi;
            $this->layanan = $layanan;
            $this->jasa_aplikasi = $jasa_aplikasi;
            $this->ppn = $ppn;
            $this->shipping_cost = $shipping_cost;
            $this->full_amount_fee = $full_amount_fee;
            $this->value_today_fee = $value_today_fee;
            $this->shipping_cost_usd = $shipping_cost_usd;
            $this->full_amount_fee_usd = $full_amount_fee_usd;
            $this->value_today_fee_usd = $value_today_fee_usd;

            foreach ($pembelianBahan->pembelianBahanDetails as $detail) {
                $decodedDetails = json_decode($detail->details, true);
                $decodedDetailsUSD = json_decode($detail->details_usd, true);

                $unitPrice = $decodedDetails['unit_price'] ?? 0;
                $unitPriceUSD = $decodedDetailsUSD['unit_price_usd'] ?? 0;
                $unitPriceUSDAset = $decodedDetailsUSD['unit_price_usd_aset'] ?? 0;

                $bahanKey = $detail->bahan_id ?? $detail->nama_bahan;
                $this->keterangan_pembayaran[$bahanKey] = $detail->keterangan_pembayaran ?? '';


                $this->pembelianBahanDetails[] = [
                    'bahan' => Bahan::find($detail->bahan_id),
                    'nama_bahan' => $detail->nama_bahan,
                    'pembelian_bahan_id' => $detail->pembelian_bahan_id,
                    'jml_bahan' => $detail->jml_bahan,
                    'qty_pengajuan' => $detail->qty_pengajuan,
                    'used_materials' => $detail->used_materials ?? 0,
                    'sub_total' => $detail->sub_total,
                    'details' => $decodedDetails,
                    'keterangan_pembayaran' => $this->keterangan_pembayaran[$bahanKey],
                    'spesifikasi' => $detail->spesifikasi ?? '',
                    'penanggungjawabaset' => $detail->penanggungjawabaset ?? '',
                    'alasan' => $detail->alasan ?? '',
                    'status_pembelian' => $detail->status_pembelian ?? '',
                ];
                $this->unit_price[$detail->bahan_id] = $unitPrice;
                $this->unit_price_aset[$detail->nama_bahan] = $unitPrice;
                $this->unit_price_usd[$detail->bahan_id] = $unitPriceUSD;
                // USD utk ASET IMPOR  🔥 (INI YG BELUM ADA)
                $this->unit_price_usd_aset[$detail->nama_bahan] = $unitPriceUSDAset;
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

        // $jml_bahan = isset($detail['jml_bahan']) ? intval($detail['jml_bahan']) : 0;
        $jml_bahan = floatval($this->jml_bahan[$itemId] ?? 0);
        // dd($jml_bahan);
        $unit_price = isset($this->unit_price[$itemId]) ? intval($this->unit_price[$itemId]) : 0;
        $unit_price_usd = isset($this->unit_price_usd[$itemId]) ? intval($this->unit_price_usd[$itemId]) : 0;

        $this->subtotals[$itemId] = $jml_bahan * $unit_price;
        // dd($this->subtotals[$itemId]);
        $this->subtotals_usd[$itemId] = $jml_bahan * $unit_price_usd;
        $this->calculateTotalHarga();
    }

    public function calculateTotalHarga()
    {
        $this->totalharga = array_sum($this->subtotals);
        $this->totalhargausd = array_sum($this->subtotals_usd);
    }

    public function formatToRupiah($item)
    {
        // Ambil nilai inputan mentah
        $rawValue = $this->{$item . '_raw'} ?? '0';

        if (strpos($item, 'usd') !== false) {
            // Jika dalam USD: Ubah koma ke titik, lalu parsing ke float
            $cleanValue = str_replace(['.', ','], ['', '.'], $rawValue);
            $this->$item = is_numeric($cleanValue) ? floatval($cleanValue) : 0;

            // Format kembali dengan 2 desimal
            $this->{$item . '_raw'} = number_format($this->$item, 2, ',', '.');
        } else {
            // Gunakan helper untuk parsing input rupiah (lokal atau dot-decimal)
            $parsedValue = $this->parseRupiahInput($rawValue);

            // Simpan ke variabel target
            $this->$item = $parsedValue;

            // Format kembali ke format Rupiah TANPA desimal
            $this->{$item . '_raw'} = number_format($parsedValue, 0, ',', '.');
        }

        // Recalculate totals if necessary
        if ($item === 'unit_price') {
            $this->calculateSubTotal($this->editingItemId);
        }

        $this->editingItemId = null;
    }

    public function formatToRupiahPPN($item)
    {
        // Ambil input mentah, misalnya dari 'ppn_raw'
        $rawValue = $this->{$item . '_raw'} ?? '0';

        // Deteksi apakah input sudah dalam format dot-decimal (contoh: 16227.50)
        if (preg_match('/^\d+(\.\d{1,2})?$/', $rawValue)) {
            $cleanValue = $rawValue;
        } else {
            // Format lokal ID: "2.066.698,20" → "2066698.20"
            $cleanValue = str_replace('.', '', $rawValue);     // Hapus ribuan
            $cleanValue = str_replace(',', '.', $cleanValue);  // Ubah koma ke titik
        }

        // Simpan ke variabel utama dengan presisi 2 desimal
        $this->$item = is_numeric($cleanValue) ? bcadd($cleanValue, '0', 2) : '0.00';

        // Format ulang untuk tampilan (Rupiah)
        $this->{$item . '_raw'} = number_format($this->$item, 2, ',', '.');

        // Hitung ulang subtotal jika item-nya adalah unit_price
        if ($item === 'unit_price') {
            $this->calculateSubTotal($this->editingItemId);
        }

        // Tutup mode edit
        $this->editingItemId = null;
    }




    public function formatToRupiahUSD($item)
    {
        $this->$item = intval(str_replace(['.', '$'], '', $this->{$item . '_raw'}));
        $this->{$item . '_raw'} = number_format($this->$item, 0, ',', '.');

        if ($item === 'unit_price') {
            $this->calculateSubTotal($this->editingItemId);
        }

        $this->editingItemId = null;
    }

    public function editItemPriceLocal($itemId)
    {
        $this->editingItemId = $itemId;
        if (isset($this->unit_price[$itemId])) {
            $this->unit_price_raw[$itemId] = $this->unit_price[$itemId];
        } else {
            $this->unit_price_raw[$itemId] = null;
        }
    }

    public function editItemPriceLocalAset($itemBahan)
    {
        $this->editingItemBahan = $itemBahan;
        $this->unit_price_raw[$itemBahan] = $this->unit_price_aset[$itemBahan] ?? 0;
    }

    public function editItemPriceImporAset($currency, $itemBahan)
    {
        // $this->editingItemBahan = $itemBahan;
        // $this->unit_price_usd_raw[$itemBahan] = $this->unit_price_usd_aset[$itemBahan] ?? 0;

        $itemBahan = $this->sanitizeKey($itemBahan);

        if ($currency === 'usd') {
            $this->editingItemBahan = 'usd_' . $itemBahan;
            $this->unit_price_usd_raw[$itemBahan] =
                $this->unit_price_usd_aset[$itemBahan] ?? 0;
        }

        if ($currency === 'idr') {
            $this->editingItemBahan = 'idr_' . $itemBahan;
            $this->unit_price_raw[$itemBahan] =
                $this->unit_price_aset[$itemBahan] ?? 0;
        }
    }

    public function editItem($item)
    {
        $this->editingItemId = $item;
        $this->{$item . '_raw'} = $this->$item ?? null;
    }
    public function editItemUSD($item)
    {
        $this->editingItemId = $item;
        $this->{$item . '_raw'} = $this->$item ?? null;
    }

    public function editItemAset($bahan)
    {
        $this->editingItemBahan = $bahan;
        $this->{$bahan . '_raw'} = $this->$bahan ?? null;
    }

    public function editItemUSDAset($bahan)
    {
        $this->editingItemBahan = $bahan;
        $this->{$bahan . '_raw'} = $this->$bahan ?? null;
    }

    public function sanitizeKey($string)
    {
        return preg_replace('/[^A-Za-z0-9_]/', '_', $string);
    }



    private function parseRupiahInput($input)
    {
        // Jika input sudah dalam format dot-decimal (contoh: 16227.50), langsung gunakan
        if (preg_match('/^\d+(\.\d{1,2})?$/', $input)) {
            return floatval($input);
        }

        // Jika input dalam format lokal (contoh: 2.066.698,20), ubah menjadi 2066698.20
        $cleaned = str_replace('.', '', $input);     // Hapus titik ribuan
        $cleaned = str_replace(',', '.', $cleaned);  // Ganti koma jadi titik desimal

        return is_numeric($cleaned) ? floatval($cleaned) : 0;
    }


    public function formatToRupiahPrice($itemId)
    {
        // Ambil input mentah dari user
        $rawValue = $this->unit_price_raw[$itemId] ?? '0';

        // Gunakan helper untuk parsing input
        $floatValue = $this->parseRupiahInput($rawValue);

        // Simpan ke variabel utama
        $this->unit_price[$itemId] = $floatValue;

        // Format kembali ke tampilan rupiah
        $this->unit_price_raw[$itemId] = number_format($floatValue, 2, ',', '.');

        // Hitung subtotal
        $this->calculateSubTotal($itemId);

        // Keluar dari mode edit
        $this->editingItemId = null;
    }


    public function formatToRupiahPriceAset($itemBahan)
    {
        if (!isset($this->unit_price_raw[$itemBahan])) {
            return;
        }

        // Ambil nilai mentah dari input user
        $rawValue = $this->unit_price_raw[$itemBahan];

        // Gunakan helper untuk parsing nilai angka
        $parsedValue = $this->parseRupiahInput($rawValue);

        // Simpan nilai ke array aset
        $this->unit_price_aset[$itemBahan] = $parsedValue;

        // Format ulang untuk tampilan tanpa desimal
        $this->unit_price_raw[$itemBahan] = number_format($parsedValue, 0, ',', '.');

        // Hitung ulang subtotal
        $this->calculateSubTotal($itemBahan);

        // Keluar dari mode edit
        $this->editingItemBahan = null;
    }

    public function formatToUSDPriceAset($itemBahan)
    {

        if (!isset($this->unit_price_usd_raw[$itemBahan])) {
            return;
        }

        $rawValue = $this->unit_price_usd_raw[$itemBahan];

        // ───── Parse agar aman ke float ─────
        // hapus spasi dan simbol $
        $clean = str_replace(['$', ' '], '', $rawValue);

        // jika formatnya 1.234,56 → ganti . jadi kosong, , jadi .
        if (strpos($clean, ',') !== false) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        }

        $parsedValue = floatval($clean);
        // dd($this->pembelianBahanId, $itemBahan, $parsedValue);
        // Simpan nilai float murni
        $this->unit_price_usd_aset[$itemBahan] = $parsedValue;

        // Format ulang ke 2 desimal INDONESIA style
        $this->unit_price_usd_raw[$itemBahan] = number_format(
            $parsedValue,
            2,
            ',',
            '.'
        );

        // 🔥 UPDATE DATABASE DI SINI
        PembelianBahanDetails::where('pembelian_bahan_id', $this->pembelianBahanId)
            ->where(function ($q) use ($itemBahan) {
                $q->where('nama_bahan', $itemBahan)
                    ->orWhereHas('dataBahan', function ($qq) use ($itemBahan) {
                        $qq->where('nama_bahan', $itemBahan);
                    });
            })
            ->update([
                'details_usd' => json_encode([
                    'unit_price_usd' => $parsedValue
                ])
            ]);
        // dd($this->pembelianBahanId, $itemBahan, $parsedValue, $this->unit_price_usd_aset[$itemBahan]);
        // Hitung ulang subtotal
        $this->calculateSubTotal($itemBahan);

        // Tutup mode edit
        $this->editingItemBahan = null;

        // dd($this->unit_price_usd);
    }


    public function formatToUSDPrice($itemId)
    {
        // Remove any non-numeric characters (except dot for decimals)
        $this->unit_price_usd[$itemId] = floatval(str_replace(['$', ','], '', $this->unit_price_usd_raw[$itemId]));

        // Format the value with dots as thousand separator and no decimals
        $this->unit_price_usd_raw[$itemId] = number_format($this->unit_price_usd[$itemId], 2, ',', '.');

        // Call a method to recalculate the sub total, or any other logic you need
        $this->calculateSubTotal($itemId);

        // Close the edit mode
        $this->editingItemId = null;
    }

    public function editItemPrice($currency, $itemId)
    {
        // Tentukan ID unik berdasarkan mata uang (USD atau IDR)
        if ($currency === 'usd') {
            $this->editingItemId = 'usd_' . $itemId; // USD Prefix
        } elseif ($currency === 'idr') {
            $this->editingItemId = 'idr_' . $itemId; // IDR Prefix
        }

        // Set data sementara
        if ($currency === 'idr' && isset($this->unit_price[$itemId])) {
            $this->unit_price_raw[$itemId] = $this->unit_price[$itemId];
        } elseif ($currency === 'usd' && isset($this->unit_price_usd[$itemId])) {
            $this->unit_price_usd_raw[$itemId] = $this->unit_price_usd[$itemId];
        } else {
            $this->unit_price_raw[$itemId] = null;
            $this->unit_price_usd_raw[$itemId] = null;
        }
    }

    public function changeKeterangan($itemId)
    {
        $requestedQty = $this->keterangan_pembayaran[$itemId] ?? 0;
    }

    public function changeKeteranganAset($itemBahan)
    {
        $requestedQty = $this->keterangan_pembayaran[$itemBahan] ?? 0;
    }

    // public function getCartItemsForStorage()
    // {
    //     $pembelianBahanDetails = [];
    //     $ongkir = [];

    //     // dd($this->keterangan_pembayaran);
    //     foreach ($this->pembelianBahanDetails as $item) {
    //         $bahanId = $item['bahan']->id ?? null;
    //         $unitPrice = $this->unit_price[$bahanId] ?? 0;
    //         $unitPriceUSD = $this->unit_price_usd[$bahanId] ?? 0;
    //         $subTotal = $item['jml_bahan'] * $unitPrice;
    //         $subTotalUSD = $item['jml_bahan'] * $unitPriceUSD;
    //         $keteranganPembayaran = $this->keterangan_pembayaran[$bahanId] ?? '';
    //         $pembelianBahanDetails[] = [
    //             'id' => $bahanId,
    //             'qty' => $this->qty[$bahanId] ?? 0,
    //             'jml_bahan' => $item['jml_bahan'],
    //             'qty_pengajuan' => $item['qty_pengajuan'],
    //             'details' => [
    //                 'unit_price' => $unitPrice,
    //             ],
    //             'details_usd' => [
    //                 'unit_price_usd' => $unitPriceUSD,
    //             ],
    //             'sub_total' => $subTotal,
    //             'sub_total_usd' => $subTotalUSD,
    //             'keterangan_pembayaran' => $keteranganPembayaran,
    //         ];
    //     }
    //     $ongkir = $this->ongkir;
    //     return $pembelianBahanDetails;
    //     return $ongkir;
    // }

    public function getCartItemsForStorage()
    {
        $pembelianBahanDetails = [];

        foreach ($this->pembelianBahanDetails as $item) {
            $bahanId = $item['bahan']->id ?? null;

            // Ambil data terkini dari Livewire state
            $jmlBahan = $this->jml_bahan[$bahanId] ?? ($item['jml_bahan'] ?? 0);
            $unitPrice = $this->unit_price[$bahanId] ?? ($item['details']['unit_price'] ?? 0);
            $unitPriceUSD = $this->unit_price_usd[$bahanId] ?? ($item['details_usd']['unit_price_usd'] ?? 0);
            $keteranganPembayaran = $this->keterangan_pembayaran[$bahanId] ?? ($item['keterangan_pembayaran'] ?? '');

            // Hitung ulang subtotal dari data terkini
            $subTotal = floatval($jmlBahan) * floatval($unitPrice);
            $subTotalUSD = floatval($jmlBahan) * floatval($unitPriceUSD);

            $pembelianBahanDetails[] = [
                'id' => $bahanId,
                'qty' => $this->qty[$bahanId] ?? 0,
                'jml_bahan' => $jmlBahan,
                'qty_pengajuan' => $item['qty_pengajuan'] ?? 0,
                'details' => [
                    'unit_price' => $unitPrice,
                ],
                'details_usd' => [
                    'unit_price_usd' => $unitPriceUSD,
                ],
                'sub_total' => $subTotal,
                'sub_total_usd' => $subTotalUSD,
                'keterangan_pembayaran' => $keteranganPembayaran,
            ];
        }

        return $pembelianBahanDetails;
    }


    public function getCartItemsForAset()
    {
        $pembelianBahanDetails = [];
        $ongkir = [];

        // dd($this->keterangan_pembayaran);
        foreach ($this->pembelianBahanDetails as $item) {
            $bahanId = $item['nama_bahan'] ?? null;

            $safeKey = $this->sanitizeKey($bahanId);

            // $unitPrice = $this->unit_price_aset[$bahanId] ?? 0;
            // $unitPriceUSD = $this->unit_price_usd[$bahanId] ?? 0;

            $unitPrice = $this->unit_price_aset[$safeKey] ?? 0;
            $unitPriceUSD = $this->unit_price_usd_aset[$safeKey] ?? 0;
            $qty = $this->qty[$safeKey] ?? ($item['jml_bahan'] ?? 0);
            $keteranganPembayaran = $this->keterangan_pembayaran[$safeKey] ?? '';

            // $subTotal = $item['jml_bahan'] * $unitPrice;
            // $subTotalUSD = $item['jml_bahan'] * $unitPriceUSD;

            $subTotal = $qty * $unitPrice;
            $subTotalUSD = $qty * $unitPriceUSD;

            // $keteranganPembayaran = $this->keterangan_pembayaran[$bahanId] ?? '';
            $pembelianBahanDetails[] = [
                'nama_bahan' => $bahanId,
                'qty' => $qty,
                'jml_bahan' => $qty,
                'details' => [
                    'unit_price' => $unitPrice,
                ],
                'details_usd' => [
                    'unit_price_usd_aset' => $unitPriceUSD,
                ],
                'sub_total' => $subTotal,
                'sub_total_usd' => $subTotalUSD,
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
            'ppn' => $this->ppn,
            'asuransi' => $this->asuransi,
            'layanan' => $this->layanan,
            'jasa_aplikasi' => $this->jasa_aplikasi,
            'shipping_cost' => $this->shipping_cost,
            'full_amount_fee' => $this->full_amount_fee,
            'value_today_fee' => $this->value_today_fee,
            'shipping_cost_usd' => $this->shipping_cost_usd,
            'full_amount_fee_usd' => $this->full_amount_fee_usd,
            'value_today_fee_usd' => $this->value_today_fee_usd,
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
