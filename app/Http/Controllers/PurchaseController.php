<?php

namespace App\Http\Controllers;

use Throwable;
use Carbon\Carbon;
use App\Models\Bahan;
use App\Models\Purchase;
use App\Helpers\LogHelper;
use App\Helpers\SatuanBahanHelper;
use Illuminate\Http\Request;
use App\Models\PurchaseDetail;
use App\Exports\PurchasesExport;
use App\Models\BahanKeluarDetails;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-bahan-masuk', ['only' => ['index']]);
        $this->middleware('permission:detail-bahan-masuk', ['only' => ['show']]);
        $this->middleware('permission:tambah-bahan-masuk', ['only' => ['create','store']]);
        // Tanpa baris untuk 'edit' dan 'update': kedua method itu tidak pernah
        // ada di controller ini, sehingga route resource-nya mengarah ke method
        // yang tidak ada dan siapa pun yang menekannya mendapat error. Koreksi
        // bahan masuk sekarang lewat satu pintu di modul Perbaikan Data, dengan
        // approval dan jejak audit — bukan lewat form edit langsung di sini.
        // Permission 'edit-bahan-masuk' dibiarkan hidup di database supaya
        // pemberiannya ke role tidak hilang kalau nanti dipakai lagi.
        $this->middleware('permission:hapus-bahan-masuk', ['only' => ['destroy']]);
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

        return Excel::download(new PurchasesExport($startDate, $endDate, $companyName), 'purchases.xlsx');
    }

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
            'no_invoice' => $purchase->no_invoice,
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
        //    dd($request->all());
            $cartItems = json_decode($request->cartItems, true);
            $validator = Validator::make([
                'tgl_masuk' => $request->tgl_masuk,
                'no_invoice' => $request->no_invoice,
                'cartItems' => $cartItems
            ], [
                'tgl_masuk' => 'required|date_format:Y-m-d',
                'no_invoice' => 'nullable',
                'cartItems' => 'required|array',
                'cartItems.*.id' => 'required',
                'cartItems.*.qty' => 'required',
                'cartItems.*.unit_price' => 'required',
                'cartItems.*.sub_total' => 'required',
                'cartItems.*.satuan' => 'nullable|in:batang,cm',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $kode_transaksi = 'KBM - ' . strtoupper(uniqid());
            // $tgl_masuk = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $purchase = new Purchase();
            $purchase->kode_transaksi = $kode_transaksi;
            $purchase->tgl_masuk = $request->tgl_masuk;
            $purchase->no_invoice = $request->no_invoice;
            $purchase->save();
            foreach ($cartItems as $item) {
                // Satuan dikirim keranjang per baris: bahan batangan bisa dicatat
                // per batang atau per cm, karena kiriman supplier tidak selalu
                // pas sepanjang panjang standar. Default batang menjaga baris
                // tanpa satuan (bahan biasa, atau kiriman lama) tetap sama
                // perilakunya. catatLot yang mengonversi ke satuan ledger.
                PurchaseDetail::catatLot([
                    'purchase_id' => $purchase->id,
                    'bahan_id' => $item['id'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'sub_total' => $item['sub_total'],
                ], $item['satuan'] ?? SatuanBahanHelper::SATUAN_BATANG);
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
            $data = Purchase::with('purchaseDetails')->find($id);
            if (!$data) {
                return redirect()->back()->with('gagal', 'Transaksi tidak ditemukan.');
            }

            // Lot yang stoknya sudah terpakai tidak boleh dihapus. Baris
            // `purchase_details` ikut terhapus lewat cascade, sedangkan kolom
            // `details` di transaksi hilir masih menyimpan `kode_transaksi` lot
            // ini — referensinya jadi menggantung dan nilai persediaan berubah
            // tanpa jejak. Jalur hapus ini lebih berbahaya daripada jalur
            // koreksi mana pun, dan tidak ada yang memeriksanya sebelum ini.
            $lotTerpakai = $data->purchaseDetails
                ->filter(fn ($lot) => (float) $lot->sisa < (float) $lot->qty);

            if ($lotTerpakai->isNotEmpty()) {
                LogHelper::error(
                    "Penghapusan bahan masuk {$data->kode_transaksi} ditolak: "
                    . $lotTerpakai->count() . ' lot stoknya sudah terpakai.'
                );

                return redirect()->back()->with(
                    'error',
                    'Transaksi bahan masuk ini tidak bisa dihapus karena stoknya sudah terpakai '
                    . 'di transaksi lain. Ajukan koreksi lewat Perbaikan Data.'
                );
            }

            $data->delete();
            LogHelper::success('Berhasil Menghapus Transaksi Bahan Masuk!');
            return redirect()->route('purchases.index')->with('success', 'Berhasil Menghapus Transaksi Bahan Masuk!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }


    public function notifTransaksi()
    {
        $tanggal = Carbon::today();
        // $tanggal = Carbon::create(2025, 17, 04);
        $awal = $tanggal->startOfDay()->toDateTimeString();
        $akhir = $tanggal->endOfDay()->toDateTimeString();

        // Fetch data for today's purchases (entries)
        $masuk = PurchaseDetail::with('dataBahan.dataUnit')
            ->whereHas('purchase', function($query) use ($awal, $akhir) {
                $query->whereBetween('tgl_masuk', [$awal, $akhir]);
            })
            ->get();

        // Fetch data for today's bahan keluar (exits)
        $keluar = BahanKeluarDetails::with('dataBahan.dataUnit')
            ->whereHas('bahanKeluar', function($query) use ($awal, $akhir) {
                $query->whereBetween('tgl_keluar', [$awal, $akhir]);
            })
            ->get();

        $consolidatedMasuk = [];
        foreach ($masuk as $item) {
            $bahanId = $item->dataBahan->id;
            if (isset($consolidatedMasuk[$bahanId])) {
                $consolidatedMasuk[$bahanId]['qty'] += $item->qty;
            } else {
                $consolidatedMasuk[$bahanId] = [
                    'nama_bahan' => $item->dataBahan->nama_bahan,
                    'qty' => $item->qty,
                    'unit' => $item->dataBahan->dataUnit->nama ?? '',
                ];
            }
        }

        $consolidatedKeluar = [];
        foreach ($keluar as $item) {
            $bahanId = $item->dataBahan->id;
            if (isset($consolidatedKeluar[$bahanId])) {
                $consolidatedKeluar[$bahanId]['qty'] += $item->qty;
            } else {
                $consolidatedKeluar[$bahanId] = [
                    'nama_bahan' => $item->dataBahan->nama_bahan,
                    'qty' => $item->qty,
                    'unit' => $item->dataBahan->dataUnit->nama ?? '',
                ];
            }
        }

        $consolidatedKeluar = array_filter($consolidatedKeluar, function ($item) {
            return $item['qty'] > 0;
        });

        // Format messages for WhatsApp
        $message = "Tanggal *" . $tanggal->toDateString() . "* \n\n";
        $message .= "List Barang Masuk:\n";
        if (!empty($consolidatedMasuk)) {
            $index = 1;
            foreach ($consolidatedMasuk as $item) {
                $message .= $index . '. ' . $item['nama_bahan'] . ' - ' . $item['qty'] .' '. $item['unit'] . "\n";
                $index++;
            }
        } else {
            $message .= "- \n";
        }

        $message .= "\nList Barang Keluar:\n";
        if (!empty($consolidatedKeluar)) {
            $index = 1;
            foreach ($consolidatedKeluar as $item) {
                $message .= $index . '. ' . $item['nama_bahan'] . ' - ' . $item['qty'] .' '. $item['unit'] . "\n";
                $index++;
            }
        } else {
            $message .= "- \n";
        }

        $message .= "\nPesan Otomatis:\n";
        $message .= "https://inventory.beacontelemetry.com/";

        if (!empty($consolidatedMasuk) || !empty($consolidatedKeluar)) {
            $response = Http::withHeaders([
                'x-api-key' => env('WHATSAPP_API_KEY'),
                'Content-Type' => 'application/json',
            // ])->post('http://72.60.78.159:3000/client/sendMessage/beacon', [
            ])->post('http://127.0.0.1:3000/client/sendMessage/beacon', [
                'chatId' => '6282242966796-1553841116@g.us',
                // 'chatId' => '6282137153589@c.us',
                'contentType' => 'string',
                'content' => $message
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Notification sent successfully',
                'response' => $response->body()
            ]);
        } else {
            return response()->json([
                'status' => 'success',
                'message' => 'No transactions for today'
            ]);
        }
    }
}
