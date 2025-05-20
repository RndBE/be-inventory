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
                'stockOpnameDetails.dataProduk',
            ])->findOrFail($id);

            // Urutkan berdasarkan nama bahan (atau nama produk jika bahan tidak ada)
            $stockOpname->stockOpnameDetails = $stockOpname->stockOpnameDetails->sortBy(function ($detail) {
                return $detail->dataBahan->nama_bahan ?? $detail->dataProduk->nama_bahan ?? '';
            })->values();

            foreach ($stockOpname->stockOpnameDetails as $detail) {
                $selisih = $detail->selisih;
                $selisih_audit = $detail->selisih_audit;

                $alokasiHarga = [];
                $alokasiHargaAudit = [];
                $totalHarga = 0;
                $totalHargaAudit = 0;

                // === Cek apakah ini bahan biasa ===
                if ($detail->dataBahan) {
                    $lastPurchaseDetail = PurchaseDetail::where('bahan_id', $detail->dataBahan->id)
                        ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
                        ->whereDate('purchases.tgl_masuk', '<=', $stockOpname->tgl_pengajuan)
                        ->orderBy('purchases.tgl_masuk', 'desc')
                        ->select('purchase_details.*', 'purchases.tgl_masuk')
                        ->first();

                    if ($lastPurchaseDetail) {
                        $harga = $selisih * $lastPurchaseDetail->unit_price;
                        $hargaAudit = $selisih_audit * $lastPurchaseDetail->unit_price;

                        $alokasiHarga[] = "{$selisih} x " . number_format($lastPurchaseDetail->unit_price, 0, ',', '.');
                        $alokasiHargaAudit[] = "{$selisih_audit} x " . number_format($lastPurchaseDetail->unit_price, 0, ',', '.');

                        $totalHarga += $harga;
                        $totalHargaAudit += $hargaAudit;
                    }

                // === Cek jika produk setengah jadi ===
                } elseif ($detail->dataProduk) {

                    $produkSetengahJadi = BahanSetengahjadiDetails::with('bahanSetengahjadi') // relasi ke parent
                        ->where('id', $detail->produk_id)
                        ->first();

                    if ($produkSetengahJadi && $produkSetengahJadi->bahanSetengahjadi && $produkSetengahJadi->bahanSetengahjadi->tgl_masuk <= $stockOpname->tgl_pengajuan) {
                        $unitPrice = $produkSetengahJadi->unit_price;
                        $harga = $selisih * $unitPrice;
                        $hargaAudit = $selisih_audit * $unitPrice;

                        $alokasiHarga[] = "{$selisih} x " . number_format($unitPrice, 0, ',', '.');
                        $alokasiHargaAudit[] = "{$selisih_audit} x " . number_format($unitPrice, 0, ',', '.');

                        $totalHarga += $harga;
                        $totalHargaAudit += $hargaAudit;
                    }

                }

                $detail->alokasi_harga = implode(', ', $alokasiHarga);
                $detail->alokasi_harga_audit = implode(', ', $alokasiHargaAudit);
                $detail->total_harga = $totalHarga;
                $detail->total_harga_audit = $totalHargaAudit;
            }


            // Hitung total keseluruhan untuk laporan
            $totalSelisih = $stockOpname->stockOpnameDetails->sum('selisih');
            $totalSelisihAudit = $stockOpname->stockOpnameDetails->sum('selisih_audit');
            $totalHargaAll = $stockOpname->stockOpnameDetails->sum('total_harga');
            $totalHargaAllAudit = $stockOpname->stockOpnameDetails->sum('total_harga_audit');

            Carbon::setLocale('id');
            $formattedDate = $stockOpname->tgl_pengajuan ? Carbon::parse($stockOpname->tgl_pengajuan)->translatedFormat('d F Y') : null;
            $formattedDateAudit = $stockOpname->tgl_audit ? Carbon::parse($stockOpname->tgl_audit)->translatedFormat('d F Y') : null;

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
            // $role = Auth::user();
            $user = Auth::user();
            $canSeeAuditDate = $user->hasAnyRole(['superadmin', 'administrasi']);
            $canSeeAuditDatePengaju = $user->hasAnyRole(['purchasing','produksi']);
            // dd($canSeeAuditDate);
            $pdf = Pdf::loadView('pages.stock-opname.pdf', compact(
                'stockOpname',
                'formattedDate',
                'formattedDateAudit',
                'tandaTanganPengaju',
                'tandaTanganManager',
                'tandaTanganDirektur',
                'tandaTanganAdminManager',
                'adminManagerceUser',
                'managerName',
                'direkturName',
                'totalSelisih',
                'totalSelisihAudit',
                'totalHargaAll',
                'totalHargaAllAudit',
                'canSeeAuditDate',
                'canSeeAuditDatePengaju'
            ))->setPaper('a4', 'landscape');
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
        // Validasi awal input mentah
        $rawCartItems = json_decode($request->cartItems, true);

        $validator = Validator::make([
            'tgl_pengajuan' => $request->tgl_pengajuan,
            'cartItems' => $rawCartItems,
        ], [
            'tgl_pengajuan' => 'required|date_format:Y-m-d',
            'cartItems' => 'required|array',
            'cartItems.*.bahan_id' => 'nullable|integer',
            'cartItems.*.produk_id' => 'nullable|integer',
            'cartItems.*.serial_number' => 'nullable|string',
            'cartItems.*.tersedia_sistem' => 'nullable|numeric',
            'cartItems.*.tersedia_fisik' => 'nullable|numeric',
            'cartItems.*.selisih' => 'nullable|numeric',
            'cartItems.*.keterangan' => 'nullable|string',
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

            // Simpan detail per item
            foreach ($rawCartItems as $item) {
                StockOpnameDetails::create([
                    'stock_opname_id' => $stockOpname->id,
                    'bahan_id' => $item['bahan_id'] ?? null,
                    'produk_id' => $item['produk_id'] ?? null,
                    'serial_number' => $item['serial_number'] ?? null,
                    'tersedia_sistem' => $item['tersedia_sistem'] ?? null,
                    'tersedia_fisik' => $item['tersedia_fisik'] ?? null,
                    'selisih' => $item['selisih'] ?? null,
                    'keterangan' => $item['keterangan'] ?? null,
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


    // public function update(Request $request, $id)
    // {
    //     dd($request->all());
    //     $validator = Validator::make($request->all(), [
    //         'tgl_pengajuan' => 'required|date_format:Y-m-d',
    //         'keterangan' => 'required|string|max:255',
    //         'cartItems' => 'required|array',
    //     ]);

    //     if ($validator->fails()) {
    //         return redirect()->back()->withErrors($validator)->withInput();
    //     }

    //     DB::beginTransaction();
    //     try {
    //         $stockOpname = StockOpname::findOrFail($id);
    //         $stockOpname->update([
    //             'tgl_pengajuan' => $request->tgl_pengajuan,
    //             'keterangan' => $request->keterangan,
    //         ]);

    //         if ($request->has('cartItems')) {
    //             $stockOpname->stockOpnameDetails()->delete();

    //             foreach ($request->cartItems as $item) {
    //                 $item = json_decode($item, true);
    //                 $detail = StockOpnameDetails::updateOrCreate(
    //                     ['stock_opname_id' => $stockOpname->id,
    //                     'bahan_id' => $item['id']],
    //                     [
    //                         'tersedia_sistem' => $item['tersedia_sistem'],
    //                         'tersedia_fisik' => $item['tersedia_fisik'],
    //                         'tersedia_fisik_audit' => $item['tersedia_fisik_audit'],
    //                         'selisih' => $item['selisih'],
    //                         'selisih_audit' => $item['selisih_audit'],
    //                         'keterangan' => $item['keterangan'],
    //                     ]
    //                 );
    //             }
    //         }

    //         DB::commit();
    //         LogHelper::success('Stock opname berhasil diperbarui.');
    //         return redirect()->route('stock-opname.index')->with('success', 'Stock opname berhasil diperbarui.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         LogHelper::error($e->getMessage());
    //         return view('pages.utility.404');
    //     }
    // }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'tgl_pengajuan' => 'required|date_format:Y-m-d',
            'tgl_audit' => 'nullable|date_format:Y-m-d',
            'auditor' => 'nullable|string|max:255',
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
                'tgl_audit' => $request->tgl_audit,
                'auditor' => $request->auditor,
                'keterangan' => $request->keterangan,
            ]);

            if ($request->has('cartItems')) {
                // Ambil semua bahan_id dan produk_id dari cartItems yg dikirim
                $idsFromRequest = collect($request->cartItems)->map(function($jsonItem) {
                    $item = json_decode($jsonItem, true);
                    return [
                        'bahan_id' => $item['bahan_id'] ?? null,
                        'produk_id' => $item['produk_id'] ?? null,
                    ];
                });
                // Ambil semua detail yang ada di DB untuk stock opname ini
                $existingDetails = StockOpnameDetails::where('stock_opname_id', $stockOpname->id)->get();

                // Hapus detail yang tidak ada di $idsFromRequest
                foreach ($existingDetails as $detail) {
                    $found = $idsFromRequest->first(function ($item) use ($detail) {
                        return $item['bahan_id'] == $detail->bahan_id && $item['produk_id'] == $detail->produk_id;
                    });

                    if (!$found) {
                        $detail->delete();
                    }
                }
                foreach ($request->cartItems as $jsonItem) {
                    $item = json_decode($jsonItem, true);

                    // Cari detail yang sudah ada di DB berdasarkan bahan_id dan produk_id
                    $detail = StockOpnameDetails::where('stock_opname_id', $stockOpname->id)
                        ->where('bahan_id', $item['bahan_id'] ?? null)
                        ->where('produk_id', $item['produk_id'] ?? null)
                        ->first();

                    if ($detail) {
                        // Bandingkan setiap field yang ingin diupdate, update hanya jika ada perubahan
                        $needUpdate = false;
                        $fields = ['tersedia_sistem', 'tersedia_fisik', 'tersedia_fisik_audit', 'selisih', 'selisih_audit', 'keterangan'];

                        foreach ($fields as $field) {
                            // Karena tipe bisa beda, gunakan loose compare untuk numeric dan string
                            if ((string)$detail->$field !== (string)$item[$field]) {
                                $needUpdate = true;
                                break;
                            }
                        }

                        if ($needUpdate) {
                            $detail->update([
                                'tersedia_sistem' => $item['tersedia_sistem'],
                                'tersedia_fisik' => $item['tersedia_fisik'],
                                'tersedia_fisik_audit' => $item['tersedia_fisik_audit'],
                                'selisih' => $item['selisih'],
                                'selisih_audit' => $item['selisih_audit'],
                                'keterangan' => $item['keterangan'],
                            ]);
                        }
                    } else {
                        // Jika belum ada, buat baru
                        StockOpnameDetails::create([
                            'stock_opname_id' => $stockOpname->id,
                            'bahan_id' => $item['bahan_id'] ?? null,
                            'produk_id' => $item['produk_id'] ?? null,
                            'tersedia_sistem' => $item['tersedia_sistem'],
                            'serial_number' => $item['serial_number'],
                            'tersedia_fisik' => $item['tersedia_fisik'],
                            'tersedia_fisik_audit' => $item['tersedia_fisik_audit'],
                            'selisih' => $item['selisih'],
                            'selisih_audit' => $item['selisih_audit'],
                            'keterangan' => $item['keterangan'],
                        ]);
                    }
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
            if ($validated['status_finance'] === 'Disetujui') {
                $data->tgl_approve_finance = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');  // isi dengan waktu saat ini
            } else {
                // Opsional: Jika status selain Disetujui, bisa set null atau tidak diubah
                $data->tgl_approve_finance = null;
            }
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
            if ($validated['status_direktur'] === 'Disetujui') {
                $data->tgl_approve_direktur = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');  // isi dengan waktu saat ini
                $data->tgl_diterima = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            } else {
                // Opsional: Jika status selain Disetujui, bisa set null atau tidak diubah
                $data->tgl_approve_direktur = null;
            }

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

    // public function selesaiStockOpname($id)
    // {
    //     try {
    //         DB::beginTransaction();
    //         $stockOpname = StockOpname::findOrFail($id);
    //         if ($stockOpname->status_direktur !== 'Disetujui') {
    //             return redirect()->back()->with('error', 'Stock opname belum disetujui!');
    //         }

    //         $user = $stockOpname->pengaju;
    //         $tujuan = $stockOpname->keterangan;

    //         $lastTransaction = BahanKeluar::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
    //         $new_transaction_number = ($lastTransaction ? intval(substr($lastTransaction->kode_transaksi, 6)) : 0) + 1;
    //         $kode_transaksi = 'KBK - ' . str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT) . ' SO';
    //         $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

    //         $bahan_keluar = BahanKeluar::create([
    //             'kode_transaksi' => $kode_transaksi,
    //             'tujuan' => $tujuan,
    //             'keterangan' => $tujuan,
    //             'divisi' => 'Purchasing',
    //             'status' => 'Disetujui',
    //             'status_leader' => 'Disetujui',
    //             'pengaju' => $user,
    //             'status_pengambilan' => 'Sudah Diambil',
    //             'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
    //             'tgl_keluar' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
    //         ]);

    //         foreach ($stockOpname->stockOpnameDetails as $detail) {
    //             $selisih = $detail->selisih_audit;

    //             // Abaikan jika selisih positif (tidak mengurangi stok)
    //             if ($selisih > 0) {
    //                 continue;
    //             }

    //             $selisih = abs($selisih);
    //             $purchaseDetails = PurchaseDetail::where('bahan_id', $detail->dataBahan->id)
    //                 ->where('sisa', '>', 0)
    //                 ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
    //                 ->orderBy('purchases.tgl_masuk', 'asc')
    //                 ->select('purchase_details.*', 'purchases.tgl_masuk')
    //                 ->get();

    //             $detailsArray = [];
    //             $totalHarga = 0;

    //             foreach ($purchaseDetails as $purchaseDetail) {
    //                 if ($selisih <= 0) break;

    //                 $qtyTerpakai = min($selisih, $purchaseDetail->sisa);
    //                 $purchaseDetail->sisa -= $qtyTerpakai;
    //                 $purchaseDetail->save();

    //                 $detailsArray[] = [
    //                     'kode_transaksi' => $purchaseDetail->kode_transaksi,
    //                     'qty' => $qtyTerpakai,
    //                     'unit_price' => $purchaseDetail->unit_price
    //                 ];

    //                 $selisih -= $qtyTerpakai;
    //             }

    //             $groupedDetails = [];
    //             foreach ($detailsArray as $detailItem) {
    //                 $unitPrice = $detailItem['unit_price'];
    //                 if (isset($groupedDetails[$unitPrice])) {
    //                     $groupedDetails[$unitPrice]['qty'] += $detailItem['qty'];
    //                 } else {
    //                     $groupedDetails[$unitPrice] = [
    //                         'qty' => $detailItem['qty'],
    //                         'unit_price' => $unitPrice,
    //                     ];
    //                 }
    //             }

    //             BahanKeluarDetails::create([
    //                 'bahan_keluar_id' => $bahan_keluar->id,
    //                 'bahan_id' => $detail->dataBahan->id,
    //                 'qty' => array_sum(array_column($groupedDetails, 'qty')),
    //                 'jml_bahan' => array_sum(array_column($groupedDetails, 'qty')),
    //                 'used_materials' => array_sum(array_column($groupedDetails, 'qty')),
    //                 'details' => json_encode(array_values($groupedDetails)),
    //                 'sub_total' => array_sum(array_map(function($item) {
    //                     return $item['qty'] * $item['unit_price'];
    //                 }, $groupedDetails)),
    //             ]);
    //         }

    //         $stockOpname->status_selesai = 'Selesai';
    //         $stockOpname->save();

    //         DB::commit();
    //         LogHelper::success('Stock opname selesai dan data sisa diperbarui.');
    //         return redirect()->route('stock-opname.index')->with('success', 'Stock opname selesai dan data sisa diperbarui.');
    //     } catch (Throwable $e) {
    //         DB::rollBack();
    //         LogHelper::error($e->getMessage());
    //         return view('pages.utility.404');
    //     }
    // }
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
            $tanggal = now()->setTimezone('Asia/Jakarta');

            $bahan_keluar = BahanKeluar::create([
                'kode_transaksi' => $kode_transaksi,
                'tujuan' => $tujuan,
                'keterangan' => $tujuan,
                'divisi' => 'Purchasing',
                'status' => 'Disetujui',
                'status_leader' => 'Disetujui',
                'pengaju' => $user,
                'status_pengambilan' => 'Sudah Diambil',
                'tgl_pengajuan' => $tanggal,
                'tgl_keluar' => $tanggal,
            ]);

            foreach ($stockOpname->stockOpnameDetails as $detail) {
                $selisih = $detail->selisih_audit;

                // Abaikan jika selisih positif
                if ($selisih > 0) continue;

                $selisih = abs($selisih);
                $groupedDetails = [];
                $produkId = $detail->produk_id;
                if ($produkId) {
                    // Kasus: Bahan setengah jadi
                    $produkDetail = BahanSetengahJadiDetails::where('id', $produkId)->first();

                    if ($produkDetail && $produkDetail->sisa >= $selisih) {
                        $produkDetail->sisa -= $selisih;
                        $produkDetail->save();

                        $groupedDetails[] = [
                            'qty' => $selisih,
                            'unit_price' => $produkDetail->unit_price ?? 0
                        ];

                        BahanKeluarDetails::create([
                            'bahan_keluar_id' => $bahan_keluar->id,
                            'bahan_id' => null,
                            'produk_id' => $produkId,
                            'qty' => $selisih,
                            'jml_bahan' => $selisih,
                            'used_materials' => $selisih,
                            'serial_number' => $produkDetail->serial_number ?? null,
                            'details' => json_encode($groupedDetails),
                            'sub_total' => $selisih * ($produkDetail->unit_price ?? 0),
                        ]);
                    }

                } else {
                    // Kasus: Bahan biasa
                    $purchaseDetails = PurchaseDetail::where('bahan_id', $detail->dataBahan->id)
                        ->where('sisa', '>', 0)
                        ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
                        ->orderBy('purchases.tgl_masuk', 'asc')
                        ->select('purchase_details.*', 'purchases.tgl_masuk')
                        ->get();

                    $detailsArray = [];

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
                        'produk_id' => null,
                        'qty' => array_sum(array_column($groupedDetails, 'qty')),
                        'jml_bahan' => array_sum(array_column($groupedDetails, 'qty')),
                        'used_materials' => array_sum(array_column($groupedDetails, 'qty')),
                        'details' => json_encode(array_values($groupedDetails)),
                        'sub_total' => array_sum(array_map(function ($item) {
                            return $item['qty'] * $item['unit_price'];
                        }, $groupedDetails)),
                    ]);
                }
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
