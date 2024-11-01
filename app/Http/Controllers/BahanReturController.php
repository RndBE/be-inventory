<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Purchase;
use App\Helpers\LogHelper;
use App\Models\BahanRetur;
use Illuminate\Http\Request;
use App\Models\ProjekDetails;
use App\Models\PurchaseDetail;
use App\Models\ProduksiDetails;
use App\Models\ProjekRndDetails;
use App\Models\BahanReturDetails;
use App\Models\BahanSetengahjadi;
use App\Models\BahanSetengahjadiDetails;

class BahanReturController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-bahan-retur', ['only' => ['index']]);
        $this->middleware('permission:detail-bahan-retur', ['only' => ['show']]);
        $this->middleware('permission:edit-bahan-retur', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-bahan-retur', ['only' => ['destroy']]);
    }

    public function index()
    {
        $bahan_returs = BahanRetur::with('bahanReturDetails')->get();
        return view('pages.bahan-returs.index', compact('bahan_returs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required',
            ]);

            $bahanRetur = BahanRetur::findOrFail($id);
            $bahanReturDetails = BahanReturDetails::where('bahan_retur_id', $id)->get();

            if ($validated['status'] === 'Disetujui') {
                $groupedDetails = [];

                // Langkah 1: Kelompokkan bahanReturDetails berdasarkan bahan_id dan unit_price
                foreach ($bahanReturDetails as $returDetail) {
                    $bahanId = $returDetail->bahan_id;
                    $unitPrice = $returDetail->unit_price;

                    if (isset($groupedDetails[$bahanId][$unitPrice])) {
                        $groupedDetails[$bahanId][$unitPrice]['qty'] += $returDetail->qty;
                    } else {
                        $groupedDetails[$bahanId][$unitPrice] = [
                            'qty' => $returDetail->qty,
                            'unit_price' => $unitPrice,
                        ];
                    }
                }

                // Langkah 2: Kurangi qty di ProduksiDetails dan ProjekDetails sesuai groupedDetails
                foreach ($groupedDetails as $bahanId => $detailsByPrice) {
                    // Update untuk ProduksiDetails
                    $produksiDetail = ProduksiDetails::where('produksi_id', $bahanRetur->produksi_id)
                        ->where('bahan_id', $bahanId)
                        ->first();

                    if ($produksiDetail) {
                        $currentDetails = json_decode($produksiDetail->details, true) ?? [];

                        foreach ($detailsByPrice as $unitPrice => $qtyData) {
                            foreach ($currentDetails as $key => &$entry) {
                                if ($entry['unit_price'] == $unitPrice) {
                                    $entry['qty'] -= $qtyData['qty'];
                                    if ($entry['qty'] <= 0) unset($currentDetails[$key]);
                                    break;
                                }
                            }
                        }

                        // Kurangi qty dan used_materials pada ProduksiDetails
                        $totalQtyReduction = array_sum(array_column($detailsByPrice, 'qty'));
                        $produksiDetail->qty -= $totalQtyReduction;
                        $produksiDetail->used_materials -= $totalQtyReduction;

                        // Pastikan qty dan used_materials tidak negatif
                        $produksiDetail->qty = max(0, $produksiDetail->qty);
                        $produksiDetail->used_materials = max(0, $produksiDetail->used_materials);

                        $produksiDetail->sub_total = 0;
                        foreach ($currentDetails as $detail) {
                            $produksiDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                        }

                        $produksiDetail->details = json_encode(array_values($currentDetails));
                        $produksiDetail->save();
                    }

                    // Update untuk ProjekDetails
                    $projekDetail = ProjekDetails::where('projek_id', $bahanRetur->projek_id)
                        ->where('bahan_id', $bahanId)
                        ->first();

                    if ($projekDetail) {
                        $currentDetails = json_decode($projekDetail->details, true) ?? [];

                        foreach ($detailsByPrice as $unitPrice => $qtyData) {
                            foreach ($currentDetails as $key => &$entry) {
                                if ($entry['unit_price'] == $unitPrice) {
                                    $entry['qty'] -= $qtyData['qty'];
                                    if ($entry['qty'] <= 0) unset($currentDetails[$key]);
                                    break;
                                }
                            }
                        }

                        $projekDetail->qty -= array_sum(array_column($detailsByPrice, 'qty'));
                        $projekDetail->qty = max(0, $projekDetail->qty);

                        $projekDetail->sub_total = 0;
                        foreach ($currentDetails as $detail) {
                            $projekDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                        }

                        $projekDetail->details = json_encode(array_values($currentDetails));
                        $projekDetail->save();
                    }

                    // Update untuk ProjekDetails
                    $projekRndDetail = ProjekRndDetails::where('projek_rnd_id', $bahanRetur->projek_rnd_id)
                        ->where('bahan_id', $bahanId)
                        ->first();

                    if ($projekRndDetail) {
                        $currentDetails = json_decode($projekRndDetail->details, true) ?? [];

                        foreach ($detailsByPrice as $unitPrice => $qtyData) {
                            foreach ($currentDetails as $key => &$entry) {
                                if ($entry['unit_price'] == $unitPrice) {
                                    $entry['qty'] -= $qtyData['qty'];
                                    if ($entry['qty'] <= 0) unset($currentDetails[$key]);
                                    break;
                                }
                            }
                        }

                        $projekRndDetail->qty -= array_sum(array_column($detailsByPrice, 'qty'));
                        $projekRndDetail->qty = max(0, $projekRndDetail->qty);

                        $projekRndDetail->sub_total = 0;
                        foreach ($currentDetails as $detail) {
                            $projekRndDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                        }

                        $projekRndDetail->details = json_encode(array_values($currentDetails));
                        $projekRndDetail->save();
                    }
                }

                // Tambahkan bahan retur ke purchase
                $purchase = Purchase::firstOrCreate(
                    ['kode_transaksi' => 'RTR-' . uniqid()],
                    ['tgl_masuk' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')]
                );

                foreach ($bahanReturDetails as $returDetail) {
                    $jenisBahan = $returDetail->dataBahan->jenisBahan->nama;

                    if ($jenisBahan === 'Produksi') {
                        $bahanSetengahJadi = BahanSetengahjadi::firstOrCreate([
                            'kode_transaksi' => 'RTR-' . uniqid(),
                            'tgl_masuk' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        ]);

                        BahanSetengahjadiDetails::create([
                            'bahan_setengahjadi_id' => $bahanSetengahJadi->id,
                            'bahan_id' => $returDetail->bahan_id,
                            'qty' => $returDetail->qty,
                            'sisa' => $returDetail->qty,
                            'unit_price' => $returDetail->unit_price,
                            'sub_total' => $returDetail->qty * $returDetail->unit_price,
                        ]);
                    } else {
                        PurchaseDetail::create([
                            'purchase_id' => $purchase->id,
                            'bahan_id' => $returDetail->bahan_id,
                            'qty' => $returDetail->qty,
                            'sisa' => $returDetail->qty,
                            'unit_price' => $returDetail->unit_price,
                            'sub_total' => $returDetail->qty * $returDetail->unit_price,
                        ]);
                    }
                }

                $bahanRetur->status = $validated['status'];
                $bahanRetur->tgl_diterima = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                $bahanRetur->save();
                LogHelper::success('Berhasil Mengubah Status Bahan Retur!');
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorColumn = '';
            if (strpos($errorMessage, 'tgl_keluar') !== false) {
                $errorColumn = 'tgl_keluar';
            } elseif (strpos($errorMessage, 'status') !== false) {
                $errorColumn = 'status';
            }
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan pada kolom: $errorColumn. Pesan error: $errorMessage");
        }
        return redirect()->route('bahan-returs.index')->with('success', 'Status berhasil diubah.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $data = BahanRetur::find($id);
            if (!$data) {
                return redirect()->back()->with('gagal', 'Transaksi tidak ditemukan.');
            }
            $data->delete();
            LogHelper::success('Berhasil Menghapus Pengajuan Bahan Retur!');
            return redirect()->route('bahan-returs.index')->with('success', 'Berhasil Menghapus Pengajuan Bahan Retur!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
