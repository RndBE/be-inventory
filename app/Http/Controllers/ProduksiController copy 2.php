<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Models\Bahan;
use App\Models\Produksi;
use App\Models\BahanJadi;
use App\Helpers\LogHelper;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use Illuminate\Http\Request;
use App\Models\DetailProduksi;
use App\Models\ProdukProduksi;
use App\Models\PurchaseDetail;
use App\Models\ProduksiDetails;
use App\Models\BahanJadiDetails;
use App\Models\BahanReturDetails;
use App\Models\BahanRusakDetails;
use App\Models\BahanSetengahjadi;
use App\Models\BahanKeluarDetails;
use Illuminate\Support\Facades\DB;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Support\Facades\Validator;

class ProduksiController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-proses-produksi', ['only' => ['index']]);
        $this->middleware('permission:selesai-proses-produksi', ['only' => ['updateStatus']]);
        $this->middleware('permission:tambah-proses-produksi', ['only' => ['create','store']]);
        $this->middleware('permission:edit-proses-produksi', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-proses-produksi', ['only' => ['destroy']]);
    }

    public function index()
    {
        return view('pages.produksis.index');
    }

    public function create()
    {
        $units = Unit::all();
        $produkProduksi = ProdukProduksi::all();
        return view('pages.produksis.create', compact('units', 'produkProduksi'));
    }

    public function store(Request $request)
    {
        try {
            //dd($request->all());
            $cartItems = json_decode($request->cartItems, true);
            $validator = Validator::make([
                'bahan_id' => $request->bahan_id,
                'jml_produksi' => $request->jml_produksi,
                'mulai_produksi' => $request->mulai_produksi,
                'jenis_produksi' => $request->jenis_produksi,
                'cartItems' => $cartItems
            ], [
                'bahan_id' => 'required',
                'jml_produksi' => 'required',
                'mulai_produksi' => 'required',
                'jenis_produksi' => 'required',
                'cartItems' => 'required|array',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $produk = ProdukProduksi::find($request->bahan_id);
            if ($produk) {
                $tujuan = $produk->dataBahan->nama_bahan;
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
            $produksi->kode_produksi = $kode_produksi;
            $produksi->bahan_id = $request->bahan_id;
            $produksi->jml_produksi = $request->jml_produksi;
            $produksi->mulai_produksi = $request->mulai_produksi;
            $produksi->jenis_produksi = $request->jenis_produksi;
            $produksi->status = 'Dalam proses';
            $produksi->save();

            // Simpan data ke Bahan Keluar
            $bahan_keluar = new BahanKeluar();
            $bahan_keluar->kode_transaksi = $kode_transaksi;
            $bahan_keluar->produksi_id = $produksi->id;
            $bahan_keluar->tgl_keluar = $tgl_keluar;
            $bahan_keluar->tujuan = 'Produksi '.$tujuan;
            $bahan_keluar->divisi = 'Produksi';
            $bahan_keluar->status = 'Belum disetujui';
            $bahan_keluar->save();

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
            }
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
        $produksi = Produksi::with(['produksiDetails.dataBahan', 'bahanKeluar'])->findOrFail($id);
        // if ($produksi->bahanKeluar->status != 'Disetujui') {
        //     return redirect()->back()->with('error', 'Produksi belum disetujui. Anda tidak dapat mengakses halaman tersebut.');
        // }
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
            dd($request->all());
            $cartItems = json_decode($request->produksiDetails, true) ?? [];
            $bahanRusak = json_decode($request->bahanRusak, true) ?? [];
            $bahanRetur = json_decode($request->bahanRetur, true) ?? [];
            $produksi = Produksi::findOrFail($id);
            $validator = Validator::make($request->all(), [
                'jml_produksi' => 'required',
                'mulai_produksi' => 'required',
                'jenis_produksi' => 'required',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Update production data
            $produksi->update([
                'jml_produksi' => $request->jml_produksi,
                'mulai_produksi' => $request->mulai_produksi,
                'jenis_produksi' => $request->jenis_produksi,
            ]);

            $produk = ProdukProduksi::find($request->bahan_id);
            if ($produk) {
                $tujuan = $produk->dataBahan->nama_bahan;
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

            // Kelompokkan item berdasarkan bahan_id dan jumlah
            $groupedItems = [];
            $totalQty = 0;  // Variabel untuk menghitung total qty

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
                $totalQty += $item['qty'];  // Tambahkan qty item ke total qty
            }

            // Cek apakah totalQty adalah 0; jika iya, hentikan proses
            if ($totalQty === 0) {
                return redirect()->back()->with('error', 'Tidak ada bahan keluar karena qty setiap item adalah 0.');
            }

            // Simpan data ke Bahan Keluar
            $bahan_keluar = new BahanKeluar();
            $bahan_keluar->kode_transaksi = $kode_transaksi;
            $bahan_keluar->produksi_id = $produksi->id;
            $bahan_keluar->tgl_keluar = $tgl_keluar;
            $bahan_keluar->tujuan = 'Produksi '.$tujuan;
            $bahan_keluar->divisi = 'Produksi';
            $bahan_keluar->status = 'Belum disetujui';
            $bahan_keluar->save();

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
            }


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

                            $produksiDetail->save();
                    }
                }
            }


            if (!empty($bahanRetur)) {
                $lastTransaction = BahanRetur::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
                if ($lastTransaction) {
                    $last_transaction_number = intval(substr($lastTransaction->kode_transaksi, 6));
                } else {
                    $last_transaction_number = 0;
                }
                $new_transaction_number = $last_transaction_number + 1;
                $formatted_number = str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT);
                $kode_transaksi = 'BR - ' . $formatted_number;

                $bahanReturRecord = BahanRetur::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                ]);

                foreach ($bahanRetur as $item) {
                    $bahan_id = $item['id'];
                    $qtyRetur = $item['qty'] ?? 0;
                    $unit_price = $item['unit_price'] ?? 0;
                    $sub_total = $qtyRetur * $unit_price;

                    BahanReturDetails::create([
                        'bahan_retur_id' => $bahanReturRecord->id,
                        'bahan_id' => $bahan_id,
                        'qty' => $qtyRetur,
                        'sisa' => $qtyRetur,
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
                                $detail['qty'] -= $qtyRetur;

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

                        $produksiDetail->used_materials -= $qtyRetur;

                            $produksiDetail->save();
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
            //dd($request->all());
            $produksi = Produksi::findOrFail($id);
            if ($produksi->status !== 'Selesai') {
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

                        LogHelper::success('Berhasil Menyelesaikan Produksi Produk Jadi!');
                        return redirect()->back()->with('success', 'Produksi Bahan Jadi telah selesai.');
                    } catch (\Exception $e) {
                        // Rollback jika ada kesalahan
                        DB::rollBack();
                        $errorMessage = $e->getMessage();
                        LogHelper::error($e->getMessage());
                        return redirect()->back()->with('error', "Gagal update status produksi.".$errorMessage);
                    }
                }
            }
            LogHelper::success('Berhasil Menyelesaikan Produksi!');
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

            // Menghapus semua bahan keluar yang terkait dengan produksi_id
            BahanKeluar::where('produksi_id', $produksi->id)->delete();

            // Menghapus produksi
            $produksi->delete();

            return redirect()->back()->with('success', 'Produksi dan semua bahan keluar terkait berhasil dihapus.');
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
