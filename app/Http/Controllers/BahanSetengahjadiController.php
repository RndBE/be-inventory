<?php

namespace App\Http\Controllers;

use App\Models\Kontrak;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;
use App\Models\ProdukProduksi;
use App\Models\BahanSetengahjadi;
use App\Models\BahanSetengahjadiDetails;

class BahanSetengahjadiController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-bahan-setengahjadi', ['only' => ['index']]);
        $this->middleware('permission:tambah-bahan-setengahjadi', ['only' => ['create','store']]);
        $this->middleware('permission:detail-bahan-setengahjadi', ['only' => ['show']]);
    }

    public function index()
    {
        $bahanSetengahjadis = BahanSetengahjadi::with('bahanSetengahjadiDetails')->get();
        return view('pages.bahan-setengahjadis.index', compact('bahanSetengahjadis'));
    }

    public function create()
    {
        $produkProduksis = ProdukProduksi::all();
        return view('pages.bahan-setengahjadis.create', compact('produkProduksis'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        // Validasi input
        $request->validate([
            'kode_transaksi' => 'nullable|string|unique:bahan_setengahjadis,kode_transaksi',
            'tgl_masuk' => 'nullable|date',
            'bahan_id' => 'nullable|exists:bahan,id',
            'qty' => 'nullable|integer',
            'unit_price' => 'nullable|numeric',
        ]);

        try {
            // Simpan data ke dalam tabel bahan_setengah_jadis
            $bahanSetengahJadi = new BahanSetengahJadi();
            $bahanSetengahJadi->kode_transaksi = $request->kode_transaksi;
            $bahanSetengahJadi->tgl_masuk = $request->tgl_masuk;
            $bahanSetengahJadi->save();

            // Hitung subtotal (unit_price * qty)
            $subTotal = $request->unit_price * $request->qty;

            // Simpan data ke dalam tabel bahan_setengahjadi_details
            $bahanSetengahJadiDetails = new BahanSetengahjadiDetails();
            $bahanSetengahJadiDetails->bahan_setengahjadi_id = $bahanSetengahJadi->id;
            $bahanSetengahJadiDetails->bahan_id = $request->bahan_id;
            $bahanSetengahJadiDetails->qty = $request->qty;
            $bahanSetengahJadiDetails->sisa = $request->qty;
            $bahanSetengahJadiDetails->unit_price = $request->unit_price;
            $bahanSetengahJadiDetails->sub_total = $subTotal; // Perhitungan subtotal
            $bahanSetengahJadiDetails->save();

            // Redirect dengan pesan sukses
            LogHelper::success('Berhasil menambahkan stok bahan setengah jadi!');
            return redirect()->route('bahan-setengahjadis.index')->with('success', 'Berhasil menambahkan stok bahan setengah jadi!');
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Pesan error: $errorMessage");
        }
    }



    public function show($id)
{
    $bahanSetengahjadi = BahanSetengahjadi::with([
        'bahanSetengahjadiDetails.dataBahan.dataUnit',
        'produksiDetails',
        'projekRndDetails'
    ])->findOrFail($id);

    return view('pages.bahan-setengahjadis.show', [
        'kode_transaksi' => $bahanSetengahjadi->kode_transaksi,
        'kode_produksi' => $bahanSetengahjadi->produksiS ? $bahanSetengahjadi->produksiS->kode_produksi : null,
        'tgl_masuk' => $bahanSetengahjadi->tgl_masuk,
        'bahanSetengahjadiDetails' => $bahanSetengahjadi->bahanSetengahjadiDetails,
        'produksiDetails' => $bahanSetengahjadi->produksiDetails,
        'projekRndDetails' => $bahanSetengahjadi->projekRndDetails,
    ]);
}

}
