<?php

namespace App\Http\Controllers;

use Throwable;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Bahan;
use App\Models\Purchase;
use App\Helpers\LogHelper;
use App\Models\BahanRetur;
use App\Models\BahanKeluar;
use App\Models\StockOpname;
use Illuminate\Http\Request;
use App\Models\ProjekDetails;
use App\Models\PurchaseDetail;
use App\Models\ProduksiDetails;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PengajuanDetails;
use App\Models\ProjekRndDetails;
use App\Models\BahanReturDetails;
use App\Models\BahanSetengahjadi;
use App\Models\BahanKeluarDetails;
use App\Models\StockOpnameDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendWhatsAppApproveLeader;
use App\Models\PengambilanBahanDetails;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Support\Facades\Validator;

class StockOpnameController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-stock-opname', ['only' => ['index']]);
        $this->middleware('permission:update-stock-opname', ['only' => ['update']]);
        $this->middleware('permission:tambah-stock-opname', ['only' => ['create','store']]);
        $this->middleware('permission:edit-stock-opname', ['only' => ['edit']]);
        $this->middleware('permission:hapus-stock-opname', ['only' => ['destroy']]);
        $this->middleware('permission:approve-stock-opname-finance', ['only' => ['updateApprovalFinance']]);
        $this->middleware('permission:approve-stock-opname-direktur', ['only' => ['updateApprovalDirektur']]);
        $this->middleware('permission:selesai-stock-opname', ['only' => ['selesaiStockOpname']]);
    }

    public function index()
    {
        $stock_opname = StockOpname::with('stockOpnameDetails')->get();
        return view('pages.stock-opname.index', compact('stock_opname'));
    }

    public function downloadPdf(int $id)
    {
        try {
            $stockOpname = StockOpname::with([
                'pengajuUser',
                'stockOpnameDetails.dataBahan.dataUnit',
            ])->findOrFail($id);

            foreach ($stockOpname->stockOpnameDetails as $detail) {
                $selisih = abs($detail->selisih); // Ambil selisih dalam nilai positif
                $purchaseDetails = PurchaseDetail::where('bahan_id', $detail->dataBahan->id)
                ->where('sisa', '>', 0)
                ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
                ->orderBy('purchases.tgl_masuk', 'asc')
                ->select('purchase_details.*', 'purchases.tgl_masuk')
                ->get();

                $alokasiHarga = [];
                $totalHarga = 0;

                foreach ($purchaseDetails as $purchase) {
                    if ($selisih <= 0) break; // Jika selisih sudah terpenuhi, hentikan proses

                    $qtyTerpakai = min($selisih, $purchase->sisa); // Ambil jumlah yang bisa digunakan dari transaksi ini
                    $harga = $qtyTerpakai * $purchase->unit_price;

                    $alokasiHarga[] = "{$qtyTerpakai} x " . number_format($purchase->unit_price, 0, ',', '.');
                    $totalHarga += $harga;

                    $selisih -= $qtyTerpakai; // Kurangi jumlah selisih dengan jumlah yang sudah diambil
                }

                // Simpan hasil alokasi ke dalam detail
                $detail->alokasi_harga = implode(', ', $alokasiHarga);
                $detail->total_harga = $totalHarga;
            }

            // Hitung total keseluruhan untuk laporan
            $totalSelisih = $stockOpname->stockOpnameDetails->sum('selisih');
            $totalHargaAll = $stockOpname->stockOpnameDetails->sum('total_harga');

            Carbon::setLocale('id');
            $formattedDate = Carbon::parse($stockOpname->tgl_pengajuan)->translatedFormat('d F Y');
            $tandaTanganPengaju = $stockOpname->pengajuUser->tanda_tangan ?? null;

            $tandaTanganLeader = null;
            $tandaTanganManager = $stockOpname->pengajuUser->atasanLevel2->tanda_tangan ?? null;
            $tandaTanganDirektur = $stockOpname->pengajuUser->atasanLevel1->tanda_tangan ?? null;
            $managerName = $stockOpname->pengajuUser->atasanLevel2 ? $stockOpname->pengajuUser->atasanLevel2->name : null;
            $direkturName = $stockOpname->pengajuUser->atasanLevel1 ? $stockOpname->pengajuUser->atasanLevel1->name : null;

            $adminManagerceUser = cache()->remember('admin_manager_user', 60, function () {
                return User::where('job_level', 2)
                    ->whereHas('dataJobPosition', function ($query) {
                        $query->where('nama', 'Admin Manager');
                    })->first();
            });
            $tandaTanganAdminManager = $adminManagerceUser->tanda_tangan ?? null;

            $pdf = Pdf::loadView('pages.stock-opname.pdf', compact(
                'stockOpname',
                'formattedDate',
                'tandaTanganPengaju',
                'tandaTanganManager',
                'tandaTanganDirektur',
                'tandaTanganAdminManager',
                'adminManagerceUser',
                'managerName',
                'direkturName',
                'totalSelisih',
                'totalHargaAll'
            ))->setPaper('a4', 'potrait');
            return $pdf->stream("stock_opname_{$id}.pdf");

            LogHelper::success('Berhasil generating PDF for stock opname ID {$id}!');
            return $pdf->download("pembelian_bahan_{$id}.pdf");

        } catch (\Exception $e) {
            LogHelper::error("Error generating PDF for stock opname ID {$id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengunduh PDF.');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.stock-opname.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $cartItems = json_decode($request->cartItems, true);
        $validator = Validator::make([
            'tgl_pengajuan' => $request->tgl_pengajuan,
            'cartItems' => $cartItems
        ], [
            'tgl_pengajuan' => 'required|date_format:Y-m-d',
            'cartItems' => 'required|array',
            'cartItems.*.id' => 'required|integer',
            'cartItems.*.tersedia_sistem' => 'required|integer',
            'cartItems.*.tersedia_fisik' => 'required|integer',
            'cartItems.*.selisih' => 'required|integer',
            'cartItems.*.keterangan' => 'nullable',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        DB::beginTransaction();
        try {

            $user = Auth::user();
            // Simpan data utama stock opname
            $stockOpname = StockOpname::create([
                'tgl_pengajuan' => $request->tgl_pengajuan,
                'tgl_diterima' => null,
                'nomor_referensi' => $this->generateNomorReferensi(),
                'keterangan' => $request->keterangan,
                'status_finance' => 'Belum disetujui',
                'status_direktur' => 'Belum disetujui',
                'pengaju' => $user->id,
            ]);

            foreach ($cartItems as $item) {
                StockOpnameDetails::create([
                    'stock_opname_id' => $stockOpname->id,
                    'bahan_id' => $item['id'],
                    'tersedia_sistem' => $item['tersedia_sistem'],
                    'tersedia_fisik' => $item['tersedia_fisik'],
                    'selisih' => $item['selisih'],
                    'keterangan' => $item['keterangan'],
                ]);
            }
            DB::commit();
            LogHelper::success('Stock opname berhasil disimpan.');
            return redirect()->route('stock-opname.index')->with('success', 'Stock opname berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    private function generateNomorReferensi()
    {
        $lastOpname = StockOpname::latest()->first();
        $nextNumber = $lastOpname ? intval(substr($lastOpname->nomor_referensi, -4)) + 1 : 1;
        return 'SO-' . date('Ymd') . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $stockOpname = StockOpname::with('stockOpnameDetails')->findOrFail($id);

        $bahans = Bahan::whereHas('jenisBahan', function($query) {
            $query->where('nama', 'Produksi');
        })->get();

        return view('pages.stock-opname.edit', [
            'stockOpname' => $stockOpname,
            'status_finance' => $stockOpname->status_finance,
            'status_direktur' => $stockOpname->status_direktur,
            'stockOpnameId' => $id,
            'bahans' => $bahans,
        ]);
    }


    public function update(Request $request, $id)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'tgl_pengajuan' => 'required|date_format:Y-m-d',
            'keterangan' => 'required|string|max:255',
            'cartItems' => 'required|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $stockOpname = StockOpname::findOrFail($id);
            $stockOpname->update([
                'tgl_pengajuan' => $request->tgl_pengajuan,
                'keterangan' => $request->keterangan,
            ]);

            if ($request->has('cartItems')) {
                $stockOpname->stockOpnameDetails()->delete();

                foreach ($request->cartItems as $item) {
                    $item = json_decode($item, true);
                    $detail = StockOpnameDetails::updateOrCreate(
                        ['stock_opname_id' => $stockOpname->id,
                        'bahan_id' => $item['id']],
                        [
                            'tersedia_sistem' => $item['tersedia_sistem'],
                            'tersedia_fisik' => $item['tersedia_fisik'],
                            'selisih' => $item['selisih'],
                            'keterangan' => $item['keterangan'],
                        ]
                    );
                }
            }

            DB::commit();
            LogHelper::success('Stock opname berhasil diperbarui.');
            return redirect()->route('stock-opname.index')->with('success', 'Stock opname berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function updateApprovalFinance(Request $request, int $id)
    {
        $validated = $request->validate([
            'status_finance' => 'required|string|in:Belum disetujui,Disetujui,Ditolak',
        ]);
        try {
            DB::beginTransaction();
            $data = StockOpname::with([
                'pengajuUser',
                'stockOpnameDetails.dataBahan.dataUnit',
            ])->findOrFail($id);

            $data->status_finance = $validated['status_finance'];
            $data->save();

            if ($data->status_finance === 'Disetujui') {

                //dd($purchasingUsers);

                $targetPhone = $data->pengajuUser->telephone;
                //dd($targetPhone);
                if ($targetPhone) {
                    $message = "Halo {$data->pengajuUser->name},\n\n";
                    $message .= "Pengajuan stock opname dengan nomor referensi {$data->nomor_referensi} telah disetujui divisi finance.\n\n";
                    $message .= "Tgl Pengajuan: {$data->tgl_pengajuan}\nPengaju: {$data->pengajuUser->name}\nKeterangan: {$data->keterangan}\n\n";
                    $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
                    SendWhatsAppApproveLeader::dispatch($targetPhone, $message);
                    LogHelper::success("Pesan sedang dikirim.");
                } else {
                    LogHelper::error('No valid phone number found for WhatsApp notification.');
                }
            }
            DB::commit();
            LogHelper::success("Status approval finance berhasil diubah.");
            return redirect()->route('stock-opname.index')->with('success', 'Status approval finance berhasil diubah.');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan. Pesan error: $errorMessage");
        }
    }

    public function updateApprovalDirektur(Request $request, int $id)
    {
        $validated = $request->validate([
            'status_direktur' => 'required|string|in:Belum disetujui,Disetujui,Ditolak',
        ]);
        try {
            DB::beginTransaction();
            $data = StockOpname::with([
                'pengajuUser',
                'stockOpnameDetails.dataBahan.dataUnit',
            ])->findOrFail($id);

            $data->status_direktur = $validated['status_direktur'];
            $data->tgl_diterima = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $data->save();

            if ($data->status_direktur === 'Disetujui') {

                //dd($purchasingUsers);

                $targetPhone = $data->pengajuUser->telephone;
                //dd($targetPhone);
                if ($targetPhone) {
                    $message = "Halo {$data->pengajuUser->name},\n\n";
                    $message .= "Pengajuan stock opname dengan nomor referensi {$data->nomor_referensi} telah disetujui direktur.\n\n";
                    $message .= "Tgl Pengajuan: {$data->tgl_pengajuan}\nPengaju: {$data->pengajuUser->name}\nKeterangan: {$data->keterangan}\n\n";
                    $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
                    SendWhatsAppApproveLeader::dispatch($targetPhone, $message);
                    LogHelper::success("Pesan sedang dikirim.");
                } else {
                    LogHelper::error('No valid phone number found for WhatsApp notification.');
                }
            }
            DB::commit();
            LogHelper::success("Status approval finance berhasil diubah.");
            return redirect()->route('stock-opname.index')->with('success', 'Status approval finance berhasil diubah.');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan. Pesan error: $errorMessage");
        }
    }

    public function selesaiStockOpname($id)
    {
        try {
            DB::beginTransaction();
            $stockOpname = StockOpname::findOrFail($id);
            if ($stockOpname->status_direktur !== 'Disetujui') {
                return redirect()->back()->with('error', 'Stock opname belum disetujui!');
            }

            $user = $stockOpname->pengaju;
            $tujuan = $stockOpname->keterangan;

            $lastTransaction = BahanKeluar::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number = ($lastTransaction ? intval(substr($lastTransaction->kode_transaksi, 6)) : 0) + 1;
            $kode_transaksi = 'KBK - ' . str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT) . ' SO';
            $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            $bahan_keluar = BahanKeluar::create([
                'kode_transaksi' => $kode_transaksi,
                'tujuan' => $tujuan,
                'keterangan' => $tujuan,
                'divisi' => 'Purchasing',
                'status' => 'Disetujui',
                'status_leader' => 'Disetujui',
                'pengaju' => $user,
                'status_pengambilan' => 'Sudah Diambil',
                'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'tgl_keluar' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ]);

            foreach ($stockOpname->stockOpnameDetails as $detail) {
                $selisih = abs($detail->selisih);
                $purchaseDetails = PurchaseDetail::where('bahan_id', $detail->dataBahan->id)
                    ->where('sisa', '>', 0)
                    ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
                    ->orderBy('purchases.tgl_masuk', 'asc')
                    ->select('purchase_details.*', 'purchases.tgl_masuk')
                    ->get();

                $detailsArray = [];
                $totalHarga = 0;

                foreach ($purchaseDetails as $purchaseDetail) {
                    if ($selisih <= 0) break;

                    $qtyTerpakai = min($selisih, $purchaseDetail->sisa);
                    $purchaseDetail->sisa -= $qtyTerpakai;
                    $purchaseDetail->save();

                    $detailsArray[] = [
                        'kode_transaksi' => $purchaseDetail->kode_transaksi,
                        'qty' => $qtyTerpakai,
                        'unit_price' => $purchaseDetail->unit_price
                    ];

                    $selisih -= $qtyTerpakai;
                }

                $groupedDetails = [];
                foreach ($detailsArray as $detailItem) {
                    $unitPrice = $detailItem['unit_price'];
                    if (isset($groupedDetails[$unitPrice])) {
                        $groupedDetails[$unitPrice]['qty'] += $detailItem['qty'];
                    } else {
                        $groupedDetails[$unitPrice] = [
                            'qty' => $detailItem['qty'],
                            'unit_price' => $unitPrice,
                        ];
                    }
                }

                BahanKeluarDetails::create([
                    'bahan_keluar_id' => $bahan_keluar->id,
                    'bahan_id' => $detail->dataBahan->id,
                    'qty' => array_sum(array_column($groupedDetails, 'qty')),
                    'jml_bahan' => array_sum(array_column($groupedDetails, 'qty')),
                    'used_materials' => array_sum(array_column($groupedDetails, 'qty')),
                    'details' => json_encode(array_values($groupedDetails)),
                    'sub_total' => array_sum(array_map(function($item) {
                        return $item['qty'] * $item['unit_price'];
                    }, $groupedDetails)),
                ]);
            }

            $stockOpname->status_selesai = 'Selesai';
            $stockOpname->save();

            DB::commit();
            LogHelper::success('Stock opname selesai dan data sisa diperbarui.');
            return redirect()->route('stock-opname.index')->with('success', 'Stock opname selesai dan data sisa diperbarui.');
        } catch (Throwable $e) {
            DB::rollBack();
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }






    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $data = StockOpname::find($id);
            if (!$data) {
                return redirect()->back()->with('gagal', 'Transaksi tidak ditemukan.');
            }
            $data->delete();
            LogHelper::success('Berhasil Menghapus Pengajuan Stock Opname!');
            return redirect()->route('stock-opname.index')->with('success', 'Berhasil Menghapus Pengajuan Stock Opname!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
