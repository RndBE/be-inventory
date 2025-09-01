<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\Kontrak;
use App\Helpers\LogHelper;
use App\Models\ProdukJadis;
use Illuminate\Http\Request;
use App\Models\ProdukProduksi;
use App\Models\BahanSetengahjadi;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\BahanSetengahjadiDetails;
use App\Exports\BahanSetengahjadisExport;

class ProdukJadisController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-bahan-setengahjadi', ['only' => ['index']]);
        $this->middleware('permission:tambah-bahan-setengahjadi', ['only' => ['create','store']]);
        $this->middleware('permission:detail-bahan-setengahjadi', ['only' => ['show']]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date') . ' 00:00:00';
        $endDate = $request->input('end_date') . ' 23:59:59';

        $companyName = "PT ARTA TEKNOLOGI COMUNINDO";

        return Excel::download(
    new BahanSetengahjadisExport($startDate, $endDate, $companyName),
    'laporan_bahan_setengahjadi.xlsx',
    \Maatwebsite\Excel\Excel::XLSX,
    ['charts' => true] // <== penting
);

    }

    public function index()
    {
        $produkJadis = ProdukJadis::with('ProdukJadiDetails')->get();
        return view('pages.produk-jadi.index', compact('produkJadis'));
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
            'kode_transaksi' => 'nullable|string',
            'tgl_masuk' => 'nullable|date',
            'bahan_id' => 'nullable|exists:bahan,id',
            'qty' => 'nullable|integer',
            'unit_price' => 'nullable|numeric',
            'serial_number' => 'nullable|string',
        ]);

        try {
            // Simpan data ke dalam tabel bahan_setengah_jadis
            $bahanSetengahJadi = new BahanSetengahJadi();
            $bahanSetengahJadi->kode_transaksi = $request->kode_transaksi;
            $bahanSetengahJadi->tgl_masuk = $request->tgl_masuk;
            $bahanSetengahJadi->save();

            // Hitung subtotal (unit_price * qty)
            // $subTotal = $request->unit_price * $request->qty;


            // Ambil serial number dari kolom `serial_number` di tabel `produksi`
            $serialNumbers = explode(',', $request->serial_number);
            $serialNumbers = array_map('trim', $serialNumbers); // Hilangkan spasi ekstra
            // dd($serialNumbers);
            if (count($serialNumbers) < $request->qty) {
                return redirect()->back()->withInput()->with('error', "Jumlah serial number kurang dari qty yang dimasukkan!");
            }
            if (count($serialNumbers) > $request->qty) {
                return redirect()->back()->withInput()->with('error', "Jumlah serial number lebih dari qty yang dimasukkan!");
            }

            $bahan = Bahan::find($request->bahan_id);

            if (!$bahan) {
                return redirect()->back()->withInput()->with('error', 'Data bahan tidak ditemukan.');
            }

            for ($i = 0; $i < $request->qty; $i++) {
                $bahanSetengahJadiDetails = new BahanSetengahjadiDetails();
                $bahanSetengahJadiDetails->bahan_setengahjadi_id = $bahanSetengahJadi->id;
                // $bahanSetengahJadiDetails->bahan_id = $produksi->bahan_id;
                $bahanSetengahJadiDetails->nama_bahan = $bahan->nama_bahan;
                $bahanSetengahJadiDetails->qty = 1;
                $bahanSetengahJadiDetails->sisa = 1;
                $bahanSetengahJadiDetails->unit_price = $request->unit_price;
                $bahanSetengahJadiDetails->sub_total = $request->unit_price;
                $bahanSetengahJadiDetails->serial_number = $serialNumbers[$i] ?? null;

                $bahanSetengahJadiDetails->save();
            }

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
        $ProdukJadi = ProdukJadis::with([
            'ProdukJadiDetails.ProdukJadis',
            'produksiProdukJadiDetails',
            'projekRndDetails'
        ])->findOrFail($id);

        // dd($ProdukJadi);

        return view('pages.produk-jadi.show', [
            'kode_transaksi' => $ProdukJadi->kode_transaksi,
            'kode_produksi' => $ProdukJadi->produksiProdukJadi ? $ProdukJadi->produksiProdukJadi->kode_produksi : null,
            'tgl_masuk' => $ProdukJadi->tgl_masuk,
            'ProdukJadiDetails' => $ProdukJadi->ProdukJadiDetails,
            'produksiProdukJadiDetails' => $ProdukJadi->produksiProdukJadiDetails,
            'projekRndDetails' => $ProdukJadi->projekRndDetails,
        ]);
    }

}
