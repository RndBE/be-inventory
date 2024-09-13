<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Models\PurchaseDetail;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends Controller
{
    public function index()
    {
        // Menampilkan semua transaksi barang masuk
        $purchases = Purchase::with('details')->get();
        return view('pages.purchases.index', compact('purchases'));
    }

    public function show($id)
    {
        // $units = Unit::all();
        // $jenisBahan = JenisBahan::all();
        // $bahan = Bahan::findOrFail($id);
        return view('pages.purchases.show');
    }

    public function create()
    {
        // Form untuk menambah barang masuk baru
        return view('pages.purchases.create');
    }

    public function store(Request $request)
    {
        // Decode cartItems dari JSON string menjadi array
        $cartItems = json_decode($request->cartItems, true);

        // Lakukan validasi setelah cartItems di-decode
        $validator = Validator::make([
            'tgl_masuk' => $request->tgl_masuk,
            'cartItems' => $cartItems
        ], [
            'tgl_masuk' => 'required', // Ubah validasi jika perlu
            'cartItems' => 'required|array',
            'cartItems.*.id' => 'required|integer', // Menambahkan validasi item cart
            'cartItems.*.qty' => 'required|integer|min:1', // Menambahkan validasi qty
            'cartItems.*.unit_price' => 'required', // Menambahkan validasi unit_price
            'cartItems.*.sub_total' => 'required', // Menambahkan validasi sub_total
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Menghasilkan kode transaksi
        $kode_transaksi = 'KBM - ' . strtoupper(uniqid());

        // Konversi format tanggal
        $tgl_masuk = $request->tgl_masuk;

        // Simpan data pembelian
        $purchase = new Purchase();
        $purchase->kode_transaksi = $kode_transaksi;
        $purchase->tgl_masuk = $tgl_masuk; // Gunakan tanggal yang sudah diformat
        $purchase->save();

        // Simpan item pembelian dari cart yang sudah didecode
        foreach ($cartItems as $item) {
            PurchaseDetail::create([
                'purchase_id' => $purchase->id,
                'bahan_id' => $item['id'],
                'qty' => $item['qty'],
                'unit_price' => $item['unit_price'],
                'sub_total' => $item['sub_total'],
            ]);
        }

        return redirect()->route('purchases.index')->with('success', 'Pembelian berhasil disimpan!');
    }

    public function destroy(Request $request, $id)
    {

        $data = Purchase::find($id);
        $purchases = $data->delete();
        if (!$purchases) {
            return redirect()->back()->with('gagal', 'menghapus');
        }
        return redirect()->route('purchases.index')->with('success', 'Bahan Masuk berhasil dihapus.');
    }


}
