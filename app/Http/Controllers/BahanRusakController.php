<?php

namespace App\Http\Controllers;

use Throwable;
use App\Helpers\LogHelper;
use App\Models\BahanRusak;
use Illuminate\Http\Request;
use App\Models\ProjekDetails;
use App\Models\ProduksiDetails;
use App\Models\BahanRusakDetails;

class BahanRusakController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-bahan-rusak', ['only' => ['index']]);
        $this->middleware('permission:detail-bahan-rusak', ['only' => ['show']]);
    }

    public function index()
    {
        $bahanRusaks = BahanRusak::with('bahanRusakDetails')->get();
        return view('pages.bahan-rusaks.index', compact('bahanRusaks'));
    }

    public function show($id)
    {
        $bahanRusak = BahanRusak::with('bahanRusakDetails.dataBahan.dataUnit')->findOrFail($id);
        return view('pages.bahan-rusaks.show', [
            'kode_transaksi' => $bahanRusak->kode_transaksi,
            'tgl_pengajuan' => $bahanRusak->tgl_pengajuan ?? null,
            'tgl_diterima' => $bahanRusak->tgl_diterima ?? null,
            'kode_produksi' => $bahanRusak->produksiS ? $bahanRusak->produksiS->kode_produksi : null,
            'kode_projek' => $bahanRusak->projek ? $bahanRusak->projek->kode_projek : null,
            'bahanRusakDetails' => $bahanRusak->bahanRusakDetails,
        ]);
    }

    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required',
            ]);

            $bahanRusak = BahanRusak::findOrFail($id);
            $bahanRusakDetails = BahanRusakDetails::where('bahan_rusak_id', $id)->get();

            // Jika status bahan rusak disetujui
            if ($validated['status'] === 'Disetujui') {
                foreach ($bahanRusakDetails as $returDetail) {
                    $produksiDetail = ProduksiDetails::where('produksi_id', $bahanRusak->produksi_id)
                        ->where('bahan_id', $returDetail->bahan_id)
                        ->first();

                    $projekDetail = ProjekDetails::where('projek_id', $bahanRusak->projek_id)
                    ->where('bahan_id', $returDetail->bahan_id)
                    ->first();

                    if ($produksiDetail) {
                        $details = json_decode($produksiDetail->details, true) ?? [];

                        foreach ($details as $key => &$detail) {
                            // Ensure correct access to unit_price
                            if ($detail['unit_price'] === $returDetail->unit_price) {
                                // Decrease qty based on retur detail
                                $detail['qty'] -= $returDetail->qty;

                                // Ensure qty doesn't go negative
                                if ($detail['qty'] <= 0) {
                                    unset($details[$key]);
                                }
                            }
                        }

                        // Total qty after updates
                        $totalQty = array_sum(array_column($details, 'qty'));

                        // Update sub_total
                        if ($totalQty > 0) {
                            $produksiDetail->sub_total = 0;
                            foreach ($details as $detail) {
                                $produksiDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                            }
                        } else {
                            $produksiDetail->sub_total = 0;
                        }

                        // Adjust qty and used_materials
                        $produksiDetail->qty -= $returDetail->qty;
                        $produksiDetail->used_materials -= $returDetail->qty;

                        // Ensure non-negative values
                        $produksiDetail->qty = max($produksiDetail->qty, 0);
                        $produksiDetail->used_materials = max($produksiDetail->used_materials, 0);

                        // Update details and save
                        $produksiDetail->details = json_encode(array_values($details));
                        $produksiDetail->save();

                        // Set remaining qty in bahan rusak detail
                        $returDetail->sisa = $returDetail->qty;
                        $returDetail->save();
                    }
                    if ($projekDetail) {
                        $details = json_decode($projekDetail->details, true) ?? [];

                        foreach ($details as $key => &$detail) {
                            // Ensure correct access to unit_price
                            if ($detail['unit_price'] === $returDetail->unit_price) {
                                // Decrease qty based on retur detail
                                $detail['qty'] -= $returDetail->qty;

                                // Ensure qty doesn't go negative
                                if ($detail['qty'] <= 0) {
                                    unset($details[$key]);
                                }
                            }
                        }

                        // Total qty after updates
                        $totalQty = array_sum(array_column($details, 'qty'));

                        // Update sub_total
                        if ($totalQty > 0) {
                            $projekDetail->sub_total = 0;
                            foreach ($details as $detail) {
                                $projekDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                            }
                        } else {
                            $projekDetail->sub_total = 0;
                        }

                        // Adjust qty and used_materials
                        $projekDetail->qty -= $returDetail->qty;

                        // Ensure non-negative values
                        $projekDetail->qty = max($projekDetail->qty, 0);

                        // Update details and save
                        $projekDetail->details = json_encode(array_values($details));
                        $projekDetail->save();

                        // Set remaining qty in bahan rusak detail
                        $returDetail->sisa = $returDetail->qty;
                        $returDetail->save();
                    }
                }
            }

            // Update status bahan rusak
            $bahanRusak->status = $validated['status'];
            $bahanRusak->tgl_diterima = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $bahanRusak->save();

            LogHelper::success('Berhasil Mengubah Status Bahan Rusak!');
        }  catch (\Exception $e) {
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
        return redirect()->route('bahan-rusaks.index')->with('success', 'Status berhasil diubah.');
    }


    public function destroy(string $id)
    {
        try{
            $data = BahanRusak::find($id);
            if (!$data) {
                return redirect()->back()->with('gagal', 'Transaksi tidak ditemukan.');
            }
            $data->delete();
            LogHelper::success('Berhasil Menghapus Pengajuan Bahan Rusak!');
            return redirect()->route('bahan-rusaks.index')->with('success', 'Berhasil Menghapus Pengajuan Bahan Rusak!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

}
