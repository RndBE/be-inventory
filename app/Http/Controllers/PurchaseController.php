<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Models\PurchaseDetail;

class PurchaseController extends Controller
{
    public function index()
    {
        // Menampilkan semua transaksi barang masuk
        $purchases = Purchase::with('details')->get();
        return view('pages.purchases.index', compact('purchases'));
    }

    public function create()
    {
        // Form untuk menambah barang masuk baru
        return view('pages.purchases.create');
    }

    public function store(Request $request)
    {
        // Validasi data input
        $request->validate([
            'tgl_masuk' => 'required|date',
            'kode_transaksi' => 'required|unique:purchases',
            'divisi' => 'required',
            'details.*.bahan_id' => 'required|exists:bahan,id',
            'details.*.qty' => 'required|integer|min:1',
            'details.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Menyimpan data ke tabel purchases
        $purchase = Purchase::create([
            'tgl_masuk' => $request->tgl_masuk,
            'kode_transaksi' => $request->kode_transaksi,
            'divisi' => $request->divisi,
        ]);

        // Menyimpan data ke tabel purchase_details
        foreach ($request->details as $detail) {
            PurchaseDetail::create([
                'purchase_id' => $purchase->id,
                'bahan_id' => $detail['bahan_id'],
                'qty' => $detail['qty'],
                'unit_price' => $detail['unit_price'],
                'sub_total' => $detail['qty'] * $detail['unit_price'],
            ]);
        }

        return redirect()->route('pages.purchases.index')->with('success', 'Data barang masuk berhasil disimpan.');
}
}
