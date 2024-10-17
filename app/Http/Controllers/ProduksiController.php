<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Bahan;
use App\Models\Produksi;
use App\Models\BahanJadi;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use Illuminate\Http\Request;
use App\Models\DetailProduksi;
use App\Models\ProdukProduksi;
use App\Models\PurchaseDetail;
use App\Models\ProduksiDetails;
use App\Models\BahanJadiDetails;
use App\Models\BahanRusakDetails;
use App\Models\BahanSetengahjadi;
use App\Models\BahanKeluarDetails;
use Illuminate\Support\Facades\DB;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Support\Facades\Validator;

class ProduksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pages.produksis.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $units = Unit::all();
        $produkProduksi = ProdukProduksi::all();

        return view('pages.produksis.create', compact('units', 'produkProduksi'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            //dd($request->all());
            $cartItems = json_decode($request->cartItems, true);
            //validasi input
            $validator = Validator::make([
                'produk_id' => $request->produk_id,
                'jml_produksi' => $request->jml_produksi,
                'mulai_produksi' => $request->mulai_produksi,
                'jenis_produksi' => $request->jenis_produksi,
                'cartItems' => $cartItems
            ], [
                'produk_id' => 'required',
                'jml_produksi' => 'required',
                'mulai_produksi' => 'required',
                'jenis_produksi' => 'required',
                'cartItems' => 'required|array',
                'cartItems.*.id' => 'required|integer',
                'cartItems.*.qty' => 'required|integer|min:1',
                'cartItems.*.details' => 'required|array',
                'cartItems.*.sub_total' => 'required',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $produk = ProdukProduksi::find($request->produk_id);
            if ($produk) {
                $tujuan = $produk->nama_produk;
            } else {
                $tujuan = null;
            }
            $lastTransaction = BahanKeluar::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
            if ($lastTransaction) {
                $last_transaction_number = intval(substr($lastTransaction->kode_transaksi, 6));
            } else {
                $last_transaction_number = 0;
            }
            $new_transaction_number = $last_transaction_number + 1;
            $formatted_number = str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT);
            $kode_transaksi = 'KBK - ' . $formatted_number;
            $tgl_keluar = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            // Simpan data ke Bahan Keluar
            $bahan_keluar = new BahanKeluar();
            $bahan_keluar->kode_transaksi = $kode_transaksi;
            $bahan_keluar->tgl_keluar = $tgl_keluar;
            $bahan_keluar->tujuan = 'Produksi '.$tujuan;
            $bahan_keluar->divisi = 'Produksi';
            $bahan_keluar->status = 'Belum disetujui';
            $bahan_keluar->save();

            // Buat kombinasi kode transaksi di Produksi
            $lastTransactionProduksi = Produksi::orderByRaw('CAST(SUBSTRING(kode_produksi, 7) AS UNSIGNED) DESC')->first();
            if ($lastTransactionProduksi) {
                $last_transaction_number_produksi = intval(substr($lastTransactionProduksi->kode_produksi, 6));
            } else {
                $last_transaction_number_produksi = 0;
            }
            $new_transaction_number_produksi = $last_transaction_number_produksi + 1;
            $formatted_number_produksi = str_pad($new_transaction_number_produksi, 5, '0', STR_PAD_LEFT);
            $kode_produksi = 'PR - ' . $formatted_number_produksi;

            // Simpan data ke Produksi
            $produksi = new Produksi();
            $produksi->bahan_keluar_id = $bahan_keluar->id;
            $produksi->kode_produksi = $kode_produksi;
            $produksi->produk_id = $request->produk_id;
            $produksi->jml_produksi = $request->jml_produksi;
            $produksi->mulai_produksi = $request->mulai_produksi;
            $produksi->jenis_produksi = $request->jenis_produksi;
            $produksi->status = 'Konfirmasi';
            $produksi->save();

            // Kelompokkan item berdasarkan bahan_id dan jumlah
            $groupedItems = [];
            foreach ($cartItems as $item) {
                if (!isset($groupedItems[$item['id']])) {
                    $groupedItems[$item['id']] = [
                        'qty' => 0,
                        'jml_bahan' => 0,
                        'details' => $item['details'],
                        'sub_total' => 0,
                    ];
                }
                $groupedItems[$item['id']]['qty'] += $item['qty'];
                $groupedItems[$item['id']]['jml_bahan'] += $item['jml_bahan'];
                $groupedItems[$item['id']]['sub_total'] += $item['sub_total'];
            }

            // Simpan data ke Bahan Keluar Detail dan Produksi Detail
            foreach ($groupedItems as $bahan_id => $details) {
                BahanKeluarDetails::create([
                    'bahan_keluar_id' => $bahan_keluar->id,
                    'bahan_id' => $bahan_id,
                    'qty' => $details['qty'],
                    'jml_bahan' => $details['jml_bahan'],
                    'used_materials' => 0,
                    'details' => json_encode($details['details']),
                    'sub_total' => $details['sub_total'],
                ]);

                ProduksiDetails::create([
                    'produksi_id' => $produksi->id,
                    'bahan_id' => $bahan_id,
                    'qty' => $details['qty'],
                    'jml_bahan' => $details['jml_bahan'],
                    'used_materials' => 0,
                    'details' => json_encode($details['details']),
                    'sub_total' => $details['sub_total'],
                ]);
            }
            return redirect()->back()->with('success', 'Permintaan berhasil ditambahkan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menambahkan data: ' . $e->getMessage());
        }
    }

    public function show(string $id)
    {

    }

    public function edit(string $id)
    {
        $units = Unit::all();
        $bahanProduksi = Bahan::whereHas('jenisBahan', function ($query) {
            $query->where('nama', 'like', '%Produksi%');
        })->get();
        $produksi = Produksi::with(['produksiDetails.dataBahan', 'bahanKeluar'])->findOrFail($id);
        if ($produksi->bahanKeluar->status != 'Disetujui') {
            return redirect()->back()->with('error', 'Produksi belum disetujui. Anda tidak dapat mengakses halaman tersebut.');
        }

        return view('pages.produksis.edit', [
            'produksiId' => $produksi->id,
            'bahanProduksi' => $bahanProduksi,
            'produksi' => $produksi,
            'units' => $units,
            'id' => $id
        ]);
    }


    public function update(Request $request, $id)
    {
        try {
            //dd($request->all());
            $cartItems = json_decode($request->cartItems, true) ?? [];
            $bahanRusak = json_decode($request->bahanRusak, true) ?? [];

            // Find the existing production entry
            $produksi = Produksi::findOrFail($id);

            // Validate input
            $validator = Validator::make($request->all(), [
                'bahan_id' => 'required',
                'jml_produksi' => 'required',
                'mulai_produksi' => 'required',
                'jenis_produksi' => 'required',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Update production data
            $produksi->update([
                'bahan_id' => $request->bahan_id,
                'jml_produksi' => $request->jml_produksi,
                'mulai_produksi' => $request->mulai_produksi,
                'jenis_produksi' => $request->jenis_produksi,
            ]);

            // Process cartItems if available
            if (!empty($cartItems)) {
                foreach ($cartItems as $item) {
                    $bahan_id = $item['id'];
                    $qty = $item['qty'] ?? 0;
                    $sub_total = $item['sub_total'] ?? 0;
                    $details = $item['details'] ?? [];

                    // Check if there's an existing ProduksiDetails entry for this bahan_id
                    $existingDetail = ProduksiDetails::where('produksi_id', $produksi->id)
                        ->where('bahan_id', $bahan_id)
                        ->first();

                    if ($existingDetail) {
                        // Decode existing details
                        $existingDetailsArray = json_decode($existingDetail->details, true) ?? [];

                        // Initialize total quantity for this detail
                        $totalQty = $existingDetail->qty;

                        // Update quantities for matching kode_transaksi
                        foreach ($details as $newDetail) {
                            $found = false;

                            foreach ($existingDetailsArray as &$existingDetailItem) {
                                if ($existingDetailItem['kode_transaksi'] === $newDetail['kode_transaksi'] && $existingDetailItem['unit_price'] === $newDetail['unit_price']) {
                                    $existingDetailItem['qty'] += $newDetail['qty']; // Increase quantity in details
                                    $found = true;
                                    break;
                                }
                            }

                            // If not found, add as a new entry
                            if (!$found) {
                                $existingDetailsArray[] = $newDetail;
                            }

                            // Add newDetail qty to totalQty
                            $totalQty += $newDetail['qty'];
                        }

                        // Update the existing detail with new quantities
                        $existingDetail->details = json_encode($existingDetailsArray);
                        $existingDetail->qty = $totalQty; // Update the total qty
                        $existingDetail->sub_total += $sub_total; // Update subtotal
                        $existingDetail->save();
                    } else {
                        // If no existing detail, create a new one
                        ProduksiDetails::create([
                            'produksi_id' => $produksi->id,
                            'bahan_id' => $bahan_id,
                            'qty' => $qty, // Set initial qty
                            'details' => json_encode($details),
                            'sub_total' => $sub_total,
                        ]);
                    }

                    foreach ($details as $newDetail) {
                        $purchaseDetail = PurchaseDetail::where('bahan_id', $bahan_id)
                            ->whereHas('purchase', function ($query) use ($newDetail) {
                                $query->where('kode_transaksi', $newDetail['kode_transaksi']);
                            })
                            ->where('unit_price', $newDetail['unit_price']) // Pengecekan unit_price
                            ->whereHas('dataBahan', function ($query) {
                                $query->whereHas('jenisBahan', function ($query) {
                                    $query->where('nama', '!=', 'Produksi'); // Mengecek jenisBahan !== 'Produksi'
                                });
                            })
                            ->first();

                        $bahanSetengahjadiDetail = BahanSetengahjadiDetails::where('bahan_id', $bahan_id)
                            ->whereHas('bahanSetengahjadi', function ($query) use ($newDetail) {
                                $query->where('kode_transaksi', $newDetail['kode_transaksi']);
                            })
                            ->where('unit_price', $newDetail['unit_price']) // Pengecekan unit_price
                            ->whereHas('dataBahan', function ($query) {
                                $query->whereHas('jenisBahan', function ($query) {
                                    $query->where('nama', 'Produksi');
                                });
                            })
                            ->first();


                        if ($purchaseDetail) {
                            // Cek apakah permintaan qty melebihi sisa stok
                            if ($newDetail['qty'] > $purchaseDetail->sisa) {
                                throw new \Exception('Permintaan qty melebihi sisa stok pada bahan: ' . $bahan_id);
                            }

                            // Kurangi stok sesuai qty permintaan
                            $purchaseDetail->sisa -= $newDetail['qty'];

                            // Jika sisa stok kurang dari 0, set sisa menjadi 0
                            if ($purchaseDetail->sisa < 0) {
                                $purchaseDetail->sisa = 0;
                            }

                            $purchaseDetail->save();
                        }elseif ($bahanSetengahjadiDetail) {
                            // Cek apakah permintaan qty melebihi sisa stok
                            if ($newDetail['qty'] > $bahanSetengahjadiDetail->sisa) {
                                throw new \Exception('Permintaan qty melebihi sisa stok pada bahan: ' . $bahan_id);
                            }

                            // Kurangi stok sesuai qty permintaan
                            $bahanSetengahjadiDetail->sisa -= $newDetail['qty'];

                            // Jika sisa stok kurang dari 0, set sisa menjadi 0
                            if ($bahanSetengahjadiDetail->sisa < 0) {
                                $bahanSetengahjadiDetail->sisa = 0;
                            }

                            $bahanSetengahjadiDetail->save();
                        } else {
                            throw new \Exception('Purchase detail tidak ditemukan untuk bahan: ' . $bahan_id);
                        }
                    }
                }
            }

            // Save bahan rusak if available
            if (!empty($bahanRusak)) {
                $lastTransaction = BahanRusak::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
                if ($lastTransaction) {
                    $last_transaction_number = intval(substr($lastTransaction->kode_transaksi, 6));
                } else {
                    $last_transaction_number = 0;
                }
                $new_transaction_number = $last_transaction_number + 1;
                $formatted_number = str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT);
                $kode_transaksi = 'BR - ' . $formatted_number;

                $bahanRusakRecord = BahanRusak::create([
                    'tgl_masuk' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                ]);

                foreach ($bahanRusak as $item) {
                    $bahan_id = $item['id'];
                    $qtyRusak = $item['qty'] ?? 0; // Default to 0 if not set
                    $unit_price = $item['unit_price'] ?? 0; // Default to 0 if not set
                    $sub_total = $qtyRusak * $unit_price;

                    // Create entry in the bahan_rusak_details table
                    BahanRusakDetails::create([
                        'bahan_rusak_id' => $bahanRusakRecord->id,
                        'bahan_id' => $bahan_id,
                        'qty' => $qtyRusak,
                        'sisa' => $qtyRusak,
                        'unit_price' => $unit_price,
                        'sub_total' => $sub_total,
                    ]);
                // Update ProduksiDetails by subtracting the qty of rusak
                $produksiDetail = ProduksiDetails::where('produksi_id', $produksi->id)
                    ->where('bahan_id', $bahan_id)
                    ->first();

                    if ($produksiDetail) {
                        // Decode existing details (which is in JSON format)
                        $existingDetailsArray = json_decode($produksiDetail->details, true) ?? [];

                        foreach ($existingDetailsArray as $key => &$detail) {
                            if ($detail['unit_price'] === $unit_price) {
                                $detail['qty'] -= $qtyRusak; // Reduce qty based on rusak

                                // Remove detail if qty becomes 0
                                if ($detail['qty'] <= 0) {
                                    unset($existingDetailsArray[$key]); // Remove detail with qty 0
                                }
                            }
                        }

                        // Re-encode the updated details array
                        $produksiDetail->details = json_encode(array_values($existingDetailsArray)); // Reindex array keys and save

                        // Recalculate the total qty and sub_total from details
                        $newTotalQty = array_sum(array_column($existingDetailsArray, 'qty'));
                        $newSubTotal = array_sum(array_map(function ($detail) {
                            return $detail['qty'] * $detail['unit_price'];
                        }, $existingDetailsArray));

                        $produksiDetail->qty = $newTotalQty; // Update total quantity
                        $produksiDetail->sub_total = $newSubTotal; // Update subtotal

                        // Save changes if there are remaining details or qty
                        if ($newTotalQty > 0) {
                            $produksiDetail->save();
                        } else {
                            // Delete the produksi detail if total qty is 0
                            $produksiDetail->delete();
                        }
                    }
            }
        }

            return redirect()->back()->with('success', 'Produksi berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function updateStatus(Request $request, $id)
    {
        //dd($request->all());
        $produksi = Produksi::findOrFail($id);
        if ($produksi->bahanKeluar->status === 'Disetujui' && $produksi->status !== 'Selesai') {
            // Proses update berdasarkan jenis produksi
            if ($produksi->jenis_produksi === 'Produk Setengah Jadi') {
                try {
                    // Mulai transaksi database
                    DB::beginTransaction();

                    // Masukkan data ke dalam tabel bahan_setengahjadi
                    $bahanSetengahJadi = new BahanSetengahjadi();
                    $bahanSetengahJadi->tgl_masuk = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                    $bahanSetengahJadi->kode_transaksi = $produksi->kode_produksi;
                    $bahanSetengahJadi->save();

                    $produksiTotal = $produksi->produksiDetails->sum('sub_total');

                    $bahanSetengahJadiDetail = new BahanSetengahjadiDetails();
                    $bahanSetengahJadiDetail->bahan_setengahjadi_id = $bahanSetengahJadi->id;
                    $bahanSetengahJadiDetail->bahan_id = $produksi->bahan_id;
                    $bahanSetengahJadiDetail->qty = $produksi->jml_produksi;
                    $bahanSetengahJadiDetail->sisa = $produksi->jml_produksi;
                    $bahanSetengahJadiDetail->unit_price = $produksiTotal / $produksi->jml_produksi;
                    $bahanSetengahJadiDetail->sub_total = $produksiTotal;
                    $bahanSetengahJadiDetail->save();

                    // Jika semua penyimpanan berhasil, update status produksi menjadi "Selesai"
                    $produksi->status = 'Selesai';
                    $produksi->selesai_produksi = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                    $produksi->save();

                    // Commit transaksi
                    DB::commit();

                    // Redirect dengan pesan sukses
                    return redirect()->back()->with('success', 'Produksi telah selesai.');
                } catch (\Exception $e) {
                    // Rollback jika ada kesalahan
                    DB::rollBack();
                    $errorMessage = $e->getMessage();
                    return redirect()->back()->with('error', "Gagal update status produksi.".$errorMessage);
                }
            }

            // Kondisi untuk jenis produksi 'Bahan Jadi'
            if ($produksi->jenis_produksi === 'Produk Jadi') {
                try {
                    // Mulai transaksi database
                    DB::beginTransaction();

                    // Masukkan data ke dalam tabel bahan_jadi
                    $bahanJadi = new BahanJadi();
                    $bahanJadi->tgl_masuk = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                    $bahanJadi->kode_transaksi = $produksi->kode_produksi;
                    $bahanJadi->save();

                    $produksiTotal = $produksi->produksiDetails->sum('sub_total');

                    $bahanJadiDetail = new BahanJadiDetails();
                    $bahanJadiDetail->bahan_jadi_id = $bahanJadi->id;
                    $bahanJadiDetail->bahan_id = $produksi->bahan_id;
                    $bahanJadiDetail->qty = $produksi->jml_produksi;
                    $bahanJadiDetail->sisa = $produksi->jml_produksi;
                    $bahanJadiDetail->unit_price = $produksiTotal / $produksi->jml_produksi;
                    $bahanJadiDetail->sub_total = $produksiTotal;
                    $bahanJadiDetail->save();

                    // Jika semua penyimpanan berhasil, update status produksi menjadi "Selesai"
                    $produksi->status = 'Selesai';
                    $produksi->selesai_produksi = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                    $produksi->save();

                    // Commit transaksi
                    DB::commit();

                    // Redirect dengan pesan sukses
                    return redirect()->back()->with('success', 'Produksi Bahan Jadi telah selesai.');
                } catch (\Exception $e) {
                    // Rollback jika ada kesalahan
                    DB::rollBack();
                    $errorMessage = $e->getMessage();
                    return redirect()->back()->with('error', "Gagal update status produksi.".$errorMessage);
                }
            }
        }
        return redirect()->back()->with('error', 'Produksi tidak bisa diupdate ke selesai.');
    }



    public function destroy(string $id)
    {
        $produksi = Produksi::find($id);
        if (!$produksi) {
            return redirect()->back()->with('gagal', 'Produksi tidak ditemukan.');
        }
        if ($produksi->status !== 'Konfirmasi') {
            return redirect()->back()->with('gagal', 'Produksi hanya dapat dihapus jika statusnya "Konfirmasi".');
        }
        $bahanKeluar = BahanKeluar::find($produksi->bahan_keluar_id);
        $produksi->delete();
        if ($bahanKeluar) {
            $bahanKeluar->delete();
        }
        return redirect()->back()->with('success', 'Produksi dan bahan keluar terkait berhasil dihapus.');
    }

}
