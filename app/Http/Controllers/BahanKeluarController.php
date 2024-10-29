<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Bahan;
use App\Models\Projek;
use App\Models\StokRnd;
use App\Models\Produksi;
use App\Helpers\LogHelper;
use App\Models\BahanKeluar;
use App\Models\StokProduksi;
use Illuminate\Http\Request;
use App\Models\ProjekDetails;
use App\Models\PurchaseDetail;
use App\Models\ProduksiDetails;
use App\Models\BahanKeluarDetails;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Support\Facades\Validator;

class BahanKeluarController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-bahan-keluar', ['only' => ['index']]);
        $this->middleware('permission:detail-bahan-keluar', ['only' => ['show']]);
        $this->middleware('permission:tambah-bahan-keluar', ['only' => ['create','store']]);
        $this->middleware('permission:edit-bahan-keluar', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-bahan-keluar', ['only' => ['destroy']]);
    }

    public function index()
    {
        $bahan_keluars = BahanKeluar::with('bahanKeluarDetails')->get();
        return view('pages.bahan-keluars.index', compact('bahan_keluars'));
    }

    public function create()
    {
        return view('pages.bahan-keluars.create');
    }

    public function show(string $id)
    {
        $bahankeluar = BahanKeluar::with('bahanKeluarDetails.dataBahan.dataUnit')->findOrFail($id); // Mengambil detail pembelian
        return view('pages.bahan-keluars.show', [
            'kode_transaksi' => $bahankeluar->kode_transaksi,
            'tgl_keluar' => $bahankeluar->tgl_keluar,
            'divisi' => $bahankeluar->divisi,
            'bahanKeluarDetails' => $bahankeluar->bahanKeluarDetails,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => 'required',
        ]);

        try {
            //dd($request->all());
            $data = BahanKeluar::find($id);
            $details = BahanKeluarDetails::where('bahan_keluar_id', $id)->get();

            $pendingStockReductions = [];

            if ($validated['status'] === 'Disetujui') {
                $tgl_keluar = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                $data->tgl_keluar = $tgl_keluar;



                foreach ($details as $detail) {
                    $transactionDetails = json_decode($detail->details, true) ?? [];
                    if (empty($transactionDetails)) {
                        if ($data->produksi_id) {
                            // Cek apakah bahan_id sudah ada di ProduksiDetails
                            $existingDetail = ProduksiDetails::where('produksi_id', $data->produksi_id)
                                ->where('bahan_id', $detail->bahan_id)
                                ->first();

                            // Jika belum ada, buat entri baru
                            if (!$existingDetail) {
                                ProduksiDetails::create([
                                    'produksi_id' => $data->produksi_id,
                                    'bahan_id' => $detail->bahan_id,
                                    'qty' => 0, // Set qty to 0 if there are no transaction details
                                    'jml_bahan' => $detail->jml_bahan,
                                    'used_materials' => 0,
                                    'details' => json_encode([]), // Set details as an empty array
                                    'sub_total' => 0, // Set sub_total to 0 if details are null or empty
                                ]);
                            }
                            continue;
                        }
                    }
                    //dd($transactionDetails);
                    if (is_array($transactionDetails)) {
                        foreach ($transactionDetails as $transaksiDetail) {
                            $setengahJadiDetail = BahanSetengahjadiDetails::where('bahan_id', $detail->bahan_id)
                            ->whereHas('bahanSetengahjadi', function ($query) use ($transaksiDetail) {
                                $query->where('kode_transaksi', $transaksiDetail['kode_transaksi']);
                            })
                            ->where('unit_price', $transaksiDetail['unit_price'])
                            ->first();

                            if ($setengahJadiDetail) {
                                if ($transaksiDetail['qty'] > $setengahJadiDetail->sisa) {
                                    throw new \Exception('Tolak pengajuan, Stok bahan setengah jadi tidak cukup!');
                                }

                                $pendingStockReductions[] = [
                                    'detail' => $setengahJadiDetail,
                                    'qty' => $transaksiDetail['qty'],
                                ];

                                $setengahJadiDetail->sisa -= $transaksiDetail['qty'];
                                if ($setengahJadiDetail->sisa < 0) {
                                    $setengahJadiDetail->sisa = 0;
                                }
                                $setengahJadiDetail->save();
                            } else {
                                $purchaseDetail = PurchaseDetail::where('bahan_id', $detail->bahan_id)
                                    ->whereHas('purchase', function ($query) use ($transaksiDetail) {
                                        $query->where('kode_transaksi', $transaksiDetail['kode_transaksi']);
                                    })
                                    ->where('unit_price', $transaksiDetail['unit_price'])
                                    ->first();

                                if ($purchaseDetail) {
                                    if ($transaksiDetail['qty'] > $purchaseDetail->sisa) {
                                        throw new \Exception('Tolak pengajuan, Lakukan pengajuan bahan kembali!');
                                    }
                                    if ($purchaseDetail->sisa <= 0) {
                                        throw new \Exception('Tidak dapat mengubah status karena sisa bahan sudah habis.');
                                    }
                                    $pendingStockReductions[] = [
                                        'detail' => $purchaseDetail,
                                        'qty' => $transaksiDetail['qty'],
                                    ];

                                    $purchaseDetail->sisa -= $transaksiDetail['qty'];
                                    if ($purchaseDetail->sisa < 0) {
                                        $purchaseDetail->sisa = 0;
                                    }
                                }
                            }

                            // Periksa jika produksi_id ada dan update ProduksiDetails
                            if ($data->produksi_id) {
                                $produksiDetail = ProduksiDetails::where('produksi_id', $data->produksi_id)
                                    ->where('bahan_id', $detail->bahan_id)
                                    ->first();

                                if ($produksiDetail) {
                                    // Tambahkan qty pada entri yang ada dan perbarui `details`
                                    $produksiDetail->qty += $transaksiDetail['qty'];
                                    $produksiDetail->used_materials += $transaksiDetail['qty'];

                                    // Hitung sub_total tambahan berdasarkan qty dan unit_price transaksiDetail
                                    $newSubTotal = $transaksiDetail['qty'] * $transaksiDetail['unit_price'];
                                    $produksiDetail->sub_total += $newSubTotal;  // Tambahkan sub_total baru ke sub_total yang sudah ada

                                    // Decode existing details
                                    $existingDetails = json_decode($produksiDetail->details, true) ?? [];
                                    $found = false;

                                    // Cek apakah ada detail dengan kode_transaksi dan unit_price yang sama
                                    foreach ($existingDetails as &$existingDetail) {
                                        if ($existingDetail['kode_transaksi'] === $transaksiDetail['kode_transaksi'] &&
                                            $existingDetail['unit_price'] === $transaksiDetail['unit_price']) {
                                            // Jika ditemukan, tambahkan qty pada detail tersebut
                                            $existingDetail['qty'] += $transaksiDetail['qty'];
                                            $found = true;
                                            break;
                                        }
                                    }

                                    // Jika tidak ditemukan, tambahkan transaksiDetail sebagai entry baru
                                    if (!$found) {
                                        $existingDetails[] = $transaksiDetail;
                                    }

                                    // Simpan kembali `details` setelah diperbarui
                                    $produksiDetail->details = json_encode($existingDetails);
                                    $produksiDetail->save();
                                } else {
                                    // Buat entri baru jika produksi_id dan bahan_id belum ada
                                    $newSubTotal = $transaksiDetail['qty'] * $transaksiDetail['unit_price'];  // Hitung sub_total baru
                                    ProduksiDetails::create([
                                        'produksi_id' => $data->produksi_id,
                                        'bahan_id' => $detail->bahan_id,
                                        'qty' => $transaksiDetail['qty'],
                                        'jml_bahan' => $detail->jml_bahan,
                                        'used_materials' => $transaksiDetail['qty'],
                                        'details' => json_encode([$transaksiDetail]),  // Mulai dengan array berisi transaksi pertama
                                        'sub_total' => $newSubTotal,  // Set nilai sub_total awal
                                    ]);
                                }
                            }
                        }
                    }
                }

                // Kurangi stok
                foreach ($pendingStockReductions as $reduction) {
                    $reduction['detail']->sisa -= $reduction['qty'];
                    if ($reduction['detail']->sisa < 0) {
                        $reduction['detail']->sisa = 0;
                    }
                    $reduction['detail']->save();
                }
            }

            if ($validated['status'] === 'Ditolak') {
                $data->tgl_keluar = null;

                // Update status Produksi atau Projek jika ditolak
                if ($data->produksis) {
                    $produksi = Produksi::where('bahan_keluar_id', $id)->first();
                    if ($produksi) {
                        $produksi->status = 'Ditolak';
                        $produksi->save();
                    }
                } elseif ($data->projek) {
                    $projek = Projek::where('bahan_keluar_id', $id)->first();
                    if ($projek) {
                        $projek->status = 'Ditolak';
                        $projek->save();
                    }
                }
            }

            $data->status = $validated['status'];
            $data->save();

            LogHelper::success('Berhasil Mengubah Status Bahan Keluar!');
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorColumn = '';
            if (strpos($errorMessage, 'tgl_keluar') !== false) {
                $errorColumn = 'tgl_keluar';
            } elseif (strpos($errorMessage, 'status') !== false) {
                $errorColumn = 'status';
            }
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', "Terjadi kesalahan pada kolom: $errorColumn. Pesan error: $errorMessage");
        }

        return redirect()->route('bahan-keluars.index')->with('success', 'Status berhasil diubah.');
    }



    public function destroy(string $id)
    {
        try{
            $data = BahanKeluar::find($id);
            if (!$data) {
                return redirect()->back()->with('gagal', 'Transaksi tidak ditemukan.');
            }
            $data->delete();
            LogHelper::success('Berhasil Menghapus Pengajuan Bahan Keluar!');
            return redirect()->route('bahan-keluars.index')->with('success', 'Berhasil Menghapus Pengajuan Bahan Keluar!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
