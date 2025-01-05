<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Purchase;
use App\Helpers\LogHelper;
use App\Models\BahanRetur;
use App\Models\StockOpname;
use Illuminate\Http\Request;
use App\Models\ProjekDetails;
use App\Models\PurchaseDetail;
use App\Models\ProduksiDetails;
use App\Models\PengajuanDetails;
use App\Models\ProjekRndDetails;
use App\Models\BahanReturDetails;
use App\Models\BahanSetengahjadi;
use Illuminate\Support\Facades\DB;
use App\Models\PengambilanBahanDetails;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Support\Facades\Validator;

class StockOpnameController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('permission:lihat-bahan-retur', ['only' => ['index']]);
    //     $this->middleware('permission:detail-bahan-retur', ['only' => ['show']]);
    //     $this->middleware('permission:edit-bahan-retur', ['only' => ['update','edit']]);
    //     $this->middleware('permission:hapus-bahan-retur', ['only' => ['destroy']]);
    // }

    public function index()
    {
        $stock_opname = StockOpname::with('stockOpnameDetails')->get();
        return view('pages.stock-opname.index', compact('stock_opname'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.stock-opname.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            dd($request->all());
            $cartItems = json_decode($request->cartItems, true);
            $validator = Validator::make([
                'tgl_masuk' => $request->tgl_masuk,
                'cartItems' => $cartItems
            ], [
                'tgl_masuk' => 'required|date_format:Y-m-d',
                'cartItems' => 'required|array',
                'cartItems.*.id' => 'required|integer',
                'cartItems.*.qty' => 'required|integer|min:1',
                'cartItems.*.unit_price' => 'required',
                'cartItems.*.sub_total' => 'required',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $kode_transaksi = 'KBM - ' . strtoupper(uniqid());
            // $tgl_masuk = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $purchase = new Purchase();
            $purchase->kode_transaksi = $kode_transaksi;
            $purchase->tgl_masuk = $request->tgl_masuk;
            $purchase->save();
            foreach ($cartItems as $item) {
                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'bahan_id' => $item['id'],
                    'qty' => $item['qty'],
                    'sisa' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'sub_total' => $item['sub_total'],
                ]);
            }
            LogHelper::success('Berhasil Menambahkan Transaksi Bahan Masuk!');
            return redirect()->route('purchases.index')->with('success', 'Berhasil Menambahkan Transaksi Bahan Masuk!');
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
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
            DB::beginTransaction();
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

                        $totalQtyReduction = array_sum(array_column($detailsByPrice, 'qty'));
                        $projekDetail->qty -= $totalQtyReduction;
                        $projekDetail->used_materials -= $totalQtyReduction;

                        $projekDetail->qty = max(0, $projekDetail->qty);
                        $projekDetail->used_materials = max(0, $projekDetail->used_materials);

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

                        $totalQtyReduction = array_sum(array_column($detailsByPrice, 'qty'));
                        $projekRndDetail->qty -= $totalQtyReduction;
                        $projekRndDetail->used_materials -= $totalQtyReduction;

                        $projekRndDetail->qty = max(0, $projekRndDetail->qty);
                        $projekRndDetail->used_materials = max(0, $projekRndDetail->used_materials);

                        $projekRndDetail->sub_total = 0;
                        foreach ($currentDetails as $detail) {
                            $projekRndDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                        }

                        $projekRndDetail->details = json_encode(array_values($currentDetails));
                        $projekRndDetail->save();
                    }

                    // Update untuk PengajuanDetails
                    $pengajuanDetail = PengajuanDetails::where('pengajuan_id', $bahanRetur->pengajuan_id)
                    ->where('bahan_id', $bahanId)
                    ->first();

                    if ($pengajuanDetail) {
                        $currentDetails = json_decode($pengajuanDetail->details, true) ?? [];

                        foreach ($detailsByPrice as $unitPrice => $qtyData) {
                            foreach ($currentDetails as $key => &$entry) {
                                if ($entry['unit_price'] == $unitPrice) {
                                    $entry['qty'] -= $qtyData['qty'];
                                    if ($entry['qty'] <= 0) unset($currentDetails[$key]);
                                    break;
                                }
                            }
                        }

                        $totalQtyReduction = array_sum(array_column($detailsByPrice, 'qty'));
                        $pengajuanDetail->qty -= $totalQtyReduction;
                        $pengajuanDetail->used_materials -= $totalQtyReduction;

                        $pengajuanDetail->qty = max(0, $pengajuanDetail->qty);
                        $pengajuanDetail->used_materials = max(0, $pengajuanDetail->used_materials);

                        $pengajuanDetail->sub_total = 0;
                        foreach ($currentDetails as $detail) {
                            $pengajuanDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                        }

                        $pengajuanDetail->details = json_encode(array_values($currentDetails));
                        if ($pengajuanDetail->qty == 0 && $pengajuanDetail->used_materials == 0) {
                            $pengajuanDetail->delete();
                        } else {
                            $pengajuanDetail->save();
                        }
                    }

                    // Update untuk PengambilanBahanDetails
                    $pengambilanBahanDetail = PengambilanBahanDetails::where('pengambilan_bahan_id', $bahanRetur->pengambilan_bahan_id)
                    ->where('bahan_id', $bahanId)
                    ->first();
                    if ($pengambilanBahanDetail) {
                        $currentDetails = json_decode($pengambilanBahanDetail->details, true) ?? [];
                        foreach ($detailsByPrice as $unitPrice => $qtyData) {
                            foreach ($currentDetails as $key => &$entry) {
                                if ($entry['unit_price'] == $unitPrice) {
                                    $entry['qty'] -= $qtyData['qty'];
                                    if ($entry['qty'] <= 0) unset($currentDetails[$key]);
                                    break;
                                }
                            }
                        }
                        $totalQtyReduction = array_sum(array_column($detailsByPrice, 'qty'));
                        $pengambilanBahanDetail->qty -= $totalQtyReduction;
                        $pengambilanBahanDetail->used_materials -= $totalQtyReduction;
                        $pengambilanBahanDetail->qty = max(0, $pengambilanBahanDetail->qty);
                        $pengambilanBahanDetail->used_materials = max(0, $pengambilanBahanDetail->used_materials);
                        $pengambilanBahanDetail->sub_total = 0;
                        foreach ($currentDetails as $detail) {
                            $pengambilanBahanDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                        }
                        $pengambilanBahanDetail->details = json_encode(array_values($currentDetails));
                        if ($pengambilanBahanDetail->qty == 0 && $pengambilanBahanDetail->used_materials == 0) {
                            $pengambilanBahanDetail->delete();
                        } else {
                            $pengambilanBahanDetail->save();
                        }
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
                DB::commit();
                LogHelper::success('Berhasil Mengubah Status Bahan Retur!');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Pesan error: $errorMessage");
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
