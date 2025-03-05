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
use App\Models\PengajuanDetails;
use App\Models\ProjekRndDetails;
use App\Models\BahanReturDetails;
use App\Models\BahanSetengahjadi;
use Illuminate\Support\Facades\DB;
use App\Models\PengambilanBahanDetails;
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
                    $key = $returDetail->bahan_id ?? $returDetail->produk_id; // Pilih bahan_id atau produk_id
                    $unitPrice = $returDetail->unit_price;
                    $serialNumber = $returDetail->serial_number ?? null; // Tambahkan Serial Number jika ada

                    if (isset($groupedDetails[$key][$unitPrice])) {
                        $groupedDetails[$key][$unitPrice]['qty'] += $returDetail->qty;
                    } else {
                        $groupedDetails[$key][$unitPrice] = [
                            'bahan_id' => $returDetail->bahan_id,
                            'produk_id' => $returDetail->produk_id,
                            'qty' => $returDetail->qty,
                            'unit_price' => $unitPrice,
                            'serial_number' => $serialNumber, // Simpan Serial Number jika ada
                        ];
                    }
                }

                // Langkah 2: Kurangi qty di ProduksiDetails dan ProjekDetails sesuai groupedDetails
                foreach ($groupedDetails as $bahanId => $detailsByPrice) {
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
