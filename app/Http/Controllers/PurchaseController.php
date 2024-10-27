<?php

namespace App\Http\Controllers;

use Throwable;
use Carbon\Carbon;
use App\Models\Bahan;
use App\Models\Purchase;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;
use App\Models\PurchaseDetail;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with('purchaseDetails')->get();
        return view('pages.purchases.index', compact('purchases'));
    }

    public function show($id)
    {
        $purchase = Purchase::with('purchaseDetails.dataBahan.dataUnit')->findOrFail($id);
        return view('pages.purchases.show', [
            'kode_transaksi' => $purchase->kode_transaksi,
            'tgl_masuk' => $purchase->tgl_masuk,
            'purchaseDetails' => $purchase->purchaseDetails,
        ]);
    }


    public function create()
    {
        return view('pages.purchases.create');
    }

    public function store(Request $request)
    {
        try{
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
            $tgl_masuk = $request->tgl_masuk . ' ' . now()->setTimezone('Asia/Jakarta')->format('H:i:s');
            $purchase = new Purchase();
            $purchase->kode_transaksi = $kode_transaksi;
            $purchase->tgl_masuk = $tgl_masuk;
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
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy(Request $request, $id)
    {
        try{
            $data = Purchase::find($id);
            if (!$data) {
                return redirect()->back()->with('gagal', 'Transaksi tidak ditemukan.');
            }
            $data->delete();
            LogHelper::success('Berhasil Menghapus Transaksi Bahan Masuk!');
            return redirect()->route('purchases.index')->with('success', 'Berhasil Menghapus Transaksi Bahan Masuk!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

}
