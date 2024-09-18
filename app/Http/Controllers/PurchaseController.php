<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Models\PurchaseDetail;
use App\Models\Bahan;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends Controller
{
    public function index()
    {
        // Menampilkan semua transaksi barang masuk
        $purchases = Purchase::with('purchaseDetails')->get();
        return view('pages.purchases.index', compact('purchases'));
    }

    public function show($id)
    {
        $purchase = Purchase::with('purchaseDetails.dataBahan.dataUnit')->findOrFail($id); // Mengambil detail pembelian
        return view('pages.purchases.show', [
            'kode_transaksi' => $purchase->kode_transaksi,
            'tgl_masuk' => $purchase->tgl_masuk, // Ambil tanggal transaksi
            'purchaseDetails' => $purchase->purchaseDetails, // Ambil detail pembelian
        ]);
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

        // Menghasilkan kode transaksi
        $kode_transaksi = 'KBM - ' . strtoupper(uniqid());
        $tgl_masuk = $request->tgl_masuk . ' ' . now()->setTimezone('Asia/Jakarta')->format('H:i:s');

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

            // Update total_stok
            $bahan = Bahan::find($item['id']); // Pastikan Anda memiliki model Bahan
            if ($bahan) {
                $bahan->total_stok += $item['qty']; // Tambahkan qty ke total_stok
                $bahan->save(); // Simpan perubahan
            }
        }

        return redirect()->route('purchases.index')->with('success', 'Pembelian berhasil disimpan!');
    }

    public function destroy(Request $request, $id)
    {
        // Temukan transaksi pembelian
        $data = Purchase::find($id);

        if (!$data) {
            return redirect()->back()->with('gagal', 'Transaksi tidak ditemukan.');
        }

        // Ambil detail pembelian untuk mengurangi stok
        $purchaseDetails = $data->purchaseDetails; // Pastikan relasi sudah didefinisikan di model Purchase

        // Kurangi total_stok berdasarkan detail pembelian
        foreach ($purchaseDetails as $detail) {
            $bahan = Bahan::find($detail->bahan_id); // Temukan bahan berdasarkan bahan_id
            if ($bahan) {
                $bahan->total_stok -= $detail->qty; // Kurangi qty dari total_stok
                $bahan->save(); // Simpan perubahan
            }
        }
        // Hapus transaksi pembelian
        $data->delete();

        return redirect()->route('purchases.index')->with('success', 'Bahan Masuk berhasil dihapus.');
    }

}
