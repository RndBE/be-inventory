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

class PembelianBahanController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-pembelian-bahan', ['only' => ['index']]);
        $this->middleware('permission:detail-pembelian-bahan', ['only' => ['show']]);
        $this->middleware('permission:tambah-pembelian-bahan', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit-pembelian-bahan', ['only' => ['updateApprovalFinance', 'updateApprovalAdminManager']]);
        $this->middleware('permission:edit-pengambilan', ['only' => ['updatepengambilan']]);
        $this->middleware('permission:edit-approvepembelian-leader', ['only' => ['updateApprovalLeader']]);
        $this->middleware('permission:edit-approvepembelian-gm', ['only' => ['updateApprovalGM']]);
        $this->middleware('permission:edit-approve-purchasing', ['only' => ['updateApprovalPurchasing']]);
        $this->middleware('permission:edit-pengajuan-purchasing', ['only' => ['update', 'edit']]);
        $this->middleware('permission:edit-approve-manager', ['only' => ['updateApprovalManager']]);
        $this->middleware('permission:update-harga-pembelian-bahan', ['only' => ['editHarga', 'updateHarga']]);
        $this->middleware('permission:upload-link-invoice', ['only' => ['uploadInvoice']]);
        // $this->middleware('permission:upload-dokumen', ['only' => ['uploadDokumen']]);
        $this->middleware('permission:hapus-pembelian-bahan', ['only' => ['destroy']]);
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

        return Excel::download(new PembelianBahanExport($startDate, $endDate, $companyName), 'Rekap Pembelian Bahan.xlsx');
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

            $pengisiHargaUser = cache()->remember('pengisi_harga_user_' . $pembelianBahan->pengisi_harga, 60, function () use ($pembelianBahan) {
                return User::where('name', $pembelianBahan->pengisi_harga)->first();
            });

            $generalUser = cache()->remember('general_user', 60, function () {
                return User::whereHas('roles', function ($query) {
                    $query->where('name', 'general_affair');
                })
                    ->first();
            });



            $tandaTanganPurchasing = $purchasingUser->tanda_tangan ?? null;

            $tandaTanganPengisiHarga = $pengisiHargaUser->tanda_tangan ?? null;

            $tandaTanganGeneral = $generalUser->tanda_tangan ?? null;

            $financeUser = cache()->remember('finance_user', 60, function () {
                return User::where('name', 'LINA WIDIASTUTI')->first();
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
                return User::whereHas('roles', function ($query) {
                    $query->where('name', 'general_affair');
                })
                    ->first();
            });

            $tandaTanganPurchasing = $purchasingUser->tanda_tangan ?? null;

            $tandaTanganGeneral = $generalUser->tanda_tangan ?? null;

            $financeUser = cache()->remember('finance_user', 60, function () {
                return User::where('name', 'LINA WIDIASTUTI')->first();
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

    public function index()
    {
        return view('pages.pembelian-bahan.index');
    }

    public function create()
    {
        return view('pages.pembelian-bahan.create');
    }

    public function edit(string $id)
    {
        $units = Unit::all();

        $pembelian_bahan = PembelianBahan::with(['pembelianBahanDetails'])->findOrFail($id);

        return view('pages.pembelian-bahan.edit', [
            'pembelianBahanId' => $id,
            'pembelian_bahan' => $pembelian_bahan,
            'units' => $units,
        ]);
    }

    public function editHarga(string $id)
    {
        $units = Unit::all();

        $pembelian_bahan = PembelianBahan::with(['pembelianBahanDetails'])->findOrFail($id);

        return view('pages.pembelian-bahan.edit-harga', [
            'pembelianBahanId' => $id,
            'pembelian_bahan' => $pembelian_bahan,
            'units' => $units,
        ]);
    }

    public function updateHarga(Request $request, int $id)
    {
        // Validate incoming request
        // dd($request->all());
        $validatedData = $request->validate([
            'pembelianBahanDetails' => 'required|string',
            'keterangan' => 'string|nullable',
            'link' => 'string|nullable',
            'biaya' => 'required|string',
        ]);

        // Decode pembelianBahanDetails from JSON
        $pembelianBahanDetails = json_decode($validatedData['pembelianBahanDetails'], true);
        $biaya = json_decode($validatedData['biaya'], true);

        $pembelianBahan = PembelianBahan::findOrFail($id);

        // Update pembelianBahan record with new keterangan
        $pembelianBahan->update([
            'keterangan' => $validatedData['keterangan'],
            'link' => $validatedData['link'],
            'ongkir' => $biaya['ongkir'] ?? 0,
            'asuransi' => $biaya['asuransi'] ?? 0,
            'layanan' => $biaya['layanan'] ?? 0,
            'jasa_aplikasi' => $biaya['jasa_aplikasi'] ?? 0,
            'ppn' => $biaya['ppn'] ?? 0,
            'shipping_cost' => $biaya['shipping_cost'] ?? 0,
            'full_amount_fee' => $biaya['full_amount_fee'] ?? 0,
            'value_today_fee' => $biaya['value_today_fee'] ?? 0,

            'shipping_cost_usd' => $biaya['shipping_cost_usd'] ?? 0,
            'full_amount_fee_usd' => $biaya['full_amount_fee_usd'] ?? 0,
            'value_today_fee_usd' => $biaya['value_today_fee_usd'] ?? 0,

            'new_shipping_cost' => $biaya['new_shipping_cost'] ?? 0,
            'new_full_amount_fee' => $biaya['new_full_amount_fee'] ?? 0,
            'new_value_today_fee' => $biaya['new_value_today_fee'] ?? 0,

            'new_shipping_cost_usd' => $biaya['new_shipping_cost_usd'] ?? 0,
            'new_full_amount_fee_usd' => $biaya['new_full_amount_fee_usd'] ?? 0,
            'new_value_today_fee_usd' => $biaya['new_value_today_fee_usd'] ?? 0,
        ]);


        if (!is_array($pembelianBahanDetails)) {
            return redirect()->back()->with('error', 'Data pembelian bahan tidak valid.');
        }

        // Process each pembelianBahanDetail
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
                    'new_details' => json_encode($item['new_details']),
                    'new_sub_total' => $item['new_sub_total'],
                    'new_details_usd' => json_encode($item['new_details_usd']),
                    'new_sub_total_usd' => $item['new_sub_total_usd'],
                    'keterangan_pembayaran' => $item['keterangan_pembayaran'] ?? '',
                ]
            );
        }

        // Fetch pembelianBahanDetails related to the given pembelianBahanId
        $details = PembelianBahanDetails::where('pembelian_bahan_id', $id)->get();
        foreach ($details as $detail) {
            // Decode new_details
            $transactionDetails = json_decode($detail->new_details, true) ?? [];
            $transactionDetailsUSD = json_decode($detail->new_details_usd, true) ?? [];

            // If new_details is an array of single value, convert it into an array
            if (!is_array($transactionDetails)) {
                $transactionDetails = [$transactionDetails];
            }

            if (!is_array($transactionDetailsUSD)) {
                $transactionDetailsUSD = [$transactionDetailsUSD];
            }

            // Check if there is a valid pengajuan_id and process accordingly
            if ($pembelianBahan->pengajuan_id) {
                // Look for existing PengajuanDetails based on pengajuan_id and bahan_id
                $existingDetail = PengajuanDetails::where('pengajuan_id', $pembelianBahan->pengajuan_id)
                    ->where(function ($query) use ($detail) {
                        if (!empty($detail->bahan_id)) {
                            $query->where('bahan_id', $detail->bahan_id);
                        } else {
                            $query->where('nama_bahan', $detail->nama_bahan);
                        }
                    })
                    ->first();

                if (!is_array($transactionDetails) || isset($transactionDetails['new_unit_price'])) {
                    // Jika data berbentuk array asosiatif sederhana (single entry), bungkus ke dalam array
                    $transactionDetails = [$transactionDetails];
                }

                if (!is_array($transactionDetailsUSD) || isset($transactionDetailsUSD['new_unit_price_usd'])) {
                    // Jika data berbentuk array asosiatif sederhana (single entry), bungkus ke dalam array
                    $transactionDetailsUSD = [$transactionDetailsUSD];
                }

                $groupedDetails = []; // Kumpulkan data transaksi berdasarkan new_unit_price
                $groupedDetailsUSD = [];
                foreach ($transactionDetails as $transaksiDetail) {
                    $unitPrice = $transaksiDetail['new_unit_price'] ?? 0;

                    if (isset($groupedDetails[$unitPrice])) {
                        $groupedDetails[$unitPrice]['jml_bahan'] += $transaksiDetail['jml_bahan'] ?? 0;
                    } else {
                        $groupedDetails[$unitPrice] = [
                            'new_unit_price' => $unitPrice,
                        ];
                    }
                }
                foreach ($transactionDetailsUSD as $transaksiDetailUSD) {
                    $unitPriceUSD = $transaksiDetailUSD['new_unit_price_usd'] ?? 0;

                    if (isset($groupedDetailsUSD[$unitPrice])) {
                        $groupedDetailsUSD[$unitPriceUSD]['jml_bahan'] += $transaksiDetailUSD['jml_bahan'] ?? 0;
                    } else {
                        $groupedDetailsUSD[$unitPriceUSD] = [
                            'new_unit_price_usd' => $unitPriceUSD,
                        ];
                    }
                }

                if ($existingDetail) {
                    // Update existing PengajuanDetails with new details
                    $existingDetail->update([
                        'qty' => array_sum(array_column($transactionDetails, 'qty')),
                        'jml_bahan' => $detail->jml_bahan,
                        'used_materials' => $detail->jml_bahan,
                        'new_details' => json_encode($transactionDetails), // Update new_details
                        'new_sub_total' => $detail->jml_bahan * array_sum(array_column($groupedDetails, 'new_unit_price')),
                        'new_details_usd' => json_encode($transactionDetailsUSD), // Update new_details
                        'new_sub_total_usd' => $detail->jml_bahan * array_sum(array_column($groupedDetailsUSD, 'new_unit_price_usd')),
                        'keterangan_pembayaran' => $detail->keterangan_pembayaran,
                        'spesifikasi' => $detail->spesifikasi,
                    ]);
                } else {
                    // Create new PengajuanDetails if not found
                    PengajuanDetails::create([
                        'pengajuan_id' => $pembelianBahan->pengajuan_id,
                        'bahan_id' => $detail->bahan_id ?? null,
                        'nama_bahan' => $detail->nama_bahan ?? null,
                        'qty' => array_sum(array_column($transactionDetails, 'qty')),
                        'jml_bahan' => $detail->jml_bahan,
                        'used_materials' => $detail->jml_bahan,
                        'new_details' => json_encode($transactionDetails),
                        'new_sub_total' => $detail->jml_bahan * array_sum(array_column($groupedDetails, 'new_unit_price')),
                        'new_details_usd' => json_encode($transactionDetailsUSD),
                        'new_sub_total_usd' => $detail->jml_bahan * array_sum(array_column($groupedDetailsUSD, 'new_unit_price_usd')),
                        'keterangan_pembayaran' => $detail->keterangan_pembayaran,
                        'spesifikasi' => $detail->spesifikasi,
                    ]);
                }

                // Update the related Pengajuan record with new keterangan if necessary
                $pengajuan = Pengajuan::find($pembelianBahan->pengajuan_id);
                if ($pengajuan) {
                    $pengajuan->update([
                        'keterangan' => $validatedData['keterangan'],
                        'ongkir' => $biaya['ongkir'] ?? 0,
                        'asuransi' => $biaya['asuransi'] ?? 0,
                        'layanan' => $biaya['layanan'] ?? 0,
                        'jasa_aplikasi' => $biaya['jasa_aplikasi'] ?? 0,
                        'ppn' => $biaya['ppn'] ?? 0,
                        'shipping_cost' => $biaya['shipping_cost'] ?? 0,
                        'full_amount_fee' => $biaya['full_amount_fee'] ?? 0,
                        'value_today_fee' => $biaya['value_today_fee'] ?? 0,

                        'new_shipping_cost' => $biaya['new_shipping_cost'] ?? 0,
                        'new_full_amount_fee' => $biaya['new_full_amount_fee'] ?? 0,
                        'new_value_today_fee' => $biaya['new_value_today_fee'] ?? 0,

                        'shipping_cost_usd' => $biaya['shipping_cost_usd'] ?? 0,
                        'full_amount_fee_usd' => $biaya['full_amount_fee_usd'] ?? 0,
                        'value_today_fee_usd' => $biaya['value_today_fee_usd'] ?? 0,

                        'new_shipping_cost_usd' => $biaya['new_shipping_cost_usd'] ?? 0,
                        'new_full_amount_fee_usd' => $biaya['new_full_amount_fee_usd'] ?? 0,
                        'new_value_today_fee_usd' => $biaya['new_value_today_fee_usd'] ?? 0,

                    ]);
                }
            }
        }
        // Redirect back with success message
        // return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Status berhasil diubah.');
        $page = $request->input('page', 1);
        return redirect()->route('pengajuan-pembelian-bahan.index', ['page' => $page])->with('success', 'Status berhasil diubah.');
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
            // dd($pembelianBahanDetails);
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
                // dd($pembelianBahanDetails, $item, $item['details_usd']);
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
                'pengisi_harga' => Auth::user()->name,
                'tgl_isi_harga' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ]);

            DB::commit();
            LogHelper::success('Pembelian Bahan berhasil diubah.');
            // return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Pembelian Bahan berhasil diubah.');
            $page = $request->input('page', 1);
            return redirect()->route('pengajuan-pembelian-bahan.index', ['page' => $page])->with('success', 'Pembelian Bahan berhasil diubah.');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan. Pesan error: $errorMessage");
        }
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

    //Approve Leader
    public function updateApprovalLeader(Request $request, int $id)
    {
        $validated = $request->validate([
            'status_leader' => 'required|string|in:Belum disetujui,Disetujui,Ditolak',
            'catatan' => 'nullable|string',
        ]);
        try {
            DB::beginTransaction();
            $data = PembelianBahan::with([
                'dataUser.atasanLevel1',
                'dataUser.atasanLevel2',
                'dataUser.atasanLevel3',
                'pembelianBahanDetails.dataBahan.dataUnit',
            ])->findOrFail($id);

            $leaderUser = null;
            if ($data->dataUser) {
                $leaderUser = $data->dataUser->atasanLevel3 ?? $data->dataUser->atasanLevel2 ?? null;
            }
            $leaderName = $leaderUser->name ?? 'Leader';

            $tgl_approve_leader = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            $data->status_leader = $validated['status_leader'];
            $data->tgl_approve_leader = $tgl_approve_leader;
            // $data->leader_approval_by = $leaderUser->id ?? null;

            $pengajuan = null;
            if ($data->pengajuan_id) {
                $pengajuan = Pengajuan::find($data->pengajuan_id);
            }
            // Jika status_leader Ditolak, maka semua status lainnya juga Ditolak
            if ($data->status_leader === 'Ditolak') {
                $data->catatan = $validated['catatan'];
                $data->status_purchasing = 'Ditolak';
                $data->status_manager = 'Ditolak';
                $data->status_finance = 'Ditolak';
                $data->status_admin_manager = 'Ditolak';
                $data->status_general_manager = 'Ditolak';
                $data->status = 'Ditolak';

                if ($pengajuan) {
                    $pengajuan->status_leader = $data->status_leader;
                    $pengajuan->catatan = $validated['catatan'];
                    $pengajuan->status_purchasing = 'Ditolak';
                    $pengajuan->status_manager = 'Ditolak';
                    $pengajuan->status_finance = 'Ditolak';
                    $pengajuan->status_admin_manager = 'Ditolak';
                    $pengajuan->status_general_manager = 'Ditolak';
                    $pengajuan->status = 'Ditolak';
                    $pengajuan->save();
                }
            } else {
                // Jika status disetujui atau masih menunggu, hanya update status_leader di pengajuan
                if ($pengajuan) {
                    $pengajuan->status_leader = $data->status_leader;
                    $pengajuan->catatan = $data->catatan;
                    $pengajuan->save();
                }
            }

            $data->save();

            if ($data->status_leader === 'Disetujui') {
                if ($data->jenis_pengajuan === 'Pembelian Aset' || 'Pembelian Aset Lokal' || 'Pembelian Aset Impor') {
                    // Kirim notifikasi ke General Affair
                    // $targetUser = User::whereHas('dataJobPosition', function ($query) {
                    //     $query->where('nama', 'General Affair'); // Posisi General Affair
                    // })->where('job_level', 3)->first();
                    // $targetRole = "General Affair";

                    $targetUser = User::whereHas('roles', function ($query) {
                        $query->where('name', 'general_affair');
                    })->first();

                    $targetRole = "General Affair";
                } else {
                    // Kirim notifikasi ke Purchasing
                    $targetUser = User::whereHas('dataJobPosition', function ($query) {
                        $query->where('nama', 'Purchasing');
                    })->where('job_level', 3)->first();
                    $targetRole = "Purchasing";
                }

                $targetPhone = $targetUser->telephone ?? null;
                $recipientName = $targetUser->name;

                // dd($targetPhone);
                //dd($targetPhone);
                if ($targetPhone) {
                    $message = "Halo {$recipientName},\n\n";
                    $message .= "Pengajuan pembelian bahan dengan kode transaksi {$data->kode_transaksi} memerlukan persetujuan Anda sebagai {$targetRole}.\n\n";
                    $message .= "Tgl Pengajuan: {$data->tgl_pengajuan}\nPengaju: {$data->dataUser->name}\nDivisi: {$data->divisi}\nProject: {$data->tujuan}\nKeterangan: {$data->keterangan}\n\n";
                    $message .= "\nPesan Otomatis:\n";
                    $message .= "https://inventory.beacontelemetry.com/";

                    SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
                } else {
                    LogHelper::error('No valid phone number found for WhatsApp notification.');
                }
            }
            // Mengirim notifikasi ke pengaju tentang tahap approval
            $targetPhone = $data->dataUser->telephone;
            $recipientName = $data->dataUser->name;
            if ($targetPhone) {
                $statusMessage = match ($data->status_leader) {
                    'Disetujui' => "telah *Disetujui* oleh Leader.",
                    'Ditolak' => "telah *Ditolak* oleh Leader.",
                    'Belum disetujui' => "masih *Menunggu Persetujuan* dari Leader.",
                    default => "dalam status yang tidak dikenal.",
                };
                $message = "Halo {$recipientName},\n\n";
                $message .= "Status pengajuan pembelian bahan Anda dengan Kode Transaksi {$data->kode_transaksi} {$statusMessage} {$data->catatan}\n\n";
                $message .= "\nPesan Otomatis:\n";
                $message .= "https://inventory.beacontelemetry.com/";

                SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
            } else {
                LogHelper::error('No valid phone number found for pengaju.');
            }
            DB::commit();
            LogHelper::success('Status approval leader berhasil diubah.');
            // return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Status approval leader berhasil diubah.');
            $page = $request->input('page', 1);
            return redirect()->route('pengajuan-pembelian-bahan.index', ['page' => $page])->with('success', 'Status approval leader berhasil diubah.');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan. Pesan error: $errorMessage");
        }
    }
    //Approve General Affair
    public function updateApprovalGM(Request $request, int $id)
    {
        $validated = $request->validate([
            'status_general_manager' => 'required|string|in:Belum disetujui,Disetujui,Ditolak',
            'catatan' => 'nullable|string',
        ]);
        try {
            DB::beginTransaction();
            $data = PembelianBahan::with([
                'dataUser.atasanLevel1',
                'dataUser.atasanLevel2',
                'dataUser.atasanLevel3',
                'pembelianBahanDetails.dataBahan.dataUnit',
            ])->findOrFail($id);
            // Periksa status Leader dan Manager
            $tgl_approve_general_manager = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            $data->status_general_manager = $validated['status_general_manager'];
            $data->tgl_approve_general_manager = $tgl_approve_general_manager;

            $pengajuan = null;
            if ($data->pengajuan_id) {
                $pengajuan = Pengajuan::find($data->pengajuan_id);
            }

            if ($data->status_general_manager === 'Ditolak') {
                $data->catatan = $validated['catatan'];
                $data->status_purchasing = 'Ditolak';
                $data->status_manager = 'Ditolak';
                $data->status_finance = 'Ditolak';
                $data->status_admin_manager = 'Ditolak';
                $data->status = 'Ditolak';

                if ($pengajuan) {
                    $pengajuan->status_general_manager = $data->status_general_manager;
                    $pengajuan->catatan = $validated['catatan'];
                    $pengajuan->status_purchasing = 'Ditolak';
                    $pengajuan->status_manager = 'Ditolak';
                    $pengajuan->status_finance = 'Ditolak';
                    $pengajuan->status_admin_manager = 'Ditolak';
                    $pengajuan->status = 'Ditolak';
                    $pengajuan->save();
                }
            } else {
                // Jika status disetujui atau masih menunggu, hanya update status_leader di pengajuan
                if ($pengajuan) {
                    $pengajuan->status_general_manager = $data->status_general_manager;
                    $pengajuan->catatan = $data->catatan;
                    $pengajuan->save();
                }
            }

            $data->save();

            if ($data->status_general_manager === 'Disetujui') {
                $purchasingUsers = User::whereHas('dataJobPosition', function ($query) {
                    $query->where('nama', 'Purchasing');
                })->where('job_level', 3)->first();

                $targetPhone = $purchasingUsers->telephone;
                $recipientName = $purchasingUsers->name;
                //dd($targetPhone);
                if ($targetPhone) {
                    $message = "Halo {$purchasingUsers->name},\n\n";
                    $message .= "Pengajuan pembelian bahan dengan kode transaksi {$data->kode_transaksi} memerlukan persetujuan Anda sebagai Purchasing.\n\n";
                    $message .= "Tgl Pengajuan: {$data->tgl_pengajuan}\nPengaju: {$data->dataUser->name}\nDivisi: {$data->divisi}\nProject: {$data->tujuan}\nKeterangan: {$data->keterangan}\n\n";
                    $message .= "\nPesan Otomatis:\n";
                    $message .= "https://inventory.beacontelemetry.com/";
                    SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
                } else {
                    LogHelper::error('No valid phone number found for WhatsApp notification.');
                }
            }
            // Mengirim notifikasi ke pengaju tentang tahap approval
            $targetPhone = $data->dataUser->telephone;
            $recipientName = $data->dataUser->name;
            if ($targetPhone) {
                $statusMessage = match ($data->status_general_manager) {
                    'Disetujui' => "telah *Disetujui* oleh General Affair.",
                    'Ditolak' => "telah *Ditolak* oleh General Affair.",
                    'Belum disetujui' => "masih *Menunggu Persetujuan* dari General Affair.",
                    default => "dalam status yang tidak dikenal.",
                };
                $message = "Halo {$data->dataUser->name},\n\n";
                $message .= "Status pengajuan pembelian bahan Anda dengan Kode Transaksi {$data->kode_transaksi} {$statusMessage} {$data->catatan}\n\n";
                $message .= "\nPesan Otomatis:\n";
                $message .= "https://inventory.beacontelemetry.com/";

                SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
            } else {
                LogHelper::error('No valid phone number found for pengaju.');
            }
            DB::commit();
            LogHelper::success('Status approval general Affair berhasil diubah.');
            // return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Status approval general Affair berhasil diubah.');
            $page = $request->input('page', 1);
            return redirect()->route('pengajuan-pembelian-bahan.index', ['page' => $page])->with('success', 'Status approval general Affair berhasil diubah.');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan. Pesan error: $errorMessage");
        }
    }
    //Approve Purchasing
    public function updateApprovalPurchasing(Request $request, int $id)
    {
        $validated = $request->validate([
            'status_purchasing' => 'required|string|in:Belum disetujui,Disetujui,Ditolak',
            'catatan' => 'nullable|string',
        ]);
        try {
            DB::beginTransaction();
            $data = PembelianBahan::with([
                'dataUser.atasanLevel1',
                'dataUser.atasanLevel2',
                'dataUser.atasanLevel3',
                'pembelianBahanDetails.dataBahan.dataUnit',
            ])->findOrFail($id);
            // Periksa status Leader dan Manager
            $tgl_approve_purchasing = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            $data->status_purchasing = $validated['status_purchasing'];
            $data->tgl_approve_purchasing = $tgl_approve_purchasing;

            $pengajuan = null;
            if ($data->pengajuan_id) {
                $pengajuan = Pengajuan::find($data->pengajuan_id);
            }

            if ($data->status_purchasing === 'Ditolak') {
                $data->catatan = $validated['catatan'];
                $data->status_manager = 'Ditolak';
                $data->status_finance = 'Ditolak';
                $data->status_admin_manager = 'Ditolak';
                $data->status = 'Ditolak';

                if ($pengajuan) {
                    $pengajuan->status_purchasing = $data->status_purchasing;
                    $pengajuan->catatan = $validated['catatan'];
                    $pengajuan->status_manager = 'Ditolak';
                    $pengajuan->status_finance = 'Ditolak';
                    $pengajuan->status_admin_manager = 'Ditolak';
                    $pengajuan->status = 'Ditolak';
                    $pengajuan->save();
                }
            } else {
                // Jika status disetujui atau masih menunggu, hanya update status_leader di pengajuan
                if ($pengajuan) {
                    $pengajuan->status_purchasing = $data->status_purchasing;
                    $pengajuan->catatan = $data->catatan;
                    $pengajuan->save();
                }
            }

            $data->save();

            if ($data->status_purchasing === 'Disetujui') {
                if ($data->dataUser->job_level == 4) {
                    if ($data->dataUser->atasan_level3_id === null && $data->dataUser->atasan_level2_id === null) {
                        // Job level 4 tanpa atasan level 3 dan 2, kirim notifikasi ke Finance
                        $financeUser = User::where('name', 'LINA WIDIASTUTI')->first();
                        $recipientName = $financeUser;
                        if ($financeUser && $financeUser->telephone) {
                            $targetPhone = $financeUser->telephone;
                            $message = "Halo {$financeUser->name},\n\n";
                            $message .= "Pengajuan pembelian bahan dengan kode transaksi {$data->kode_transaksi} memerlukan persetujuan Anda sebagai Finance.\n\n";
                            $message .= "Tgl Pengajuan: {$data->tgl_pengajuan}\nPengaju: {$data->dataUser->name}\nDivisi: {$data->divisi}\nProject: {$data->tujuan}\nKeterangan: {$data->keterangan}\n\n";
                            $message .= "\nPesan Otomatis:\n";
                            $message .= "https://inventory.beacontelemetry.com/";

                            SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
                        } else {
                            LogHelper::error('No valid phone number found for Finance notification.');
                        }
                    } elseif ($data->dataUser->atasan_level3_id && $data->dataUser->atasan_level2_id === null) {
                        // Job level 4 tanpa atasan level 3 dan 2, kirim notifikasi ke Finance
                        $financeUser = User::where('name', 'LINA WIDIASTUTI')->first();
                        $recipientName = $financeUser;
                        if ($financeUser && $financeUser->telephone) {
                            $targetPhone = $financeUser->telephone;
                            $message = "Halo {$financeUser->name},\n\n";
                            $message .= "Pengajuan pembelian bahan dengan kode transaksi {$data->kode_transaksi} memerlukan persetujuan Anda sebagai Finance.\n\n";
                            $message .= "Tgl Pengajuan: {$data->tgl_pengajuan}\nPengaju: {$data->dataUser->name}\nDivisi: {$data->divisi}\nProject: {$data->tujuan}\nKeterangan: {$data->keterangan}\n\n";
                            $message .= "\nPesan Otomatis:\n";
                            $message .= "https://inventory.beacontelemetry.com/";

                            SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
                        } else {
                            LogHelper::error('No valid phone number found for Finance notification.');
                        }
                    } else {
                        // Kirim notifikasi ke atasan level 2/manager
                        $managerUser = $data->dataUser->atasanLevel2;
                        $recipientName = $managerUser->name;
                        if ($managerUser && $managerUser->telephone) {
                            $targetPhone = $managerUser->telephone;
                            $message = "Halo {$managerUser->name},\n\n";
                            $message .= "Pengajuan pembelian bahan dengan kode transaksi {$data->kode_transaksi} memerlukan persetujuan Anda sebagai Manager.\n\n";
                            $message .= "Tgl Pengajuan: {$data->tgl_pengajuan}\nPengaju: {$data->dataUser->name}\nDivisi: {$data->divisi}\nProject: {$data->tujuan}\nKeterangan: {$data->keterangan}\n\n";
                            $message .= "\nPesan Otomatis:\n";
                            $message .= "https://inventory.beacontelemetry.com/";

                            SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
                        } else {
                            LogHelper::error('No valid phone number found for Manager notification.');
                        }
                    }
                } else {
                    // Kirim notifikasi ke atasan level 2/manager
                    $managerUser = $data->dataUser->atasanLevel2;
                    $recipientName = $managerUser->name;
                    if ($managerUser && $managerUser->telephone) {
                        $targetPhone = $managerUser->telephone;
                        $message = "Halo {$managerUser->name},\n\n";
                        $message .= "Pengajuan pembelian bahan dengan kode transaksi {$data->kode_transaksi} memerlukan persetujuan Anda sebagai Manager.\n\n";
                        $message .= "Tgl Pengajuan: {$data->tgl_pengajuan}\nPengaju: {$data->dataUser->name}\nDivisi: {$data->divisi}\nProject: {$data->tujuan}\nKeterangan: {$data->keterangan}\n\n";
                        $message .= "\nPesan Otomatis:\n";
                        $message .= "https://inventory.beacontelemetry.com/";

                        SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
                    } else {
                        LogHelper::error('No valid phone number found for Manager notification.');
                    }
                }
            }

            // Kirim notifikasi ke Pengaju
            $targetPhone = $data->dataUser->telephone;
            $recipientName = $data->dataUser->name;
            if ($targetPhone) {
                $statusMessage = match ($data->status_purchasing) {
                    'Disetujui' => "telah *Disetujui* oleh Purchasing.",
                    'Ditolak' => "telah *Ditolak* oleh Purchasing.",
                    'Belum disetujui' => "masih *Menunggu Persetujuan* dari Purchasing.",
                    default => "dalam status yang tidak dikenal.",
                };
                $message = "Halo {$data->dataUser->name},\n\n";
                $message .= "Status pengajuan pembelian bahan Anda dengan Kode Transaksi {$data->kode_transaksi} {$statusMessage} {$data->catatan}\n\n";
                $message .= "\nPesan Otomatis:\n";
                $message .= "https://inventory.beacontelemetry.com/";

                SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
            } else {
                LogHelper::error('No valid phone number found for pengaju.');
            }
            DB::commit();
            LogHelper::success('Status approval purchasing berhasil diubah.');
            // return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Status approval purchasing berhasil diubah.');
            $page = $request->input('page', 1);
            return redirect()->route('pengajuan-pembelian-bahan.index', ['page' => $page])->with('success', 'Status approval purchasing berhasil diubah.');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan. Pesan error: $errorMessage");
        }
    }
    //Approve Manager
    public function updateApprovalManager(Request $request, int $id)
    {
        $validated = $request->validate([
            'status_manager' => 'required|string|in:Belum disetujui,Disetujui,Ditolak',
            'catatan' => 'nullable|string',
        ]);
        try {
            DB::beginTransaction();
            $data = PembelianBahan::with([
                'dataUser.atasanLevel1',
                'dataUser.atasanLevel2',
                'dataUser.atasanLevel3',
                'pembelianBahanDetails.dataBahan.dataUnit',
            ])->findOrFail($id);
            $tgl_approve_manager = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            $data->status_manager = $validated['status_manager'];
            $data->tgl_approve_manager = $tgl_approve_manager;

            $pengajuan = null;
            if ($data->pengajuan_id) {
                $pengajuan = Pengajuan::find($data->pengajuan_id);
            }

            if ($data->status_manager === 'Ditolak') {
                $data->catatan = $validated['catatan'];
                $data->status_finance = 'Ditolak';
                $data->status_admin_manager = 'Ditolak';
                $data->status = 'Ditolak';

                if ($pengajuan) {
                    $pengajuan->status_manager = $data->status_manager;
                    $pengajuan->catatan = $validated['catatan'];
                    $pengajuan->status_finance = 'Ditolak';
                    $pengajuan->status_admin_manager = 'Ditolak';
                    $pengajuan->status = 'Ditolak';
                    $pengajuan->save();
                }
            } else {
                // Jika status disetujui atau masih menunggu, hanya update status_leader di pengajuan
                if ($pengajuan) {
                    $pengajuan->status_manager = $data->status_manager;
                    $pengajuan->catatan = $data->catatan;
                    $pengajuan->save();
                }
            }

            $data->save();

            if ($data->status_manager === 'Disetujui') {
                $financeUser = User::where('name', 'LINA WIDIASTUTI')->first();
                $recipientName = $financeUser->name;
                if ($financeUser && $financeUser->telephone) {
                    $targetPhone = $financeUser->telephone;
                    $message = "Halo {$financeUser->name},\n\n";
                    $message .= "Pengajuan pembelian bahan dengan kode transaksi {$data->kode_transaksi} memerlukan persetujuan Anda sebagai Finance.\n\n";
                    $message .= "Tgl Pengajuan: {$data->tgl_pengajuan}\nPengaju: {$data->dataUser->name}\nDivisi: {$data->divisi}\nProject: {$data->tujuan}\nKeterangan: {$data->keterangan}\n\n";
                    $message .= "\nPesan Otomatis:\n";
                    $message .= "https://inventory.beacontelemetry.com/";

                    SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
                } else {
                    LogHelper::error('No valid phone number found for Finance notification.');
                }
            }

            $targetPhone = $data->dataUser->telephone;
            $recipientName = $data->dataUser->name;
            if ($targetPhone) {
                $statusMessage = match ($data->status_manager) {
                    'Disetujui' => "telah *Disetujui* oleh Manager.",
                    'Ditolak' => "telah *Ditolak* oleh Manager.",
                    'Belum disetujui' => "masih *Menunggu Persetujuan* dari Manager.",
                    default => "dalam status yang tidak dikenal.",
                };
                $message = "Halo {$data->dataUser->name},\n\n";
                $message .= "Status pengajuan pembelian bahan Anda dengan Kode Transaksi {$data->kode_transaksi} {$statusMessage} {$data->catatan}\n\n";
                $message .= "\nPesan Otomatis:\n";
                $message .= "https://inventory.beacontelemetry.com/";

                SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
            } else {
                LogHelper::error('No valid phone number found for pengaju.');
            }
            DB::commit();
            LogHelper::success('Status approval manager berhasil diubah.');
            // return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Status approval manager berhasil diubah.');
            $page = $request->input('page', 1);
            return redirect()->route('pengajuan-pembelian-bahan.index', ['page' => $page])->with('success', 'Status approval manager berhasil diubah.');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan. Pesan error: $errorMessage");
        }
    }
    // //Approve Finance
    public function updateApprovalFinance(Request $request, int $id)
    {
        $validated = $request->validate([
            'status_finance' => 'required|string|in:Belum disetujui,Disetujui,Ditolak',
            'catatan' => 'nullable|string',
        ]);
        try {
            DB::beginTransaction();
            $data = PembelianBahan::with([
                'dataUser.atasanLevel1',
                'dataUser.atasanLevel2',
                'dataUser.atasanLevel3',
                'pembelianBahanDetails.dataBahan.dataUnit',
            ])->findOrFail($id);
            $tgl_approve_finance = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            $data->status_finance = $validated['status_finance'];
            $data->tgl_approve_finance = $tgl_approve_finance;

            $pengajuan = null;
            if ($data->pengajuan_id) {
                $pengajuan = Pengajuan::find($data->pengajuan_id);
            }

            if ($data->status_finance === 'Ditolak') {
                $data->catatan = $validated['catatan'];
                $data->status_admin_manager = 'Ditolak';
                $data->status = 'Ditolak';

                if ($pengajuan) {
                    $pengajuan->status_finance = $data->status_finance;
                    $pengajuan->catatan = $validated['catatan'];
                    $pengajuan->status_admin_manager = 'Ditolak';
                    $pengajuan->status = 'Ditolak';
                    $pengajuan->save();
                }
            } else {
                // Jika status disetujui atau masih menunggu, hanya update status_leader di pengajuan
                if ($pengajuan) {
                    $pengajuan->status_finance = $data->status_finance;
                    $pengajuan->catatan = $data->catatan;
                    $pengajuan->save();
                }
            }

            $data->save();

            // Kirim notifikasi ke Pengaju
            $targetPhone = $data->dataUser->telephone;
            $recipientName = $data->dataUser->name;
            if ($targetPhone) {
                $statusMessage = match ($data->status_finance) {
                    'Disetujui' => "telah *Disetujui* oleh Finance.",
                    'Ditolak' => "telah *Ditolak* oleh Finance.",
                    'Belum disetujui' => "masih *Menunggu Persetujuan* dari Finance.",
                    default => "dalam status yang tidak dikenal.",
                };
                $message = "Halo {$data->dataUser->name},\n\n";
                $message .= "Status pengajuan pembelian bahan Anda dengan Kode Transaksi {$data->kode_transaksi} {$statusMessage} {$data->catatan}\n\n";

                $message .= "\nPesan Otomatis:\n";
                $message .= "https://inventory.beacontelemetry.com/";

                SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
            } else {
                LogHelper::error('No valid phone number found for pengaju.');
            }
            DB::commit();
            LogHelper::success('Status approval finance berhasil diubah.');
            // return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Status approval finance berhasil diubah.');
            $page = $request->input('page', 1);
            return redirect()->route('pengajuan-pembelian-bahan.index', ['page' => $page])->with('success', 'Status approval finance berhasil diubah.');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan. Pesan error: $errorMessage");
        }
    }
    // //Approve Admin Manager
    public function updateApprovalAdminManager(Request $request, int $id)
    {
        $validated = $request->validate([
            'status_admin_manager' => 'required|string|in:Belum disetujui,Disetujui,Ditolak',
            'catatan' => 'nullable|string',
        ]);
        try {
            DB::beginTransaction();
            $data = PembelianBahan::with([
                'dataUser.atasanLevel1',
                'dataUser.atasanLevel2',
                'dataUser.atasanLevel3',
                'pembelianBahanDetails.dataBahan.dataUnit',
            ])->findOrFail($id);
            $tgl_approve_admin_manager = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            $data->status_admin_manager = $validated['status_admin_manager'];
            $data->tgl_approve_admin_manager = $tgl_approve_admin_manager;

            $pengajuan = null;
            if ($data->pengajuan_id) {
                $pengajuan = Pengajuan::find($data->pengajuan_id);
            }

            if ($data->status_admin_manager === 'Ditolak') {
                $data->catatan = $validated['catatan'];
                $data->status = 'Ditolak';

                if ($pengajuan) {
                    $pengajuan->status_admin_manager = $data->status_admin_manager;
                    $pengajuan->catatan = $validated['catatan'];
                    $pengajuan->status = 'Ditolak';
                    $pengajuan->save();
                }
            } else {
                // Jika status disetujui atau masih menunggu, hanya update status_leader di pengajuan
                if ($pengajuan) {
                    $pengajuan->status_admin_manager = $data->status_admin_manager;
                    $pengajuan->catatan = $data->catatan;
                    $pengajuan->save();
                }
            }

            $data->save();

            // Kirim notifikasi ke Pengaju
            $targetPhone = $data->dataUser->telephone;
            $recipientName = $data->dataUser->name;
            if ($targetPhone) {
                $statusMessage = match ($data->status_admin_manager) {
                    'Disetujui' => "telah *Disetujui* oleh Manager Admin.",
                    'Ditolak' => "telah *Ditolak* oleh Manager Admin.",
                    'Belum disetujui' => "masih *Menunggu Persetujuan* dari Manager Admin.",
                    default => "dalam status yang tidak dikenal.",
                };
                $message = "Halo {$data->dataUser->name},\n\n";
                $message .= "Status pengajuan pembelian bahan Anda dengan Kode Transaksi {$data->kode_transaksi} {$statusMessage} {$data->catatan}\n\n";
                $message .= "\nPesan Otomatis:\n";
                $message .= "https://inventory.beacontelemetry.com/";

                SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
            } else {
                LogHelper::error('No valid phone number found for pengaju.');
            }
            DB::commit();
            LogHelper::success('Status approval admin manager berhasil diubah.');
            // return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Status approval admin manager berhasil diubah.');
            $page = $request->input('page', 1);
            return redirect()->route('pengajuan-pembelian-bahan.index', ['page' => $page])->with('success', 'Status approval admin manager berhasil diubah.');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan. Pesan error: $errorMessage");
        }
    }
    public function uploadInvoice(Request $request, int $id)
    {
        $validated = $request->validate([
            'link' => 'nullable|string',
        ]);
        try {
            DB::beginTransaction();
            $data = PembelianBahan::findOrFail($id);

            $data->link = $validated['link'];
            $data->save();

            DB::commit();
            LogHelper::success('Upload link invoice berhasil.');
            // return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Upload link invoice berhasil.');
            $page = $request->input('page', 1);
            return redirect()->route('pengajuan-pembelian-bahan.index', ['page' => $page])->with('success', 'Upload link invoice berhasil.');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan. Pesan error: $errorMessage");
        }
    }

    public function uploadDokumen(Request $request, int $id)
    {
        $validated = $request->validate([
            'dokumen' => 'required|file|mimes:pdf|max:2048', // hanya PDF max 2MB
        ]);

        try {
            DB::beginTransaction();

            $data = PembelianBahan::findOrFail($id);

            if ($request->hasFile('dokumen')) {
                // Hapus dokumen lama kalau ada
                if ($data->dokumen && Storage::disk('public')->exists($data->dokumen)) {
                    Storage::disk('public')->delete($data->dokumen);
                }

                // Simpan dokumen baru
                $file = $request->file('dokumen');
                $fileName = 'dokumen_' . time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('dokumen-pembatalan', $fileName, 'public');

                // Simpan path ke DB
                $data->dokumen = $filePath;
            }

            $data->save();

            DB::commit();
            LogHelper::success('Upload dokumen berhasil.');

            $page = $request->input('page', 1);
            return redirect()
                ->route('pengajuan-pembelian-bahan.index', ['page' => $page])
                ->with('success', 'Upload dokumen berhasil.');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan. Pesan error: $errorMessage");
        }
    }


    //Approve Direktur
    public function updateApprovalDirektur(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Belum disetujui,Disetujui,Ditolak',
            'catatan' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            $data = PembelianBahan::with([
                'dataUser.atasanLevel1',
                'dataUser.atasanLevel2',
                'dataUser.atasanLevel3',
                'pembelianBahanDetails.dataBahan.dataUnit',
            ])->findOrFail($id);
            $tgl_approve_direktur = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            $data->status = $validated['status'];
            $data->tgl_approve_direktur = $tgl_approve_direktur;

            $pengajuan = null;
            if ($data->pengajuan_id) {
                $pengajuan = Pengajuan::find($data->pengajuan_id);
            }

            if ($data->status === 'Ditolak') {
                $data->catatan = $validated['catatan'];

                if ($pengajuan) {
                    $pengajuan->status = 'Ditolak';
                    $pengajuan->catatan = $validated['catatan'];
                    $pengajuan->save();
                }
            }

            if ($data->status === 'Disetujui') {
                $data->tgl_keluar = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

                $details = PembelianBahanDetails::where('pembelian_bahan_id', $id)->get();

                foreach ($details as $detail) {
                    $transactionDetails = json_decode($detail->details, true) ?? [];
                    $transactionDetailsUSD = json_decode($detail->details_usd, true) ?? [];
                    if (!is_array($transactionDetails) || isset($transactionDetails['unit_price'])) {
                        $transactionDetails = [$transactionDetails];
                    }

                    if (!is_array($transactionDetailsUSD) || isset($transactionDetailsUSD['unit_price_usd'])) {
                        $transactionDetailsUSD = [$transactionDetailsUSD];
                    }

                    if ($data->pengajuan_id) {
                        // Proses data pada tabel PengajuanDetails
                        $existingDetail = PengajuanDetails::where('pengajuan_id', $data->pengajuan_id)
                            ->where(function ($query) use ($detail) {
                                if (!empty($detail->bahan_id)) {
                                    $query->where('bahan_id', $detail->bahan_id);
                                } else {
                                    $query->where('nama_bahan', $detail->nama_bahan);
                                }
                            })
                            ->first();


                        $groupedDetails = [];
                        $groupedDetailsUSD = [];

                        foreach ($transactionDetails as $transaksiDetail) {
                            $unitPrice = $transaksiDetail['unit_price'] ?? 0;

                            if (isset($groupedDetails[$unitPrice])) {
                                $groupedDetails[$unitPrice]['jml_bahan'] += $transaksiDetail['jml_bahan'] ?? 0;
                            } else {
                                $groupedDetails[$unitPrice] = [
                                    'unit_price' => $unitPrice,
                                ];
                            }
                        }

                        foreach ($transactionDetailsUSD as $transaksiDetailUSD) {
                            $unitPriceUSD = $transaksiDetailUSD['unit_price_usd'] ?? 0;

                            if (isset($groupedDetailsUSD[$unitPriceUSD])) {
                                $groupedDetailsUSD[$unitPriceUSD]['jml_bahan'] += $transaksiDetailUSD['jml_bahan'] ?? 0;
                            } else {
                                $groupedDetailsUSD[$unitPriceUSD] = [
                                    'unit_price_usd' => $unitPriceUSD,
                                ];
                            }
                        }

                        if ($existingDetail) {
                            // Ensure the sub total is calculated correctly
                            $subTotal = $detail->jml_bahan * array_sum(array_column($groupedDetails, 'unit_price'));
                            $subTotalUSD = $detail->jml_bahan * array_sum(array_column($groupedDetailsUSD, 'unit_price_usd'));

                            // Update existing data
                            $existingDetail->sub_total += $subTotal;
                            $existingDetail->sub_total_usd += $subTotalUSD;
                            $existingDetail->jml_bahan = $detail->jml_bahan;
                            $existingDetail->keterangan_pembayaran = $detail->keterangan_pembayaran;
                            // Gabungkan data details
                            $currentDetails = json_decode($existingDetail->details, true) ?? [];
                            $mergedDetails = array_merge($currentDetails, $groupedDetails);

                            $currentDetailsUSD = json_decode($existingDetail->details_usd, true) ?? [];
                            $mergedDetailsUSD = array_merge($currentDetailsUSD, $groupedDetailsUSD);

                            $existingDetail->details = json_encode(array_values($mergedDetails));
                            $existingDetail->details_usd = json_encode(array_values($mergedDetailsUSD));
                            $existingDetail->save();
                        } else {
                            $subTotal = $detail->jml_bahan * array_sum(array_column($groupedDetails, 'unit_price'));
                            $subTotalUSD = $detail->jml_bahan * array_sum(array_column($groupedDetailsUSD, 'unit_price_usd'));

                            PengajuanDetails::create([
                                'pengajuan_id' => $data->pengajuan_id,
                                'bahan_id' => $detail->bahan_id ?? null,
                                'nama_bahan' => $detail->nama_bahan ?? null,
                                'qty' => array_sum(array_column($groupedDetails, 'qty')),
                                'jml_bahan' => $detail->jml_bahan,
                                'used_materials' => $detail->jml_bahan,
                                'details' => json_encode(array_values($groupedDetails)),
                                'details_usd' => json_encode(array_values($groupedDetailsUSD)),
                                'sub_total' => $subTotal,
                                'sub_total_usd' => $subTotalUSD,
                                'keterangan_pembayaran' => $detail->keterangan_pembayaran,
                                'spesifikasi' => $detail->spesifikasi,
                                'penanggungjawabaset' => $detail->penanggungjawabaset,
                                'alasan' => $detail->alasan,
                            ]);
                        }

                        $pengajuan = Pengajuan::find($data->pengajuan_id);
                        if ($pengajuan) {
                            $pengajuan->ongkir = $data->ongkir;
                            $pengajuan->layanan = $data->layanan;
                            $pengajuan->jasa_aplikasi = $data->jasa_aplikasi;
                            $pengajuan->asuransi = $data->asuransi;
                            $pengajuan->shipping_cost = $data->shipping_cost;
                            $pengajuan->full_amount_fee = $data->full_amount_fee;
                            $pengajuan->value_today_fee = $data->value_today_fee;
                            $pengajuan->shipping_cost_usd = $data->shipping_cost_usd;
                            $pengajuan->full_amount_fee_usd = $data->full_amount_fee_usd;
                            $pengajuan->value_today_fee_usd = $data->value_today_fee_usd;
                            $pengajuan->selesai_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                            $pengajuan->status = $data->status;
                            $pengajuan->catatan = $data->catatan;
                            $pengajuan->save();
                        }
                    }
                }
            }
            $data->save();
            // Kirim notifikasi ke Pengaju
            $targetPhone = $data->dataUser->telephone;
            $recipientName = $data->dataUser->name;
            if ($targetPhone) {
                $statusMessage = match ($data->status) {
                    'Disetujui' => "telah *Disetujui* oleh Direktur.",
                    'Ditolak' => "telah *Ditolak* oleh Direktur.",
                    'Belum disetujui' => "masih *Menunggu Persetujuan* dari Direktur.",
                    default => "dalam status yang tidak dikenal.",
                };
                $message = "Halo {$data->dataUser->name},\n\n";
                $message .= "Status pengajuan pembelian bahan Anda dengan Kode Transaksi {$data->kode_transaksi} {$statusMessage} {$data->catatan}\n\n";
                $message .= "\nPesan Otomatis:\n";
                $message .= "https://inventory.beacontelemetry.com/";

                SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
            } else {
                LogHelper::error('No valid phone number found for pengaju.');
            }
            DB::commit();
            LogHelper::success('Status approval direktur berhasil diubah.');
            // return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Status approval direktur berhasil diubah.');
            $page = $request->input('page', 1);
            return redirect()->route('pengajuan-pembelian-bahan.index', ['page' => $page])->with('success', 'Status approval direktur berhasil diubah.');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan. Pesan error: $errorMessage");
        }
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
