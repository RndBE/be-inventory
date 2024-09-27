<?php

namespace App\Http\Controllers;

use App\Models\BahanKeluar;
use Illuminate\Http\Request;
use App\Models\BahanKeluarDetails;
use App\Models\DetailProduksi;
use App\Models\Produksi;
use App\Models\ProduksiDetails;
use Illuminate\Support\Facades\Validator;

class ProduksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pages.produksis.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.produksis.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $cartItems = json_decode($request->cartItems, true);
        $validator = Validator::make([
            'nama_produk' => $request->nama_produk,
            'jml_produksi' => $request->jml_produksi,
            'mulai_produksi' => $request->mulai_produksi,
            'jenis_produksi' => $request->jenis_produksi,
            'cartItems' => $cartItems
        ], [
            'nama_produk' => 'required',
            'jml_produksi' => 'required',
            'mulai_produksi' => 'required',
            'jenis_produksi' => 'required',
            'cartItems' => 'required|array',
            'cartItems.*.id' => 'required|integer',
            'cartItems.*.qty' => 'required|integer|min:1',
            'cartItems.*.details' => 'required|array',
            'cartItems.*.sub_total' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $lastTransaction = BahanKeluar::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
        if ($lastTransaction) {
            $last_transaction_number = intval(substr($lastTransaction->kode_transaksi, 6));
        } else {
            $last_transaction_number = 0;
        }
        $new_transaction_number = $last_transaction_number + 1;
        $formatted_number = str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT);
        $kode_transaksi = 'KBK - ' . $formatted_number;

        $tgl_keluar = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        // Save main keluar data
        $bahan_keluar = new BahanKeluar();
        $bahan_keluar->kode_transaksi = $kode_transaksi;
        $bahan_keluar->tgl_keluar = $tgl_keluar;
        $bahan_keluar->divisi = 'Produksi';
        $bahan_keluar->status = 'Belum disetujui';
        $bahan_keluar->save();

        $lastTransactionProduksi = Produksi::orderByRaw('CAST(SUBSTRING(kode_produksi, 7) AS UNSIGNED) DESC')->first();
        if ($lastTransactionProduksi) {
            $last_transaction_number_produksi = intval(substr($lastTransactionProduksi->kode_produksi, 6));
        } else {
            $last_transaction_number_produksi = 0;
        }
        $new_transaction_number_produksi = $last_transaction_number_produksi + 1;
        $formatted_number_produksi = str_pad($new_transaction_number_produksi, 5, '0', STR_PAD_LEFT);
        $kode_produksi = 'PR - ' . $formatted_number_produksi;

        $produksi = new Produksi();
        $produksi->bahan_keluar_id = $bahan_keluar->id;
        $produksi->kode_produksi = $kode_produksi;
        $produksi->nama_produk = $request->nama_produk;
        $produksi->jml_produksi = $request->jml_produksi;
        $produksi->mulai_produksi = $request->mulai_produksi;
        $produksi->jenis_produksi = $request->jenis_produksi;
        $produksi->status = 'Konfirmasi';
        $produksi->save();

        // Group items by bahan_id and aggregate quantities
        $groupedItems = [];
        foreach ($cartItems as $item) {
            if (!isset($groupedItems[$item['id']])) {
                $groupedItems[$item['id']] = [
                    'qty' => 0,
                    'details' => $item['details'], // Assuming you want to keep the same unit price
                    'sub_total' => 0,
                ];
            }
            $groupedItems[$item['id']]['qty'] += $item['qty'];
            $groupedItems[$item['id']]['sub_total'] += $item['sub_total'];
        }

        // Save the details
        foreach ($groupedItems as $bahan_id => $details) {
            BahanKeluarDetails::create([
                'bahan_keluar_id' => $bahan_keluar->id,
                'bahan_id' => $bahan_id,
                'qty' => $details['qty'],
                'details' => json_encode($details['details']),
                'sub_total' => $details['sub_total'],
            ]);

            ProduksiDetails::create([
                'produksi_id' => $produksi->id,
                'bahan_id' => $bahan_id,
                'qty' => $details['qty'],
                'details' => json_encode($details['details']),
                'sub_total' => $details['sub_total'],
            ]);
        }
        return redirect()->back()->with('success', 'Permintaan berhasil ditambahkan!');
    }

    public function show(string $id)
    {

    }

    public function edit(string $id)
    {
        $produksi = Produksi::with(['produksiDetails.dataBahan', 'bahanKeluar'])->findOrFail($id);
        if ($produksi->bahanKeluar->status != 'Disetujui') {
            return redirect()->back()->with('error', 'Produksi belum disetujui. Anda tidak dapat mengakses halaman tersebut.');
        }

        return view('pages.produksis.edit', [
            'produksiId' => $produksi->id,
            'produksi' => $produksi,
            'id' => $id
        ]);
    }


    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        // Temukan transaksi produksi
        $produksi = Produksi::find($id);
        if (!$produksi) {
            return redirect()->back()->with('gagal', 'Produksi tidak ditemukan.');
        }
        // Temukan bahan keluar yang terkait dengan produksi
        $bahanKeluar = BahanKeluar::find($produksi->bahan_keluar_id);
        // Hapus produksi
        $produksi->delete();
        // Jika bahan keluar ditemukan, hapus juga
        if ($bahanKeluar) {
            $bahanKeluar->delete();
        }
        return redirect()->route('produksis.index')->with('success', 'Produksi dan bahan keluar terkait berhasil dihapus.');
    }

}
