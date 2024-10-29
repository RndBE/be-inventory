<?php

namespace App\Http\Controllers;

use Throwable;
use App\Helpers\LogHelper;
use App\Models\BahanRusak;
use Illuminate\Http\Request;
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
            'tgl_masuk' => $bahanRusak->tgl_masuk,
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
                    $produksiDetail = ProduksiDetails::where('bahan_id', $returDetail->bahan_id)->first();

                    if ($produksiDetail) {
                        // Decode kolom details untuk mengakses setiap item secara individu
                        $details = json_decode($produksiDetail->details, true);

                        foreach ($details as $key => &$detail) {
                            if ($detail['unit_price'] == $returDetail->unit_price) {
                                // Kurangi qty sesuai dengan qty pada bahan retur detail
                                $detail['qty'] -= $returDetail->qty;

                                // Pastikan qty tidak menjadi negatif
                                if ($detail['qty'] <= 0) {
                                    unset($details[$key]);
                                }
                            }
                        }

                        // Menghitung total qty setelah update
                        $totalQty = array_sum(array_column($details, 'qty'));

                        // Update sub_total pada produksi detail
                        if ($totalQty > 0) {
                            $produksiDetail->sub_total = 0;
                            foreach ($details as $detail) {
                                $produksiDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                            }
                        } else {
                            $produksiDetail->sub_total = 0;
                        }

                        // Kurangi kolom qty dan used_materials di produksi detail
                        $produksiDetail->qty -= $returDetail->qty;
                        $produksiDetail->used_materials -= $returDetail->qty;

                        // Pastikan nilai qty dan used_materials tidak negatif
                        $produksiDetail->qty = max($produksiDetail->qty, 0);
                        $produksiDetail->used_materials = max($produksiDetail->used_materials, 0);

                        // Update details dan simpan ke database
                        $produksiDetail->details = json_encode(array_values($details));
                        $produksiDetail->save();

                        // Set nilai sisa pada bahan rusak detail
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
