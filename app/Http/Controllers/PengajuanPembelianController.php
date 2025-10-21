<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Models\User;
use App\Models\Bahan;
use App\Models\Projek;
use App\Models\StokRnd;
use App\Models\Produksi;
use App\Models\Pengajuan;
use App\Helpers\LogHelper;
use App\Models\BahanKeluar;
use App\Models\StokProduksi;
use Illuminate\Http\Request;
use App\Models\ProjekDetails;
use App\Models\PembelianBahan;
use App\Models\ProdukProduksi;
use App\Models\PurchaseDetail;
use App\Models\ProduksiDetails;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PengajuanDetails;
use App\Models\PengambilanBahan;
use App\Models\ProjekRndDetails;
use App\Jobs\SendWhatsAppMessage;
use App\Models\BahanKeluarDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PembelianBahanExport;
use App\Models\PembelianBahanDetails;
use App\Jobs\SendWhatsAppNotification;
use App\Models\PengambilanBahanDetails;
use Illuminate\Support\Facades\Storage;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Support\Facades\Validator;

class PengajuanPembelianController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-pengajuan', ['only' => ['index']]);
        // $this->middleware('permission:tambah-pembelian', ['only' => ['create','store']]);
        // $this->middleware('permission:edit-pengajuan-purchasing', ['only' => ['edit']]);
    }

    public function index()
    {
        return view('pages.pengajuan-pembelian.index');
    }

    public function downloadPdf(int $id)
    {
        try {
            $pembelianBahan = PembelianBahan::with([
                'dataUser.atasanLevel1',
                'dataUser.atasanLevel2',
                'dataUser.atasanLevel3',
                'pembelianBahanDetails.dataBahan.dataUnit',
            ])->findOrFail($id);

            $ongkir = $pembelianBahan->ongkir ?? 0;
            $asuransi = $pembelianBahan->asuransi ?? 0;
            $layanan = $pembelianBahan->layanan ?? 0;
            $jasa_aplikasi = $pembelianBahan->jasa_aplikasi ?? 0;
            $ppn = $pembelianBahan->ppn ?? 0;
            $status = $pembelianBahan->status ?? null;
            $status_leader = $pembelianBahan->status_leader ?? null;
            $status_purchasing = $pembelianBahan->status_purchasing ?? null;
            $status_manager = $pembelianBahan->status_manager ?? null;
            $status_finance = $pembelianBahan->status_finance ?? null;
            $status_admin_manager = $pembelianBahan->status_admin_manager ?? null;
            $status_general_manager = $pembelianBahan->status_general_manager ?? null;
            $jenis_pengajuan = $pembelianBahan->jenis_pengajuan ?? null;
            $shipping_cost = $pembelianBahan->shipping_cost ?? 0;
            $full_amount_fee = $pembelianBahan->full_amount_fee ??  0;
            $value_today_fee = $pembelianBahan->value_today_fee ??  0;

            $new_shipping_cost = $pembelianBahan->new_shipping_cost ?? 0;
            $new_full_amount_fee = $pembelianBahan->new_full_amount_fee ??  0;
            $new_value_today_fee = $pembelianBahan->new_value_today_fee ??  0;

            $shipping_cost_usd = $pembelianBahan->shipping_cost_usd ?? 0;
            $full_amount_fee_usd = $pembelianBahan->full_amount_fee_usd ??  0;
            $value_today_fee_usd = $pembelianBahan->value_today_fee_usd ??  0;

            $new_shipping_cost_usd = $pembelianBahan->new_shipping_cost_usd ?? 0;
            $new_full_amount_fee_usd = $pembelianBahan->new_full_amount_fee_usd ??  0;
            $new_value_today_fee_usd = $pembelianBahan->new_value_today_fee_usd ??  0;

            $tandaTanganPengaju = $pembelianBahan->dataUser->tanda_tangan ?? null;

            $tandaTanganLeader = null;
            $tandaTanganManager = $pembelianBahan->dataUser->atasanLevel2->tanda_tangan ?? null;
            $tandaTanganDirektur = $pembelianBahan->dataUser->atasanLevel1->tanda_tangan ?? null;

            if ($pembelianBahan->dataUser->atasanLevel3) {
                $tandaTanganLeader = $pembelianBahan->dataUser->atasanLevel3->tanda_tangan ?? null;
            } elseif ($pembelianBahan->dataUser->atasanLevel2) {
                $tandaTanganLeader = $pembelianBahan->dataUser->atasanLevel2->tanda_tangan ?? null;
            }

            $leaderName = $pembelianBahan->dataUser->atasanLevel3 ? $pembelianBahan->dataUser->atasanLevel3->name : null;
            $managerName = $pembelianBahan->dataUser->atasanLevel2 ? $pembelianBahan->dataUser->atasanLevel2->name : null;
            $direkturName = $pembelianBahan->dataUser->atasanLevel1 ? $pembelianBahan->dataUser->atasanLevel1->name : null;

            if (!$leaderName && $managerName) {
                $leaderName = $managerName;
            }

            if ($pembelianBahan->dataUser->job_level == 3) {
                $tandaTanganLeader = $tandaTanganPengaju;
                $leaderName = $pembelianBahan->dataUser->name;
            }

            $purchasingUser = cache()->remember('purchasing_user', 60, function () {
                return User::where('job_level', 3)
                    ->whereHas('dataJobPosition', function ($query) {
                        $query->where('nama', 'Purchasing');
                    })->first();
            });

            $generalUser = cache()->remember('general_user', 60, function () {
                return User::where('job_level', 3)
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'general_affair');
                    })
                    ->first();
            });


            $tandaTanganPurchasing = $purchasingUser->tanda_tangan ?? null;

            $tandaTanganGeneral= $generalUser->tanda_tangan ?? null;

            $financeUser = cache()->remember('finance_user', 60, function () {
                return User::where('name', 'MARITZA ISYAURA PUTRI RIZMA')->first();
            });
            $tandaTanganFinance = $financeUser->tanda_tangan ?? null;

            $adminManagerceUser = cache()->remember('admin_manager_user', 60, function () {
                return User::where('job_level', 2)
                    ->whereHas('dataJobPosition', function ($query) {
                        $query->where('nama', 'Admin Manager');
                    })->first();
            });
            $tandaTanganAdminManager = $adminManagerceUser->tanda_tangan ?? null;

            $pdf = Pdf::loadView('pages.pembelian-bahan.pdf', compact(
                'pembelianBahan','status_leader','status_purchasing','status_manager','status_finance','status_admin_manager','status_general_manager',
                'tandaTanganPengaju',
                'tandaTanganLeader',
                'tandaTanganManager',
                'tandaTanganDirektur',
                'tandaTanganPurchasing','tandaTanganGeneral',
                'purchasingUser','generalUser',
                'tandaTanganFinance','new_shipping_cost','new_full_amount_fee','new_value_today_fee',
                'financeUser','new_shipping_cost_usd','new_full_amount_fee_usd','new_value_today_fee_usd',
                'tandaTanganAdminManager','shipping_cost_usd','full_amount_fee_usd','value_today_fee_usd',
                'adminManagerceUser','shipping_cost','full_amount_fee','value_today_fee', 'ppn',
                'leaderName','status','jenis_pengajuan',
                'managerName','ongkir','layanan','jasa_aplikasi','asuransi'
            ));
            return $pdf->stream("pembelian_bahan_{$id}.pdf");

            LogHelper::success('Berhasil generating PDF for pembelianBahan ID {$id}!');
            return $pdf->download("pembelian_bahan_{$id}.pdf");

        } catch (\Exception $e) {
            LogHelper::error("Error generating PDF for pembelianBahan ID {$id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengunduh PDF.');
        }
    }

    public function downloadPdfPo(int $id)
    {
        try {
            $pembelianBahan = PembelianBahan::with([
                'dataUser.atasanLevel1',
                'dataUser.atasanLevel2',
                'dataUser.atasanLevel3',
                'pembelianBahanDetails.dataBahan.dataUnit',
            ])->findOrFail($id);

            $ongkir = $pembelianBahan->ongkir ?? 0;
            $asuransi = $pembelianBahan->asuransi ?? 0;
            $layanan = $pembelianBahan->layanan ?? 0;
            $jasa_aplikasi = $pembelianBahan->jasa_aplikasi ?? 0;
            $ppn = $pembelianBahan->ppn ?? 0;
            $status = $pembelianBahan->status ?? null;
            $status_leader = $pembelianBahan->status_leader ?? null;
            $status_purchasing = $pembelianBahan->status_purchasing ?? null;
            $status_manager = $pembelianBahan->status_manager ?? null;
            $status_finance = $pembelianBahan->status_finance ?? null;
            $status_admin_manager = $pembelianBahan->status_admin_manager ?? null;
            $status_general_manager = $pembelianBahan->status_general_manager ?? null;
            $jenis_pengajuan = $pembelianBahan->jenis_pengajuan ?? null;
            $shipping_cost = $pembelianBahan->shipping_cost ?? 0;
            $full_amount_fee = $pembelianBahan->full_amount_fee ??  0;
            $value_today_fee = $pembelianBahan->value_today_fee ??  0;

            $new_shipping_cost = $pembelianBahan->new_shipping_cost ?? 0;
            $new_full_amount_fee = $pembelianBahan->new_full_amount_fee ??  0;
            $new_value_today_fee = $pembelianBahan->new_value_today_fee ??  0;

            $shipping_cost_usd = $pembelianBahan->shipping_cost_usd ?? 0;
            $full_amount_fee_usd = $pembelianBahan->full_amount_fee_usd ??  0;
            $value_today_fee_usd = $pembelianBahan->value_today_fee_usd ??  0;

            $new_shipping_cost_usd = $pembelianBahan->new_shipping_cost_usd ?? 0;
            $new_full_amount_fee_usd = $pembelianBahan->new_full_amount_fee_usd ??  0;
            $new_value_today_fee_usd = $pembelianBahan->new_value_today_fee_usd ??  0;

            $tandaTanganPengaju = $pembelianBahan->dataUser->tanda_tangan ?? null;

            $tandaTanganLeader = null;
            $tandaTanganManager = $pembelianBahan->dataUser->atasanLevel2->tanda_tangan ?? null;
            $tandaTanganDirektur = $pembelianBahan->dataUser->atasanLevel1->tanda_tangan ?? null;

            if ($pembelianBahan->dataUser->atasanLevel3) {
                $tandaTanganLeader = $pembelianBahan->dataUser->atasanLevel3->tanda_tangan ?? null;
            } elseif ($pembelianBahan->dataUser->atasanLevel2) {
                $tandaTanganLeader = $pembelianBahan->dataUser->atasanLevel2->tanda_tangan ?? null;
            }

            $leaderName = $pembelianBahan->dataUser->atasanLevel3 ? $pembelianBahan->dataUser->atasanLevel3->name : null;
            $managerName = $pembelianBahan->dataUser->atasanLevel2 ? $pembelianBahan->dataUser->atasanLevel2->name : null;
            $direkturName = $pembelianBahan->dataUser->atasanLevel1 ? $pembelianBahan->dataUser->atasanLevel1->name : null;

            if (!$leaderName && $managerName) {
                $leaderName = $managerName;
            }

            if ($pembelianBahan->dataUser->job_level == 3) {
                $tandaTanganLeader = $tandaTanganPengaju;
                $leaderName = $pembelianBahan->dataUser->name;
            }

            $purchasingUser = cache()->remember('purchasing_user', 60, function () {
                return User::where('job_level', 3)
                    ->whereHas('dataJobPosition', function ($query) {
                        $query->where('nama', 'Purchasing');
                    })->first();
            });

            $generalUser = cache()->remember('general_user', 60, function () {
                return User::where('job_level', 3)
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'general_affair');
                    })
                    ->first();
            });


            $tandaTanganPurchasing = $purchasingUser->tanda_tangan ?? null;

            $tandaTanganGeneral= $generalUser->tanda_tangan ?? null;

            $financeUser = cache()->remember('finance_user', 60, function () {
                return User::where('name', 'MARITZA ISYAURA PUTRI RIZMA')->first();
            });
            $tandaTanganFinance = $financeUser->tanda_tangan ?? null;

            $adminManagerceUser = cache()->remember('admin_manager_user', 60, function () {
                return User::where('job_level', 2)
                    ->whereHas('dataJobPosition', function ($query) {
                        $query->where('nama', 'Admin Manager');
                    })->first();
            });
            $tandaTanganAdminManager = $adminManagerceUser->tanda_tangan ?? null;

            $pdf = Pdf::loadView('pages.pembelian-bahan.pdfpo', compact(
                'pembelianBahan','status_leader','status_purchasing','status_manager','status_finance','status_admin_manager','status_general_manager',
                'tandaTanganPengaju',
                'tandaTanganLeader',
                'tandaTanganManager',
                'tandaTanganDirektur',
                'tandaTanganPurchasing','tandaTanganGeneral',
                'purchasingUser','generalUser',
                'tandaTanganFinance','new_shipping_cost','new_full_amount_fee','new_value_today_fee',
                'financeUser','new_shipping_cost_usd','new_full_amount_fee_usd','new_value_today_fee_usd',
                'tandaTanganAdminManager','shipping_cost_usd','full_amount_fee_usd','value_today_fee_usd',
                'adminManagerceUser','shipping_cost','full_amount_fee','value_today_fee', 'ppn',
                'leaderName','status','jenis_pengajuan',
                'managerName','ongkir','layanan','jasa_aplikasi','asuransi'
            ));
            return $pdf->stream("pembelian_bahan_{$id}.pdf");

            LogHelper::success('Berhasil generating PDF for pembelianBahan ID {$id}!');
            return $pdf->download("pembelian_bahan_{$id}.pdf");

        } catch (\Exception $e) {
            LogHelper::error("Error generating PDF for pembelianBahan ID {$id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengunduh PDF.');
        }
    }

    public function create()
    {
        $units = Unit::all();
        $produkProduksi = ProdukProduksi::all();
        return view('pages.pengajuan-pembelian.create', compact('units', 'produkProduksi'));
    }

    public function store(Request $request)
    {
        try {
            // dd($request->all());
            DB::beginTransaction();
            // Validasi input
            $cartItems = json_decode($request->cartItems, true);
            $itemsAset = json_decode($request->itemsAset, true);
            $validator = Validator::make([
                'divisi' => $request->divisi,
                'project' => $request->project,
                'keterangan' => $request->keterangan,
                'jenis_pengajuan' => $request->jenis_pengajuan,
                'cartItems' => $cartItems,
                'itemsAset' => $itemsAset,
            ], [
                'divisi' => 'required',
                'project' => 'required',
                'keterangan' => 'required',
                'jenis_pengajuan' => 'required',
                'cartItems' => 'nullable|array',
                'itemsAset' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $tujuan = $request->project;
            $user = Auth::user();
            $jenisPengajuan = $request->jenis_pengajuan;

            if ($jenisPengajuan === 'Pembelian Bahan/Barang/Alat Lokal') {
                $prefix = 'PBL-';
            } elseif ($jenisPengajuan === 'Pembelian Bahan/Barang/Alat Impor') {
                $prefix = 'PBI-';
            } elseif ($jenisPengajuan === 'Pembelian Aset') {
                $prefix = 'PA-';
            } else {
                $prefix = 'PB-';
            }

            $purchasingUser = User::whereHas('dataJobPosition', function ($query) {
                $query->where('nama', 'Purchasing');
            })->where('job_level', 3)->first();

            $generalAffairUser = User::whereHas('roles', function ($query) {
                $query->where('name', 'general_affair');
            })->first();

            $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            // Buat kode transaksi berdasarkan jenis pengajuan
            $lastTransaksi = PembelianBahan::latest()->first();
            $nextNumber = $lastTransaksi ? intval(substr($lastTransaksi->kode_transaksi, -4)) + 1 : 1;
            $kode_transaksi = $prefix . date('Ymd') . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            if ($jenisPengajuan === 'Pembelian Bahan/Barang/Alat Lokal' || $jenisPengajuan === 'Pembelian Bahan/Barang/Alat Impor') {
                if ($user->job_level == 3 && $user->atasan_level3_id === null) {
                    // Job level 3 dan atasan_level3_id null
                    $status_leader = 'Disetujui';
                    $status_manager = 'Belum disetujui'; // Menunggu approval manager
                    // Kirim notifikasi ke Purchasing
                    $targetPhone = $purchasingUser ? $purchasingUser->telephone : null;
                    $recipientName = $purchasingUser ? $purchasingUser->name : 'Purchasing';
                } elseif ($user->job_level == 4 && $user->atasan_level3_id === null && $user->atasan_level2_id === null) {
                    // Job level 4 dan atasan_level3_id, atasan_level2_id null
                    $status_leader = 'Disetujui';
                    $status_manager = 'Disetujui'; // Menunggu approval manager
                    // Kirim notifikasi ke Purchasing
                    $targetPhone = $purchasingUser ? $purchasingUser->telephone : null;
                    $recipientName = $purchasingUser ? $purchasingUser->name : 'Purchasing';
                } elseif ($user->job_level == 4 && $user->atasan_level3_id === null) {
                    // Job level 4 dan atasan_level3_id null
                    $status_leader = 'Belum disetujui';
                    $status_manager = 'Belum disetujui'; // Menunggu approval manager
                    // Kirim notifikasi ke atasan level 2
                    $targetPhone = $user->atasanLevel2 ? $user->atasanLevel2->telephone : null;
                    $recipientName = $user->atasanLevel2 ? $user->atasanLevel2->name : 'Manager';
                } elseif ($user->job_level == 4) {
                    // Job level 4 dan atasan_level3_id tidak null
                    $status_leader = 'Belum disetujui';
                    $status_manager = 'Belum disetujui'; // Menunggu approval manager
                    // Kirim notifikasi ke atasan level 3
                    $targetPhone = $user->atasanLevel3 ? $user->atasanLevel3->telephone : null;
                    $recipientName = $user->atasanLevel3 ? $user->atasanLevel3->name : 'Leader';
                } else {
                    // Job level lainnya, kirim ke Purchasing
                    $status_leader = 'Belum disetujui';
                    $status_manager = 'Belum disetujui'; // Menunggu approval manager
                    $targetPhone = $purchasingUser ? $purchasingUser->telephone : null;
                    $recipientName = $purchasingUser ? $purchasingUser->name : 'Purchasing';
                }
            }else{
                if ($user->job_level == 3 && $user->atasan_level3_id === null) {
                    // Job level 3 dan atasan_level3_id null
                    $status_leader = 'Disetujui';
                    $status_manager = 'Belum disetujui'; // Menunggu approval manager
                    // Kirim notifikasi ke General Affair
                    $targetPhone = $generalAffairUser ? $generalAffairUser->telephone : null;
                    $recipientName = $generalAffairUser ? $generalAffairUser->name : 'General Affair';
                } elseif ($user->job_level == 4 && $user->atasan_level3_id === null && $user->atasan_level2_id === null) {
                    // Job level 4 dan atasan_level3_id, atasan_level2_id null
                    $status_leader = 'Disetujui';
                    $status_manager = 'Disetujui'; // Menunggu approval manager
                    // Kirim notifikasi ke General Affair
                    $targetPhone = $generalAffairUser ? $generalAffairUser->telephone : null;
                    $recipientName = $generalAffairUser ? $generalAffairUser->name : 'General Affair';
                } elseif ($user->job_level == 4 && $user->atasan_level3_id === null) {
                    // Job level 4 dan atasan_level3_id null
                    $status_leader = 'Belum disetujui';
                    $status_manager = 'Belum disetujui'; // Menunggu approval manager
                    // Kirim notifikasi ke atasan level 2
                    $targetPhone = $user->atasanLevel2 ? $user->atasanLevel2->telephone : null;
                    $recipientName = $user->atasanLevel2 ? $user->atasanLevel2->name : 'Manager';
                } elseif ($user->job_level == 4) {
                    // Job level 4 dan atasan_level3_id tidak null
                    $status_leader = 'Belum disetujui';
                    $status_manager = 'Belum disetujui'; // Menunggu approval manager
                    // Kirim notifikasi ke atasan level 3
                    $targetPhone = $user->atasanLevel3 ? $user->atasanLevel3->telephone : null;
                    $recipientName = $user->atasanLevel3 ? $user->atasanLevel3->name : 'Leader';
                } else {
                    // Job level lainnya, kirim ke General Affair
                    $status_leader = 'Belum disetujui';
                    $status_manager = 'Belum disetujui'; // Menunggu approval manager
                    $targetPhone = $generalAffairUser ? $generalAffairUser->telephone : null;
                    $recipientName = $generalAffairUser ? $generalAffairUser->name : 'General Affair';
                }
            }

            $pembelian_bahan = PembelianBahan::create([
                'kode_transaksi' => $kode_transaksi,
                'tgl_pengajuan' => $tgl_pengajuan,
                'tujuan' => $tujuan,
                'keterangan' => $request->keterangan,
                'divisi' => $request->divisi,
                'pengaju' => $user->id,
                'jenis_pengajuan' => $request->jenis_pengajuan,
                // 'status_pengambilan' => 'Belum Diambil',
                'status' => 'Belum disetujui',
                'status_leader' => $status_leader,
                'status_manager' => $status_manager,
            ]);

            if ($jenisPengajuan === 'Pembelian Aset') {
                foreach ($itemsAset as $item) {
                    PembelianBahanDetails::create([
                        'pembelian_bahan_id' => $pembelian_bahan->id,
                        'nama_bahan' => $item['nama_bahan'],
                        'qty' => 0,
                        'jml_bahan' => $item['jml_bahan'],
                        'used_materials' => 0,
                        'spesifikasi' => $item['spesifikasi'],
                        'penanggungjawabaset' => $item['penanggungjawabaset'],
                        'alasan' => $item['alasan'],
                    ]);
                }
            } else {
                // Group items by bahan_id dan simpan
                foreach ($cartItems as $item) {
                    PembelianBahanDetails::create([
                        'pembelian_bahan_id' => $pembelian_bahan->id,
                        'bahan_id' => $item['id'],
                        'qty' => $item['qty'],
                        'jml_bahan' => $item['jml_bahan'],
                        'qty_pengajuan' => $item['qty_pengajuan'],
                        'used_materials' => 0,
                        'details' => json_encode($item['details']),
                        'sub_total' => $item['sub_total'],
                        'spesifikasi' => $item['spesifikasi'],
                        'penanggungjawabaset' => $item['penanggungjawabaset'],
                        'alasan' => $item['alasan'],
                    ]);
                }
            }

            // Kirim notifikasi jika nomor telepon valid
            if ($targetPhone) {
                $message = "Halo {$recipientName},\n\n";
                $message .= "Pengajuan pembelian bahan dengan kode transaksi $kode_transaksi memerlukan persetujuan Anda.\n\n";
                $message .= "Tgl Pengajuan: " . $tgl_pengajuan . "\nPengaju: {$user->name}\nDivisi: {$request->divisi}\nProject: {$request->project}\nKeterangan: {$request->keterangan}\n\n";
                $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";

                // Dispatch Job
                SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
            }

            DB::commit();
            $request->session()->forget('cartItems');
            LogHelper::success('Berhasil Menambahkan Pengajuan Bahan!');
            return redirect()->back()->with('success', 'Berhasil Menambahkan Pengajuan Bahan!');
        } catch (\Exception $e) {
            DB::rollBack();
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menambahkan data: ' . $e->getMessage());
        }
    }

    public function edit(string $id)
    {
        $units = Unit::all();

        $pembelian_bahan = PembelianBahan::with(['pembelianBahanDetails'])->findOrFail($id);

        return view('pages.pengajuan-pembelian.edit', [
            'pembelianBahanId' => $id,
            'pembelian_bahan' => $pembelian_bahan,
            'units' => $units,
        ]);
    }

    public function show(string $id)
    {
        $bahankeluar = BahanKeluar::with('bahanKeluarDetails.dataBahan.dataUnit')->findOrFail($id); // Mengambil detail pembelian
        return view('pages.pembelian-bahan.show', [
            'kode_transaksi' => $bahankeluar->kode_transaksi,
            'tgl_keluar' => $bahankeluar->tgl_keluar,
            'divisi' => $bahankeluar->divisi,
            'bahanKeluarDetails' => $bahankeluar->bahanKeluarDetails,
        ]);
    }

    public function update(Request $request, string $id)
    {
        // Debug request data (optional, for testing)
        // dd($request->all());
        try {
            DB::beginTransaction();
            $validatedData = $request->validate([
                'pembelianBahanDetails' => 'required|string',
                'biaya' => 'required|string',
            ]);

            // Decode pembelianBahanDetails and biaya
            $pembelianBahanDetails = json_decode($validatedData['pembelianBahanDetails'], true);
            $biaya = json_decode($validatedData['biaya'], true);

            if (!is_array($pembelianBahanDetails) || !is_array($biaya)) {
                return redirect()->back()->with('error', 'Data tidak valid.');
            }

            // Update or create pembelian bahan details
            foreach ($pembelianBahanDetails as $item) {
                $bahanId = $item['id'] ?? null;
                $namaBahan = $item['nama_bahan'] ?? null;

                // Tentukan kondisi pencarian: jika bahan_id ada, gunakan itu; jika tidak, gunakan nama_bahan
                $conditions = ['pembelian_bahan_id' => $id];
                if ($bahanId) {
                    $conditions['bahan_id'] = $bahanId;
                } elseif ($namaBahan) {
                    $conditions['nama_bahan'] = $namaBahan;
                }
                PembelianBahanDetails::updateOrCreate(
                    $conditions,
                    [
                        'bahan_id' => $bahanId, // Bisa null jika tidak ada bahan_id
                        'nama_bahan' => $namaBahan,
                        'qty' => $item['qty'],
                        'jml_bahan' => $item['jml_bahan'],
                        'used_materials' => 0,
                        'details' => json_encode($item['details']),
                        'details_usd' => json_encode($item['details_usd']),
                        'sub_total' => $item['sub_total'],
                        'sub_total_usd' => $item['sub_total_usd'],
                        'keterangan_pembayaran' => $item['keterangan_pembayaran'] ?? '',
                    ]
                );
            }

            // Update biaya di tabel PembelianBahan
            PembelianBahan::where('id', $id)->update([
                'ongkir' => $biaya['ongkir'] ?? 0,
                'ppn' => $biaya['ppn'] ?? 0,
                'asuransi' => $biaya['asuransi'] ?? 0,
                'layanan' => $biaya['layanan'] ?? 0,
                'jasa_aplikasi' => $biaya['jasa_aplikasi'] ?? 0,
                'shipping_cost' => $biaya['shipping_cost'] ?? 0,
                'full_amount_fee' => $biaya['full_amount_fee'] ?? 0,
                'value_today_fee' => $biaya['value_today_fee'] ?? 0,
                'shipping_cost_usd' => $biaya['shipping_cost_usd'] ?? 0,
                'full_amount_fee_usd' => $biaya['full_amount_fee_usd'] ?? 0,
                'value_today_fee_usd' => $biaya['value_today_fee_usd'] ?? 0,
            ]);

            DB::commit();
            LogHelper::success('Pembelian Bahan berhasil diubah.');
            // return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Pembelian Bahan berhasil diubah.');
            $page = $request->input('page', 1);
            return redirect()->route('pengajuan-pembelian.index', ['page' => $page])->with('success', 'Pembelian Bahan berhasil diubah.');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan. Pesan error: $errorMessage");
        }
    }

    public function destroy(Request $request, string $id)
    {
        try{
            $data = PembelianBahan::find($id);
            if (!$data) {
                return redirect()->back()->with('gagal', 'Transaksi tidak ditemukan.');
            }
            $data->delete();
            LogHelper::success('Berhasil Menghapus Pengajuan Pembelian Bahan!');
            // return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Berhasil Menghapus Pengajuan Pembelian Bahan!');
            $page = $request->input('page', 1);
            return redirect()->route('pengajuan-pembelian-bahan.index', ['page' => $page])->with('success', 'Berhasil Menghapus Pengajuan Pembelian Bahan!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
