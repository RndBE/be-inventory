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
            $jenis_pengajuan = $pembelianBahan->base_jenis_pengajuan;
            $currency = $pembelianBahan->currency;
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

            // Kategori Riset: slot Leader diputus Manager (atasan level 2), jadi
            // nama & tanda tangannya yang muncul di kolom Leader.
            $approverLeader = $pembelianBahan->approverLeader();

            if ($approverLeader) {
                $tandaTanganLeader = $approverLeader->tanda_tangan ?? null;
            }

            $leaderName = $approverLeader->name ?? null;
            $managerName = $pembelianBahan->dataUser->atasanLevel2 ? $pembelianBahan->dataUser->atasanLevel2->name : null;
            $direkturName = $pembelianBahan->dataUser->atasanLevel1 ? $pembelianBahan->dataUser->atasanLevel1->name : null;

            if (!$leaderName && $managerName) {
                $leaderName = $managerName;
            }

            $jobLevelPengaju = $pembelianBahan->dataUser->job_level;

            if ($jobLevelPengaju !== null && (int) $jobLevelPengaju <= 2) {
                // Pengaju setingkat Manager: slot Leader & Manager miliknya sendiri.
                $tandaTanganLeader = $tandaTanganPengaju;
                $leaderName = $pembelianBahan->dataUser->name;
                $tandaTanganManager = $tandaTanganPengaju;
                $managerName = $pembelianBahan->dataUser->name;
            } elseif ((int) $jobLevelPengaju === 3 && ! $pembelianBahan->leaderDiputusManager()) {
                // job_level 3 adalah Leader-nya sendiri — kecuali kategori Riset,
                // yang slot Leader-nya justru diputus Manager.
                $tandaTanganLeader = $tandaTanganPengaju;
                $leaderName = $pembelianBahan->dataUser->name;
            }

            $purchasingUser = $this->resolvePurchasingUser($pembelianBahan->tgl_pengajuan ?? null);

            $pengisiHargaUser = cache()->remember('pengisi_harga_user_' . $pembelianBahan->pengisi_harga, 60, function () use ($pembelianBahan) {
                return User::where('name', $pembelianBahan->pengisi_harga)->first();
            });

            $generalUser = cache()->remember('general_user', 60, function () {
                return User::where('job_level', 3)
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'general_affair');
                    })
                    ->first();
            });


            $tandaTanganPurchasing = $purchasingUser->tanda_tangan ?? null;

            $tandaTanganPengisiHarga = $pengisiHargaUser->tanda_tangan ?? null;

            $tandaTanganGeneral = $generalUser->tanda_tangan ?? null;

            $financeUser = $this->resolveFinanceUser($pembelianBahan->tgl_pengajuan ?? null);
            $tandaTanganFinance = $financeUser->tanda_tangan ?? null;

            $adminManagerceUser = cache()->remember('admin_manager_user', 60, function () {
                return User::where('job_level', 2)
                    ->whereHas('dataJobPosition', function ($query) {
                        $query->where('nama', 'Admin Manager');
                    })->first();
            });
            $tandaTanganAdminManager = $adminManagerceUser->tanda_tangan ?? null;

            $pdf = Pdf::loadView('pages.pembelian-bahan.pdf', compact(
                'pembelianBahan',
                'status_leader',
                'status_purchasing',
                'status_manager',
                'status_finance',
                'status_admin_manager',
                'status_general_manager',
                'tandaTanganPengaju',
                'tandaTanganLeader',
                'tandaTanganManager',
                'tandaTanganDirektur',
                'tandaTanganPurchasing',
                'tandaTanganGeneral',
                'purchasingUser',
                'generalUser',
                'tandaTanganFinance',
                'new_shipping_cost',
                'new_full_amount_fee',
                'new_value_today_fee',
                'financeUser',
                'new_shipping_cost_usd',
                'new_full_amount_fee_usd',
                'new_value_today_fee_usd',
                'tandaTanganAdminManager',
                'shipping_cost_usd',
                'full_amount_fee_usd',
                'value_today_fee_usd',
                'adminManagerceUser',
                'shipping_cost',
                'full_amount_fee',
                'value_today_fee',
                'ppn',
                'leaderName',
                'status',
                'currency',
                'jenis_pengajuan',
                'managerName',
                'ongkir',
                'layanan',
                'jasa_aplikasi',
                'asuransi',
                'pengisiHargaUser',
                'tandaTanganPengisiHarga'
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
            $jenis_pengajuan = $pembelianBahan->base_jenis_pengajuan;
            $currency = $pembelianBahan->currency;
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

            // Kategori Riset: slot Leader diputus Manager (atasan level 2), jadi
            // nama & tanda tangannya yang muncul di kolom Leader.
            $approverLeader = $pembelianBahan->approverLeader();

            if ($approverLeader) {
                $tandaTanganLeader = $approverLeader->tanda_tangan ?? null;
            }

            $leaderName = $approverLeader->name ?? null;
            $managerName = $pembelianBahan->dataUser->atasanLevel2 ? $pembelianBahan->dataUser->atasanLevel2->name : null;
            $direkturName = $pembelianBahan->dataUser->atasanLevel1 ? $pembelianBahan->dataUser->atasanLevel1->name : null;

            if (!$leaderName && $managerName) {
                $leaderName = $managerName;
            }

            $jobLevelPengaju = $pembelianBahan->dataUser->job_level;

            if ($jobLevelPengaju !== null && (int) $jobLevelPengaju <= 2) {
                // Pengaju setingkat Manager: slot Leader & Manager miliknya sendiri.
                $tandaTanganLeader = $tandaTanganPengaju;
                $leaderName = $pembelianBahan->dataUser->name;
                $tandaTanganManager = $tandaTanganPengaju;
                $managerName = $pembelianBahan->dataUser->name;
            } elseif ((int) $jobLevelPengaju === 3 && ! $pembelianBahan->leaderDiputusManager()) {
                // job_level 3 adalah Leader-nya sendiri — kecuali kategori Riset,
                // yang slot Leader-nya justru diputus Manager.
                $tandaTanganLeader = $tandaTanganPengaju;
                $leaderName = $pembelianBahan->dataUser->name;
            }

            $purchasingUser = $this->resolvePurchasingUser($pembelianBahan->tgl_pengajuan ?? null);

            $generalUser = cache()->remember('general_user', 60, function () {
                return User::where('job_level', 3)
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'general_affair');
                    })
                    ->first();
            });


            $tandaTanganPurchasing = $purchasingUser->tanda_tangan ?? null;

            $tandaTanganGeneral = $generalUser->tanda_tangan ?? null;

            $financeUser = $this->resolveFinanceUser($pembelianBahan->tgl_pengajuan ?? null);
            $tandaTanganFinance = $financeUser->tanda_tangan ?? null;

            $adminManagerceUser = cache()->remember('admin_manager_user', 60, function () {
                return User::where('job_level', 2)
                    ->whereHas('dataJobPosition', function ($query) {
                        $query->where('nama', 'Admin Manager');
                    })->first();
            });
            $tandaTanganAdminManager = $adminManagerceUser->tanda_tangan ?? null;

            $pdf = Pdf::loadView('pages.pembelian-bahan.pdfpo', compact(
                'pembelianBahan',
                'status_leader',
                'status_purchasing',
                'status_manager',
                'status_finance',
                'status_admin_manager',
                'status_general_manager',
                'tandaTanganPengaju',
                'tandaTanganLeader',
                'tandaTanganManager',
                'tandaTanganDirektur',
                'tandaTanganPurchasing',
                'tandaTanganGeneral',
                'purchasingUser',
                'generalUser',
                'tandaTanganFinance',
                'new_shipping_cost',
                'new_full_amount_fee',
                'new_value_today_fee',
                'financeUser',
                'new_shipping_cost_usd',
                'new_full_amount_fee_usd',
                'new_value_today_fee_usd',
                'tandaTanganAdminManager',
                'shipping_cost_usd',
                'full_amount_fee_usd',
                'value_today_fee_usd',
                'adminManagerceUser',
                'shipping_cost',
                'full_amount_fee',
                'value_today_fee',
                'ppn',
                'leaderName',
                'status',
                'currency',
                'jenis_pengajuan',
                'managerName',
                'ongkir',
                'layanan',
                'jasa_aplikasi',
                'asuransi'
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
                'kategori_pengajuan' => $request->kategori_pengajuan,
                'cartItems' => $cartItems,
                'itemsAset' => $itemsAset,
            ], [
                'divisi' => 'required',
                'project' => 'required',
                'keterangan' => 'required',
                'jenis_pengajuan' => 'required',
                'kategori_pengajuan' => 'nullable|in:' . PembelianBahan::KATEGORI_PRODUKSI . ',' . PembelianBahan::KATEGORI_RISET,
                'cartItems' => 'nullable|array',
                'itemsAset' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $tujuan = $request->project;
            $user = Auth::user();
            $jenisPengajuan = $request->jenis_pengajuan;

            // Kategori hanya berlaku untuk Pembelian Bahan/Barang/Alat; jenis Aset
            // memakai alur General Affair sehingga kolomnya dibiarkan null.
            $pakaiKategori = in_array($jenisPengajuan, PembelianBahan::JENIS_PAKAI_KATEGORI, true);
            $kategoriPengajuan = $pakaiKategori
                ? ($request->kategori_pengajuan ?: PembelianBahan::KATEGORI_PRODUKSI)
                : null;

            // dd($jenisPengajuan);

            if ($jenisPengajuan === 'Pembelian Bahan/Barang/Alat Lokal') {
                $prefix = 'PBL-';
            } elseif ($jenisPengajuan === 'Pembelian Bahan/Barang/Alat Impor') {
                $prefix = 'PBI-';
            } elseif ($jenisPengajuan === 'Pembelian Aset Lokal') {
                $prefix = 'PAL-';
            } elseif ($jenisPengajuan === 'Pembelian Aset Impor') {
                $prefix = 'PAI-';
            } else {
                $prefix = 'PB-';
            }

            $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $purchasingUser = $this->resolvePurchasingUser($tgl_pengajuan);

            $generalAffairUser = User::whereHas('roles', function ($query) {
                $query->where('name', 'general_affair');
            })->first();

            // Buat kode transaksi berdasarkan jenis pengajuan
            $lastTransaksi = PembelianBahan::latest()->first();
            $nextNumber = $lastTransaksi ? intval(substr($lastTransaksi->kode_transaksi, -4)) + 1 : 1;
            $kode_transaksi = $prefix . date('Ymd') . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            if ($jenisPengajuan === 'Pembelian Bahan/Barang/Alat Lokal' || $jenisPengajuan === 'Pembelian Bahan/Barang/Alat Impor') {
                if ($user->job_level !== null && (int) $user->job_level <= 2) {
                    // Pengaju sudah setingkat Manager, jadi slot Leader & Manager
                    // adalah dirinya sendiri — sejalan dengan job_level 3 yang slot
                    // Leader-nya otomatis disetujui. Berlaku untuk kedua kategori.
                    $status_leader = 'Disetujui';
                    $status_manager = 'Disetujui';
                    $targetPhone = $purchasingUser ? $purchasingUser->telephone : null;
                    $recipientName = $purchasingUser ? $purchasingUser->name : 'Purchasing';
                } elseif ($kategoriPengajuan === PembelianBahan::KATEGORI_RISET) {
                    // Riset: atasan level 3 dilewati, slot Leader diputus Manager
                    // (atasan level 2). Tidak ada tahap yang di-auto-approve.
                    $status_leader = PembelianBahan::statusLeaderAwal(
                        $kategoriPengajuan,
                        $user->atasan_level3_id,
                        $user->atasan_level2_id
                    );
                    $status_manager = $user->atasan_level2_id === null ? 'Disetujui' : 'Belum disetujui';

                    if ($user->atasan_level2_id === null) {
                        // Tidak ada Manager: langsung ke Purchasing.
                        $targetPhone = $purchasingUser ? $purchasingUser->telephone : null;
                        $recipientName = $purchasingUser ? $purchasingUser->name : 'Purchasing';
                    } else {
                        $targetPhone = $user->atasanLevel2 ? $user->atasanLevel2->telephone : null;
                        $recipientName = $user->atasanLevel2 ? $user->atasanLevel2->name : 'Manager';
                    }
                } elseif ($user->job_level == 3 && $user->atasan_level3_id === null && $user->atasan_level2_id === null) {
                    // Job level 3 dan atasan_level3_id null, atasan_level2_id null
                    $status_leader = 'Disetujui';
                    $status_manager = 'Disetujui'; // Menunggu approval manager
                    // Kirim notifikasi ke Purchasing
                    $targetPhone = $purchasingUser ? $purchasingUser->telephone : null;
                    $recipientName = $purchasingUser ? $purchasingUser->name : 'Purchasing';
                } elseif ($user->job_level == 3 && $user->atasan_level3_id === null) {
                    // Job level 3 dan atasan_level3_id null
                    $status_leader = 'Disetujui';
                    $status_manager = 'Belum disetujui'; // Menunggu approval manager
                    // Kirim notifikasi ke Purchasing
                    // $targetPhone = $purchasingUser ? $purchasingUser->telephone : null;
                    // $recipientName = $purchasingUser ? $purchasingUser->name : 'Purchasing';
                    $targetPhone = $user->atasanLevel2 ? $user->atasanLevel2->telephone : null;
                    $recipientName = $user->atasanLevel2 ? $user->atasanLevel2->name : 'Manager';
                } elseif ($user->job_level == 4 && $user->atasan_level3_id === null && $user->atasan_level2_id === null) {
                    // Job level 4 dan atasan_level3_id null, atasan_level2_id null
                    $status_leader = 'Disetujui';
                    $status_manager = 'Disetujui'; // Menunggu approval manager
                    // Kirim notifikasi ke Purchasing
                    $targetPhone = $purchasingUser ? $purchasingUser->telephone : null;
                    $recipientName = $purchasingUser ? $purchasingUser->name : 'Purchasing';
                } elseif ($user->job_level == 4 && $user->atasan_level3_id !== null &&$user->atasan_level2_id === null) {
                    // Job level 4 dan atasan_level3_id, atasan_level2_id null
                    $status_leader = 'Belum disetujui';
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
                if ($user->job_level == 3 && $user->atasan_level3_id === null && $user->atasan_level2_id === null) {
                    // Job level 3 dan atasan_level3_id null
                    $status_leader = 'Disetujui';
                    $status_manager = 'Disetujui'; // Menunggu approval manager
                    // Kirim notifikasi ke General Affair
                    $targetPhone = $generalAffairUser ? $generalAffairUser->telephone : null;
                    $recipientName = $generalAffairUser ? $generalAffairUser->name : 'General Affair';
                } elseif ($user->job_level == 3 && $user->atasan_level3_id === null) {
                    // Job level 3 dan atasan_level3_id null
                    $status_leader = 'Disetujui';
                    $status_manager = 'Belum disetujui'; // Menunggu approval manager
                    // Kirim notifikasi ke General Affair
                    $targetPhone = $generalAffairUser ? $generalAffairUser->telephone : null;
                    $recipientName = $generalAffairUser ? $generalAffairUser->name : 'General Affair';
                } elseif ($user->job_level == 4 && $user->atasan_level3_id === null && $user->atasan_level2_id === null) {
                    // Job level 4 dan atasan_level3_id null, atasan_level2_id null
                    $status_leader = 'Disetujui';
                    $status_manager = 'Disetujui'; // Menunggu approval manager
                    // Kirim notifikasi ke General Affair
                    $targetPhone = $generalAffairUser ? $generalAffairUser->telephone : null;
                    $recipientName = $generalAffairUser ? $generalAffairUser->name : 'General Affair';
                } elseif ($user->job_level == 4 && $user->atasan_level3_id !== null &&$user->atasan_level2_id === null) {
                    // Job level 4 dan atasan_level3_id, atasan_level2_id null
                    $status_leader = 'Belum disetujui';
                    $status_manager = 'Disetujui'; // Menunggu approval manager
                    // Kirim notifikasi ke General Affair
                    $targetPhone = $generalAffairUser ? $generalAffairUser->telephone : null;
                    $recipientName = $generalAffairUser ? $generalAffairUser->name : 'General Affair';
                } elseif ($user->job_level == 3 && $user->atasan_level3_id === null) {
                    // Job level 3 dan atasan_level3_id null
                    $status_leader = 'Disetujui';
                    $status_manager = 'Belum disetujui'; // Menunggu approval manager
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
            // dd($itemsAset);
            $pembelian_bahan = PembelianBahan::create([
                'kode_transaksi' => $kode_transaksi,
                'tgl_pengajuan' => $tgl_pengajuan,
                'tujuan' => $tujuan,
                'keterangan' => $request->keterangan,
                'divisi' => $request->divisi,
                'pengaju' => $user->id,
                'jenis_pengajuan' => str_contains($request->jenis_pengajuan ?? '', 'Impor') && $request->currency
                    ? $request->jenis_pengajuan . '|' . $request->currency
                    : $request->jenis_pengajuan,
                'kategori_pengajuan' => $kategoriPengajuan,
                // 'status_pengambilan' => 'Belum Diambil',
                'status' => 'Belum disetujui',
                'status_leader' => $status_leader,
                'status_manager' => $status_manager,
                // Tahap yang otomatis disetujui saat submit tetap dicap waktu,
                // supaya kolom selisih waktu di tabel approval tidak kosong dan
                // tahap sesudahnya punya titik pembanding.
                'tgl_approve_leader' => $status_leader === 'Disetujui' ? $tgl_pengajuan : null,
                'tgl_approve_manager' => $status_manager === 'Disetujui' ? $tgl_pengajuan : null,
            ]);

            if ($jenisPengajuan === 'Pembelian Aset Lokal' || $jenisPengajuan === 'Pembelian Aset Impor') {
                foreach ($itemsAset as $item) {
                    PembelianBahanDetails::create([
                        'pembelian_bahan_id' => $pembelian_bahan->id,
                        'nama_bahan' => $item['nama_bahan'],
                        'qty' => 0,
                        'jml_bahan' => $this->normalizeDecimal($item['jml_bahan'] ?? 0),
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
                        'qty' => $this->normalizeDecimal($item['qty'] ?? 0),
                        'jml_bahan' => $this->normalizeDecimal($item['jml_bahan'] ?? 0),
                        'qty_pengajuan' => $this->normalizeDecimal($item['qty_pengajuan'] ?? 0),
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

    /**
     * Simpan toggle kategori Produksi/Riset. Berdiri sendiri, terpisah dari
     * update() yang mengurus harga & biaya, supaya mengganti kategori tidak ikut
     * menimpa harga yang belum diisi.
     */
    public function updateKategori(Request $request, string $id)
    {
        $validated = $request->validate([
            'kategori_pengajuan' => 'required|in:' . PembelianBahan::KATEGORI_PRODUKSI . ',' . PembelianBahan::KATEGORI_RISET,
        ]);

        $page = $request->input('page', 1);

        try {
            $pembelianBahan = PembelianBahan::findOrFail($id);

            // Halaman edit menampilkan bag error validasi, bukan session('error'),
            // jadi pesan gagal dikirim lewat withErrors supaya terlihat.
            if (! $pembelianBahan->pakaiKategoriPengajuan()) {
                return redirect()->back()->withErrors(['error' => 'Jenis pengajuan ini tidak memakai kategori Produksi/Riset.']);
            }

            if (! $pembelianBahan->kategoriMasihBisaDiubah()) {
                return redirect()->back()->withErrors(['error' => 'Kategori tidak bisa diubah karena approval sudah berjalan.']);
            }

            DB::beginTransaction();
            $this->ubahKategoriPengajuan($id, $validated['kategori_pengajuan']);
            DB::commit();

            LogHelper::success('Kategori pengajuan pembelian berhasil diubah.');

            return redirect()->route('pengajuan-pembelian.index', ['page' => $page])
                ->with('success', 'Kategori pengajuan berhasil diubah menjadi ' . $validated['kategori_pengajuan'] . '.');
        } catch (\Exception $e) {
            DB::rollBack();
            LogHelper::error($e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat mengubah kategori: ' . $e->getMessage()]);
        }
    }

    /**
     * Pindah kategori Produksi <-> Riset pada pengajuan Bahan/Barang/Alat.
     *
     * Riset melewati approval Leader, Produksi memakainya kembali, jadi
     * status_leader ikut dihitung ulang. Hanya berlaku selama Manager belum
     * memutus apa pun; setelah itu kategori dibiarkan apa adanya supaya riwayat
     * approval tidak berubah di belakang approver.
     */
    private function ubahKategoriPengajuan(string $id, ?string $kategori): void
    {
        if ($kategori === null) {
            return;
        }

        $pembelianBahan = PembelianBahan::with(['dataUser.atasanLevel2', 'dataUser.atasanLevel3'])->find($id);

        if (! $pembelianBahan || ! $pembelianBahan->kategoriMasihBisaDiubah()) {
            return;
        }

        // Data sebelum kolom kategori ada bernilai null dan tampil sebagai
        // 'Produksi' di form. Menyimpan tanpa mengganti pilihan berarti kategori
        // tidak berubah: cukup diisi, status approval jangan disentuh — kalau
        // dihitung ulang, approval Leader yang sudah jatuh bisa balik menggantung.
        $kategoriSekarang = $pembelianBahan->kategori_pengajuan ?? PembelianBahan::KATEGORI_PRODUKSI;

        if ($kategoriSekarang === $kategori) {
            if ($pembelianBahan->kategori_pengajuan === null) {
                $pembelianBahan->kategori_pengajuan = $kategori;
                $pembelianBahan->save();
            }

            return;
        }

        $pengaju = $pembelianBahan->dataUser;

        // Hanya slot Leader yang dihitung ulang: pindah kategori memindah siapa
        // yang berhak memutusnya, sehingga approval lama di slot itu batal.
        // status_manager sengaja tidak disentuh — tahap itu jalan setelah
        // Purchasing dan bisa saja sudah diputus orang lain.
        $pembelianBahan->kategori_pengajuan = $kategori;
        $pembelianBahan->status_leader = PembelianBahan::statusLeaderAwal(
            $kategori,
            $pengaju->atasan_level3_id ?? null,
            $pengaju->atasan_level2_id ?? null
        );
        // Cap waktu ikut status: terisi bila otomatis disetujui, dikosongkan lagi
        // bila slot Leader kembali menunggu approver baru.
        $pembelianBahan->tgl_approve_leader = $pembelianBahan->status_leader === 'Disetujui'
            ? now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')
            : null;
        $pembelianBahan->save();

        // Approver slot Leader berubah, jadi kabari yang sekarang kebagian.
        if ($pembelianBahan->status_leader === 'Belum disetujui') {
            $targetUser = $pembelianBahan->approverLeader();
            $targetRole = $pembelianBahan->leaderDiputusManager() ? 'Manager' : 'Leader';
        } else {
            $targetUser = $this->resolvePurchasingUser($pembelianBahan->tgl_pengajuan ?? null);
            $targetRole = 'Purchasing';
        }

        $targetPhone = $targetUser->telephone ?? null;

        if (! $targetPhone) {
            LogHelper::error('No valid phone number found for WhatsApp notification.');

            return;
        }

        $recipientName = $targetUser->name;
        $message = "Halo {$recipientName},\n\n";
        $message .= "Pengajuan pembelian bahan dengan kode transaksi {$pembelianBahan->kode_transaksi} kini berkategori *{$kategori}* dan memerlukan persetujuan Anda sebagai {$targetRole}.\n\n";
        $message .= "Tgl Pengajuan: {$pembelianBahan->tgl_pengajuan}\nPengaju: {$pengaju->name}\nDivisi: {$pembelianBahan->divisi}\nProject: {$pembelianBahan->tujuan}\nKeterangan: {$pembelianBahan->keterangan}\n\n";
        $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";

        SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
    }

    private function normalizeDecimal($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $normalized = str_replace(',', '.', (string) $value);

        return is_numeric($normalized) ? (float) $normalized : 0;
    }

    private function resolvePurchasingUser($tglPengajuan = null)
    {
        if ($tglPengajuan && strtotime((string) $tglPengajuan) >= strtotime('2026-07-31 00:00:00')) {
            return cache()->remember('purchasing_user_lina', 60, function () {
                return User::where('name', 'LINA WIDIASTUTI')->first();
            });
        }

        return cache()->remember('purchasing_user_legacy', 60, function () {
            // Format lama untuk data sebelum 2026-07-31 WIB.
            return User::where('job_level', 3)
                ->whereHas('dataJobPosition', function ($query) {
                    $query->where('nama', 'Purchasing');
                })->first();
        });
    }

    private function resolveFinanceUser($tglPengajuan = null)
    {
        if ($tglPengajuan && strtotime((string) $tglPengajuan) >= strtotime('2026-07-31 00:00:00')) {
            return cache()->remember('finance_user_maritza', 60, function () {
                return User::where('name', 'MARITZA ISYAURA PUTRI RIZMA')->first();
            });
        }

        return cache()->remember('finance_user_legacy', 60, function () {
            // Format lama untuk data sebelum 2026-07-31 WIB.
            return User::where('name', 'LINA WIDIASTUTI')->first();
        });
    }

    public function destroy(Request $request, string $id)
    {
        try {
            $data = PembelianBahan::find($id);
            if (!$data) {
                return redirect()->back()->with('gagal', 'Transaksi tidak ditemukan.');
            }
            $data->delete();
            LogHelper::success('Berhasil Menghapus Pengajuan Pembelian Bahan!');
            // return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Berhasil Menghapus Pengajuan Pembelian Bahan!');
            $page = $request->input('page', 1);
            return redirect()->route('pengajuan-pembelian-bahan.index', ['page' => $page])->with('success', 'Berhasil Menghapus Pengajuan Pembelian Bahan!');
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
