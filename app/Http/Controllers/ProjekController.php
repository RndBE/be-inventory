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

            $tujuan = $request->nama_projek;

            // Create transaction code for BahanKeluar
            $lastTransaction = BahanKeluar::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number = ($lastTransaction ? intval(substr($lastTransaction->kode_transaksi, 6)) : 0) + 1;
            $kode_transaksi = 'KBK - ' . str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT);
            $tgl_keluar = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            $bahan_keluar = BahanKeluar::create([
                'kode_transaksi' => $kode_transaksi,
                'tgl_keluar' => $tgl_keluar,
                'tujuan' => 'Produksi ' . $tujuan,
                'divisi' => 'Produksi',
                'status' => 'Belum disetujui'
            ]);

            // Create transaction code for Projek
            $lastTransactionProduksi = Projek::orderByRaw('CAST(SUBSTRING(kode_projek, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number_produksi = ($lastTransactionProduksi ? intval(substr($lastTransactionProduksi->kode_projek, 6)) : 0) + 1;
            $kode_produksi = 'PR - ' . str_pad($new_transaction_number_produksi, 5, '0', STR_PAD_LEFT);

            $produksi = Projek::create([
                'bahan_keluar_id' => $bahan_keluar->id,
                'kode_projek' => $kode_produksi,
                'nama_projek' => $request->nama_projek,
                'jml_projek' => $request->jml_projek,
                'mulai_projek' => $request->mulai_projek,
                'status' => 'Konfirmasi'
            ]);

            // Group items by bahan_id
            $groupedItems = [];
            foreach ($cartItems as $item) {
                $itemId = $item['id'];
                $groupedItems[$itemId] = [
                    'qty' => $item['qty'],
                    'details' => $item['details'] ?? [],
                    'sub_total' => $item['sub_total'] ?? 0,
                ];
            }

            // Save items to BahanKeluarDetails and ProjekDetails
            foreach ($groupedItems as $bahan_id => $details) {
                BahanKeluarDetails::create([
                    'bahan_keluar_id' => $bahan_keluar->id,
                    'bahan_id' => $bahan_id,
                    'qty' => $details['qty'],
                    'jml_bahan' => 0,
                    'used_materials' => 0,
                    'details' => json_encode($details['details']),
                    'sub_total' => $details['sub_total'],
                ]);

                ProjekDetails::create([
                    'projek_id' => $produksi->id,
                    'bahan_id' => $bahan_id,
                    'qty' => $details['qty'],
                    'details' => json_encode($details['details']),
                    'sub_total' => $details['sub_total'],
                ]);
            }
            $request->session()->forget('cartItems');
            LogHelper::success('Berhasil Menambahkan Pengajuan Projek!');
            return redirect()->back()->with('success', 'Berhasil Menambahkan Pengajuan Projek!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menambahkan data: ' . $e->getMessage());
        }
    }


    public function edit(string $id)
    {
        $units = Unit::all();
        $bahanProjek = Bahan::whereHas('jenisBahan', function ($query) {
            $query->where('nama', 'like', '%Produksi%');
        })->get();
        $projek = Projek::with(['projekDetails.dataBahan', 'bahanKeluar'])->findOrFail($id);
        if ($projek->bahanKeluar->status != 'Disetujui') {
            return redirect()->back()->with('error', 'Projek belum disetujui. Anda tidak dapat mengakses halaman tersebut.');
        }
        return view('pages.projek.edit', [
            'projekId' => $projek->id,
            'bahanProjek' => $bahanProjek,
            'projek' => $projek,
            'units' => $units,
            'id' => $id
        ]);
    }


    public function update(Request $request, $id)
    {
        try {
            dd($request->all());
            $cartItems = json_decode($request->projekDetails, true) ?? [];
            $bahanRusak = json_decode($request->bahanRusak, true) ?? [];
            $projek = Projek::findOrFail($id);

            if (!empty($cartItems)) {
                foreach ($cartItems as $item) {
                    $bahan_id = $item['id'];
                    $qty = $item['qty'] ?? 0;
                    $sub_total = $item['sub_total'] ?? 0;
                    $details = $item['details'] ?? [];
                    $existingDetail = ProjekDetails::where('projek_id', $projek->id)
                        ->where('bahan_id', $bahan_id)
                        ->first();

                    if ($existingDetail) {
                        $existingDetailsArray = json_decode($existingDetail->details, true) ?? [];
                        $totalQty = $existingDetail->qty;
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
                        }
                        $existingDetail->details = json_encode($existingDetailsArray);
                        $existingDetail->qty = $totalQty;
                        $existingDetail->sub_total += $sub_total;
                        $existingDetail->save();
                    } else {
                        ProjekDetails::create([
                            'projek_id' => $projek->id,
                            'bahan_id' => $bahan_id,
                            'qty' => $qty,
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
                $projekDetail = ProjekDetails::where('projek_id', $projek->id)
                    ->where('bahan_id', $bahan_id)
                    ->first();

                    if ($projekDetail) {
                        $existingDetailsArray = json_decode($projekDetail->details, true) ?? [];

                        foreach ($existingDetailsArray as $key => &$detail) {
                            if ($detail['unit_price'] === $unit_price) {
                                $detail['qty'] -= $qtyRusak;

                                if ($detail['qty'] <= 0) {
                                    unset($existingDetailsArray[$key]);
                                }
                            }
                        }

                        $projekDetail->details = json_encode(array_values($existingDetailsArray));

                        $newTotalQty = array_sum(array_column($existingDetailsArray, 'qty'));
                        $newSubTotal = array_sum(array_map(function ($detail) {
                            return $detail['qty'] * $detail['unit_price'];
                        }, $existingDetailsArray));

                        $projekDetail->qty = $newTotalQty;
                        $projekDetail->sub_total = $newSubTotal;

                        if ($newTotalQty > 0) {
                            $projekDetail->save();
                        } else {
                            $projekDetail->delete();
                        }
                    }
            }
        }
        LogHelper::success('Berhasil Mengubah Detail Projek!');
            return redirect()->back()->with('success', 'Projek berhasil diperbarui!');
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
            $projek = Projek::find($id);
            //dd($projek);
            if (!$projek) {
                return redirect()->back()->with('gagal', 'Projek tidak ditemukan.');
            }
            if ($projek->status !== 'Konfirmasi') {
                return redirect()->back()->with('gagal', 'Projek hanya dapat dihapus jika statusnya "Konfirmasi".');
            }
            $bahanKeluar = BahanKeluar::find($projek->bahan_keluar_id);
            $projek->delete();
            if ($bahanKeluar) {
                $bahanKeluar->delete();
            }
            return redirect()->back()->with('success', 'Projek dan bahan keluar terkait berhasil dihapus.');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

}
