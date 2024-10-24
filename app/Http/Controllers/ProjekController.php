<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Models\Bahan;
use App\Models\Projek;
use App\Models\Produksi;
use App\Models\BahanJadi;
use App\Helpers\LogHelper;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use Illuminate\Http\Request;
use App\Models\ProjekDetails;
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

class ProjekController extends Controller
{
    public function index()
    {
        return view('pages.projek.index');
    }

    public function create()
    {
        $units = Unit::all();
        $produkProduksi = ProdukProduksi::all();
        return view('pages.projek.create', compact('units', 'produkProduksi'));
    }

    public function store(Request $request)
    {
        try {
            dd($request->all());
            $cartItems = json_decode($request->cartItems, true);
            $validator = Validator::make([
                'nama_projek' => $request->nama_projek,
                'jml_projek' => $request->jml_projek,
                'mulai_projek' => $request->mulai_projek,
                'cartItems' => $cartItems
            ], [
                'nama_projek' => 'required',
                'jml_projek' => 'required',
                'mulai_projek' => 'required',
                'cartItems' => 'required|array',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
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
            $bahan_keluar->tujuan = 'Projek '. $request->nama_projek;
            $bahan_keluar->divisi = 'Produksi';
            $bahan_keluar->status = 'Belum disetujui';
            $bahan_keluar->save();

            // Buat kombinasi kode transaksi di Produksi
            $lastTransactionProjek = Projek::orderByRaw('CAST(SUBSTRING(kode_projek, 7) AS UNSIGNED) DESC')->first();
            if ($lastTransactionProjek) {
                $last_transaction_number_projek = intval(substr($lastTransactionProjek->kode_projek, 6));
            } else {
                $last_transaction_number_projek = 0;
            }
            $new_transaction_number_projek = $last_transaction_number_projek + 1;
            $formatted_number_projek = str_pad($new_transaction_number_projek, 5, '0', STR_PAD_LEFT);
            $kode_projek = 'PR - ' . $formatted_number_projek;

            // Simpan data ke Produksi
            $projek = new Projek();
            $projek->bahan_keluar_id = $bahan_keluar->id;
            $projek->kode_projek = $kode_projek;
            $projek->nama_projek = $request->nama_projek;
            $projek->jml_projek = $request->jml_projek;
            $projek->mulai_projek = $request->mulai_projek;
            $projek->status = 'Konfirmasi';
            $projek->save();

            // Kelompokkan item berdasarkan bahan_id dan jumlah
            $groupedItems = [];
            foreach ($cartItems as $item) {
                if (!isset($groupedItems[$item['id']])) {
                    $groupedItems[$item['id']] = [
                        'qty' => 0,
                        'details' => $item['details'],
                        'sub_total' => 0,
                    ];
                }
                $groupedItems[$item['id']]['qty'] += $item['qty'];
                $groupedItems[$item['id']]['sub_total'] += $item['sub_total'];
            }

            // Simpan data ke Bahan Keluar Detail dan Produksi Detail
            foreach ($groupedItems as $bahan_id => $details) {
                BahanKeluarDetails::create([
                    'bahan_keluar_id' => $bahan_keluar->id,
                    'bahan_id' => $bahan_id,
                    'qty' => $details['qty'],
                    'details' => json_encode($details['details']),
                    'sub_total' => $details['sub_total'],
                ]);

                ProjekDetails::create([
                    'projek_id' => $projek->id,
                    'bahan_id' => $bahan_id,
                    'qty' => $details['qty'],
                    'details' => json_encode($details['details']),
                    'sub_total' => $details['sub_total'],
                ]);
            }
            session()->forget('cartItems');
            LogHelper::success('Berhasil Menambahkan Pengajuan Produksi!');
            return redirect()->back()->with('success', 'Berhasil Menambahkan Pengajuan Produksi!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menambahkan data: ' . $e->getMessage());
        }
    }

    public function edit(string $id)
    {
        $units = Unit::all();
        $bahanProduksi = Bahan::whereHas('jenisBahan', function ($query) {
            $query->where('nama', 'like', '%Produksi%');
        })->get();
        $projek = Projek::with(['projekDetails.dataBahan', 'bahanKeluar'])->findOrFail($id);
        if ($projek->bahanKeluar->status != 'Disetujui') {
            return redirect()->back()->with('error', 'Projek belum disetujui. Anda tidak dapat mengakses halaman tersebut.');
        }
        return view('pages.projek.edit', [
            'projekId' => $projek->id,
            'bahanProduksi' => $bahanProduksi,
            'projek' => $projek,
            'units' => $units,
            'id' => $id
        ]);
    }


    public function update(Request $request, $id)
    {
        try {
            dd($request->all());
            $cartItems = json_decode($request->produksiDetails, true) ?? [];
            $bahanRusak = json_decode($request->bahanRusak, true) ?? [];
            $produksi = Produksi::findOrFail($id);
            // $validator = Validator::make($request->all(), [
            //     'jml_produksi' => 'required',
            //     'mulai_produksi' => 'required',
            //     'jenis_produksi' => 'required',
            // ]);

            // if ($validator->fails()) {
            //     return redirect()->back()->withErrors($validator)->withInput();
            // }

            // Update production data
            // $produksi->update([
            //     'jml_produksi' => $request->jml_produksi,
            //     'mulai_produksi' => $request->mulai_produksi,
            //     'jenis_produksi' => $request->jenis_produksi,
            // ]);

            if (!empty($cartItems)) {
                foreach ($cartItems as $item) {
                    $bahan_id = $item['id'];
                    $qty = $item['qty'] ?? 0;
                    $sub_total = $item['sub_total'] ?? 0;
                    $details = $item['details'] ?? [];
                    $newUsedMaterials = $item['used_materials'] ?? 0;
                    $existingDetail = ProduksiDetails::where('produksi_id', $produksi->id)
                        ->where('bahan_id', $bahan_id)
                        ->first();

                    if ($existingDetail) {
                        $existingDetailsArray = json_decode($existingDetail->details, true) ?? [];
                        $totalQty = $existingDetail->qty;
                        $totalUsedMaterials = $existingDetail->used_materials;
                        foreach ($details as $newDetail) {
                            $found = false;
                            foreach ($existingDetailsArray as &$existingDetailItem) {
                                if ($existingDetailItem['kode_transaksi'] === $newDetail['kode_transaksi'] && $existingDetailItem['unit_price'] === $newDetail['unit_price']) {
                                    $existingDetailItem['qty'] += $newDetail['qty']; // Increase quantity in details
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $existingDetailsArray[] = $newDetail;
                            }
                            $totalQty += $newDetail['qty'];
                            $totalUsedMaterials += $newDetail['qty'];
                        }
                        $existingDetail->details = json_encode($existingDetailsArray);
                        $existingDetail->qty = $totalQty;
                        $existingDetail->used_materials = $totalUsedMaterials + $newUsedMaterials;
                        $existingDetail->sub_total += $sub_total;
                        $existingDetail->save();
                    } else {
                        ProduksiDetails::create([
                            'produksi_id' => $produksi->id,
                            'bahan_id' => $bahan_id,
                            'qty' => $qty,
                            'used_materials' => $newUsedMaterials,
                            'details' => json_encode($details),
                            'sub_total' => $sub_total,
                        ]);
                    }

                    foreach ($details as $newDetail) {
                        $purchaseDetail = PurchaseDetail::where('bahan_id', $bahan_id)
                            ->whereHas('purchase', function ($query) use ($newDetail) {
                                $query->where('kode_transaksi', $newDetail['kode_transaksi']);
                            })
                            ->where('unit_price', $newDetail['unit_price'])
                            ->whereHas('dataBahan', function ($query) {
                                $query->whereHas('jenisBahan', function ($query) {
                                    $query->where('nama', '!=', 'Produksi');
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
                            if ($newDetail['qty'] > $purchaseDetail->sisa) {
                                throw new \Exception('Permintaan qty melebihi sisa stok pada bahan: ' . $bahan_id);
                            }

                            $purchaseDetail->sisa -= $newDetail['qty'];
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
                    $qtyRusak = $item['qty'] ?? 0;
                    $unit_price = $item['unit_price'] ?? 0;
                    $sub_total = $qtyRusak * $unit_price;

                    BahanRusakDetails::create([
                        'bahan_rusak_id' => $bahanRusakRecord->id,
                        'bahan_id' => $bahan_id,
                        'qty' => $qtyRusak,
                        'sisa' => $qtyRusak,
                        'unit_price' => $unit_price,
                        'sub_total' => $sub_total,
                    ]);
                $produksiDetail = ProduksiDetails::where('produksi_id', $produksi->id)
                    ->where('bahan_id', $bahan_id)
                    ->first();

                    if ($produksiDetail) {
                        $existingDetailsArray = json_decode($produksiDetail->details, true) ?? [];

                        foreach ($existingDetailsArray as $key => &$detail) {
                            if ($detail['unit_price'] === $unit_price) {
                                $detail['qty'] -= $qtyRusak;

                                if ($detail['qty'] <= 0) {
                                    unset($existingDetailsArray[$key]);
                                }
                            }
                        }

                        $produksiDetail->details = json_encode(array_values($existingDetailsArray));

                        $newTotalQty = array_sum(array_column($existingDetailsArray, 'qty'));
                        $newSubTotal = array_sum(array_map(function ($detail) {
                            return $detail['qty'] * $detail['unit_price'];
                        }, $existingDetailsArray));

                        $produksiDetail->qty = $newTotalQty;
                        $produksiDetail->sub_total = $newSubTotal;

                        $produksiDetail->used_materials -= $qtyRusak;

                        if ($newTotalQty > 0) {
                            $produksiDetail->save();
                        } else {
                            $produksiDetail->delete();
                        }
                    }
            }
        }
        LogHelper::success('Berhasil Mengubah Detail Produksi!');
            return redirect()->back()->with('success', 'Produksi berhasil diperbarui!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try{

            $produksi = Produksi::findOrFail($id);
            // dd($produksi);
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
                        $bahanSetengahJadiDetail->produk_id = $produksi->produk_id;
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

                        LogHelper::success('Berhasil Menyelesaikan Produksi Produk Setengah Jadi!');
                        return redirect()->back()->with('success', 'Produksi telah selesai.');
                    } catch (\Exception $e) {
                        // Rollback jika ada kesalahan
                        DB::rollBack();
                        $errorMessage = $e->getMessage();
                        LogHelper::error($e->getMessage());
                        return redirect()->back()->with('error', "Gagal update status produksi.".$errorMessage);
                    }
                }

                // Kondisi untuk jenis produksi 'Bahan Jadi'
                // if ($produksi->jenis_produksi === 'Produk Jadi') {
                //     try {
                //         // Mulai transaksi database
                //         DB::beginTransaction();

                //         // Masukkan data ke dalam tabel bahan_jadi
                //         $bahanJadi = new BahanJadi();
                //         $bahanJadi->tgl_masuk = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                //         $bahanJadi->kode_transaksi = $produksi->kode_produksi;
                //         $bahanJadi->save();

                //         $produksiTotal = $produksi->produksiDetails->sum('sub_total');

                //         $bahanJadiDetail = new BahanJadiDetails();
                //         $bahanJadiDetail->bahan_jadi_id = $bahanJadi->id;
                //         $bahanJadiDetail->bahan_id = $produksi->bahan_id;
                //         $bahanJadiDetail->qty = $produksi->jml_produksi;
                //         $bahanJadiDetail->sisa = $produksi->jml_produksi;
                //         $bahanJadiDetail->unit_price = $produksiTotal / $produksi->jml_produksi;
                //         $bahanJadiDetail->sub_total = $produksiTotal;
                //         $bahanJadiDetail->save();

                //         // Jika semua penyimpanan berhasil, update status produksi menjadi "Selesai"
                //         $produksi->status = 'Selesai';
                //         $produksi->selesai_produksi = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                //         $produksi->save();

                //         // Commit transaksi
                //         DB::commit();

                //         LogHelper::success('Berhasil Menyelesaikan Produksi Produk Jadi!');
                //         return redirect()->back()->with('success', 'Produksi Bahan Jadi telah selesai.');
                //     } catch (\Exception $e) {
                //         // Rollback jika ada kesalahan
                //         DB::rollBack();
                //         $errorMessage = $e->getMessage();
                //         LogHelper::error($e->getMessage());
                //         return redirect()->back()->with('error', "Gagal update status produksi.".$errorMessage);
                //     }
                // }
            }
            return redirect()->back()->with('error', 'Produksi tidak bisa diupdate ke selesai.');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }



    public function destroy(string $id)
    {
        try{
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
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

}
