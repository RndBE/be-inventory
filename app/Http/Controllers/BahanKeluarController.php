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
            $data = BahanKeluar::find($id);
            $details = BahanKeluarDetails::where('bahan_keluar_id', $id)->get();

            $pendingStockReductions = [];
            $groupedDetails = []; // Pastikan ini diinisialisasi

            if ($validated['status'] === 'Disetujui') {
                $tgl_keluar = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                $data->tgl_keluar = $tgl_keluar;

                foreach ($details as $detail) {
                    $transactionDetails = json_decode($detail->details, true) ?? [];

                    // Jika tidak ada transactionDetails, lanjutkan
                    if (empty($transactionDetails)) {
                        if ($data->produksi_id) {
                            // Check if the bahan_id already exists in ProduksiDetails
                            $existingDetail = ProduksiDetails::where('produksi_id', $data->produksi_id)
                            ->where('bahan_id', $detail->bahan_id)
                            ->first();

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
                             // Continue to the next detail
                        }
                        elseif ($data->projek_id) {
                            $existingDetail = ProjekDetails::where('projek_id', $data->projek_id)
                                ->where('bahan_id', $detail->bahan_id)
                                ->first();

                            if (!$existingDetail) {
                                ProjekDetails::create([
                                    'projek_id' => $data->projek_id,
                                    'bahan_id' => $detail->bahan_id,
                                    'qty' => 0,
                                    'details' => json_encode([]),
                                    'sub_total' => 0,
                                ]);
                            }
                        }
                        continue;
                    }

                    if (is_array($transactionDetails)) {
                        $groupedDetails = [];
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

                                $unitPrice = $transaksiDetail['unit_price'];
                                if (isset($groupedDetails[$unitPrice])) {
                                    // Jika harga satuan sudah ada, tingkatkan qty
                                    $groupedDetails[$unitPrice]['qty'] += $transaksiDetail['qty'];
                                } else {
                                    // Buat entri baru jika harga satuan belum ada
                                    $groupedDetails[$unitPrice] = [
                                        'qty' => $transaksiDetail['qty'],
                                        'unit_price' => $unitPrice,
                                    ];
                                }

                                $setengahJadiDetail->sisa -= $transaksiDetail['qty'];
                                $setengahJadiDetail->sisa = max(0, $setengahJadiDetail->sisa);
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

                                    $unitPrice = $transaksiDetail['unit_price'];
                                    if (isset($groupedDetails[$unitPrice])) {
                                        // Jika harga satuan sudah ada, tingkatkan qty
                                        $groupedDetails[$unitPrice]['qty'] += $transaksiDetail['qty'];
                                    } else {
                                        // Buat entri baru jika harga satuan belum ada
                                        $groupedDetails[$unitPrice] = [
                                            'qty' => $transaksiDetail['qty'],
                                            'unit_price' => $unitPrice,
                                        ];
                                    }

                                    $purchaseDetail->sisa -= $transaksiDetail['qty'];
                                    $purchaseDetail->sisa = max(0, $purchaseDetail->sisa);
                                    $purchaseDetail->save();
                                }
                            }
                        }

                        // Update ProduksiDetails setelah selesai menghitung
                        if ($data->produksi_id) {
                            foreach ($groupedDetails as $unitPrice => $qty) {
                                $produksiDetail = ProduksiDetails::where('produksi_id', $data->produksi_id)
                                    ->where('bahan_id', $detail->bahan_id)
                                    ->first();

                                $detailsToSave = [
                                    'qty' => $qty['qty'],
                                    'unit_price' => $unitPrice,
                                ];

                                if ($produksiDetail) {
                                    $produksiDetail->qty += $qty['qty'];
                                    $produksiDetail->used_materials += $qty['qty'];
                                    $produksiDetail->sub_total += $qty['qty'] * $unitPrice;
                                    $produksiDetail->details = json_encode(array_values($groupedDetails)); // Gunakan array_values untuk mengubah menjadi indexed array
                                    $produksiDetail->save();
                                } else {
                                    ProduksiDetails::create([
                                        'produksi_id' => $data->produksi_id,
                                        'bahan_id' => $detail->bahan_id,
                                        'qty' => $qty['qty'],
                                        'jml_bahan' => $detail->jml_bahan,
                                        'used_materials' => $qty['qty'],
                                        'details' => json_encode(array_values($groupedDetails)), // Gunakan array_values untuk mengubah menjadi indexed array
                                        'sub_total' => $qty['qty'] * $unitPrice,
                                    ]);
                                }
                            }
                        } elseif ($data->projek_id) {
                            foreach ($groupedDetails as $unitPrice => $qty) {
                                $projekDetail = ProjekDetails::where('projek_id', $data->projek_id)
                                    ->where('bahan_id', $detail->bahan_id)
                                    ->first();

                                $detailsToSave = [
                                    'qty' => $qty['qty'],
                                    'unit_price' => $unitPrice,
                                ];

                                if ($projekDetail) {
                                    $projekDetail->qty += $qty['qty'];
                                    $projekDetail->sub_total += $qty['qty'] * $unitPrice;
                                    $projekDetail->details = json_encode(array_values($groupedDetails)); // Gunakan array_values untuk mengubah menjadi indexed array
                                    $projekDetail->save();
                                } else {
                                    ProjekDetails::create([
                                        'projek_id' => $data->projek_id,
                                        'bahan_id' => $detail->bahan_id,
                                        'qty' => $qty['qty'],
                                        'details' => json_encode(array_values($groupedDetails)), // Gunakan array_values untuk mengubah menjadi indexed array
                                        'sub_total' => $qty['qty'] * $unitPrice,
                                    ]);
                                }
                            }
                        }
                    }
                }

                // Kurangi stok
                foreach ($pendingStockReductions as $reduction) {
                    $reduction['detail']->sisa -= $reduction['qty'];
                    $reduction['detail']->sisa = max(0, $reduction['detail']->sisa);
                    $reduction['detail']->save();
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
