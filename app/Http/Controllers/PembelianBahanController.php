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
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PembelianBahanExport;
use App\Models\PembelianBahanDetails;
use App\Models\PengambilanBahanDetails;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Support\Facades\Validator;

class PembelianBahanController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-pembelian-bahan', ['only' => ['index']]);
        $this->middleware('permission:detail-pembelian-bahan', ['only' => ['show']]);
        $this->middleware('permission:tambah-pembelian-bahan', ['only' => ['create','store']]);
        $this->middleware('permission:edit-pembelian-bahan', ['only' => ['updateApprovalFinance','updateApprovalAdminManager']]);
        $this->middleware('permission:edit-pengambilan', ['only' => ['updatepengambilan']]);
        $this->middleware('permission:edit-approvepembelian-leader', ['only' => ['updateApprovalLeader']]);
        $this->middleware('permission:edit-approvepembelian-gm', ['only' => ['updateApprovalGM']]);
        $this->middleware('permission:edit-approve-purchasing', ['only' => ['update','edit','updateApprovalPurchasing']]);
        $this->middleware('permission:edit-approve-manager', ['only' => ['updateApprovalManager']]);
        $this->middleware('permission:update-harga-pembelian-bahan', ['only' => ['editHarga','updateHarga']]);

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
            $status = $pembelianBahan->status ?? null;
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
                return User::where('job_level', 4)
                    ->whereHas('dataJobPosition', function ($query) {
                        $query->where('nama', 'Secretary');
                    })->first();
            });

            $tandaTanganPurchasing = $purchasingUser->tanda_tangan ?? null;

            $tandaTanganGeneral= $generalUser->tanda_tangan ?? null;

            $financeUser = cache()->remember('finance_user', 60, function () {
                return User::where('name', 'REVIDYA CHRISDWIMAYA PUTRI')->first();
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
                'tandaTanganPengaju',
                'tandaTanganLeader',
                'tandaTanganManager',
                'tandaTanganDirektur',
                'tandaTanganPurchasing','tandaTanganGeneral',
                'purchasingUser','generalUser',
                'tandaTanganFinance','new_shipping_cost','new_full_amount_fee','new_value_today_fee',
                'financeUser','new_shipping_cost_usd','new_full_amount_fee_usd','new_value_today_fee_usd',
                'tandaTanganAdminManager','shipping_cost_usd','full_amount_fee_usd','value_today_fee_usd',
                'adminManagerceUser','shipping_cost','full_amount_fee','value_today_fee',
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
            'keterangan' => 'string',
            'link' => 'string',
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
            // Update or create PembelianBahanDetails
            PembelianBahanDetails::updateOrCreate(
                ['pembelian_bahan_id' => $id, 'bahan_id' => $item['id']],
                [
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
                    ->where('bahan_id', $detail->bahan_id)
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
                        'bahan_id' => $detail->bahan_id,
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
        return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Status berhasil diubah.');
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
                PembelianBahanDetails::updateOrCreate(
                    ['pembelian_bahan_id' => $id, 'bahan_id' => $item['id']],
                    [
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
            return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Pembelian Bahan berhasil diubah.');
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
        ]);
        try {
            DB::beginTransaction();
            $data = PembelianBahan::with([
                'dataUser.atasanLevel1',
                'dataUser.atasanLevel2',
                'dataUser.atasanLevel3',
                'pembelianBahanDetails.dataBahan.dataUnit',
            ])->findOrFail($id);

            $data->status_leader = $validated['status_leader'];
            $data->save();

            if ($data->status_leader === 'Disetujui') {

                if ($data->jenis_pengajuan === 'Pembelian Aset') {
                    // Kirim notifikasi ke General Affair
                    $targetUser = User::whereHas('dataJobPosition', function ($query) {
                        $query->where('nama', 'Secretary'); // Posisi General Affair
                    })->where('job_level', 4)->first();
                    $targetRole = "General Affair";
                } else {
                    // Kirim notifikasi ke Purchasing
                    $targetUser = User::whereHas('dataJobPosition', function ($query) {
                        $query->where('nama', 'Purchasing');
                    })->where('job_level', 3)->first();
                    $targetRole = "Purchasing";
                }

                $targetPhone = $targetUser->telephone ?? null;
                //dd($targetPhone);
                if ($targetPhone) {
                    $message = "Halo {$targetUser->name},\n\n";
                    $message .= "Pengajuan pembelian bahan dengan kode transaksi {$data->kode_transaksi} memerlukan persetujuan Anda sebagai {$targetRole}.\n\n";
                    $message .= "Tgl Pengajuan: {$data->tgl_pengajuan}\nPengaju: {$data->dataUser->name}\nDivisi: {$data->divisi}\nProject: {$data->tujuan}\nKeterangan: {$data->keterangan}\n\n";
                    $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
                    try{
                        $response = Http::withHeaders([
                            'x-api-key' => env('WHATSAPP_API_KEY'),
                            'Content-Type' => 'application/json',
                        ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
                            'chatId' => "{$targetPhone}@c.us",
                            'contentType' => 'string',
                            'content' => $message,
                        ]);
                        if ($response->successful()) {
                            LogHelper::success("WhatsApp message sent to: {$targetPhone}");
                        } else {
                            LogHelper::error("Failed to send WhatsApp message to: {$targetPhone}");
                        }
                    } catch (\Exception $e) {
                        LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
                    }
                } else {
                    LogHelper::error('No valid phone number found for WhatsApp notification.');
                }
                // Mengirim notifikasi ke pengaju tentang tahap approval
                $pengajuPhone = $data->dataUser->telephone;
                if ($pengajuPhone) {
                    $statusMessage = match ($data->status_leader) {
                        'Disetujui' => "telah *Disetujui* oleh Leader.",
                        'Ditolak' => "telah *Ditolak* oleh Leader.",
                        'Belum disetujui' => "masih *Menunggu Persetujuan* dari Leader.",
                        default => "dalam status yang tidak dikenal.",
                    };
                    $message = "Halo {$data->dataUser->name},\n\n";
                    $message .= "Status pengajuan pembelian bahan Anda dengan Kode Transaksi {$data->kode_transaksi} {$statusMessage}\n\n";
                    $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
                    try{
                        $responsePengaju = Http::withHeaders([
                            'x-api-key' => env('WHATSAPP_API_KEY'),
                            'Content-Type' => 'application/json',
                        ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
                            'chatId' => "{$pengajuPhone}@c.us",
                            'contentType' => 'string',
                            'content' => $message,
                        ]);
                        if ($responsePengaju->successful()) {
                            LogHelper::success("WhatsApp message sent to pengaju: {$pengajuPhone}");
                        } else {
                            LogHelper::error("Failed to send WhatsApp message to pengaju: {$pengajuPhone}");
                        }
                    } catch (\Exception $e) {
                        LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
                    }
                }  else {
                    LogHelper::error('No valid phone number found for pengaju.');
                }
            }
            DB::commit();
            LogHelper::success('Status approval leader berhasil diubah.');
            return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Status approval leader berhasil diubah.');
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
            if ($data->status_leader === 'Disetujui') {
                $data->status_general_manager = $validated['status_general_manager'];
            } else {
                LogHelper::error('Status general affair tidak dapat diubah karena leader belum menyetujui.');
                return redirect()->back()->with('error', 'Status general affair tidak dapat diubah karena leader belum menyetujui.');
            }
            $data->save();

            if ($data->status_general_manager === 'Disetujui') {
                $purchasingUsers = User::whereHas('dataJobPosition', function ($query) {
                    $query->where('nama', 'Purchasing');
                })->where('job_level', 3)->first();

                $targetPhone = $purchasingUsers->telephone;
                //dd($targetPhone);
                if ($targetPhone) {
                    $message = "Halo {$purchasingUsers->name},\n\n";
                    $message .= "Pengajuan pembelian bahan dengan kode transaksi {$data->kode_transaksi} memerlukan persetujuan Anda sebagai Purchasing.\n\n";
                    $message .= "Tgl Pengajuan: {$data->tgl_pengajuan}\nPengaju: {$data->dataUser->name}\nDivisi: {$data->divisi}\nProject: {$data->tujuan}\nKeterangan: {$data->keterangan}\n\n";
                    $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
                    try{
                        $response = Http::withHeaders([
                            'x-api-key' => env('WHATSAPP_API_KEY'),
                            'Content-Type' => 'application/json',
                        ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
                            'chatId' => "{$targetPhone}@c.us",
                            'contentType' => 'string',
                            'content' => $message,
                        ]);
                        if ($response->successful()) {
                            LogHelper::success("WhatsApp message sent to: {$targetPhone}");
                        } else {
                            LogHelper::error("Failed to send WhatsApp message to: {$targetPhone}");
                        }
                    } catch (\Exception $e) {
                        LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
                    }
                } else {
                    LogHelper::error('No valid phone number found for WhatsApp notification.');
                }
                // Mengirim notifikasi ke pengaju tentang tahap approval
                $pengajuPhone = $data->dataUser->telephone;
                if ($pengajuPhone) {
                    $statusMessage = match ($data->status_general_manager) {
                        'Disetujui' => "telah *Disetujui* oleh General Affair.",
                        'Ditolak' => "telah *Ditolak* oleh General Affair.",
                        'Belum disetujui' => "masih *Menunggu Persetujuan* dari General Affair.",
                        default => "dalam status yang tidak dikenal.",
                    };
                    $message = "Halo {$data->dataUser->name},\n\n";
                    $message .= "Status pengajuan pembelian bahan Anda dengan Kode Transaksi {$data->kode_transaksi} {$statusMessage}\n\n";
                    $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
                    try{
                        $responsePengaju = Http::withHeaders([
                            'x-api-key' => env('WHATSAPP_API_KEY'),
                            'Content-Type' => 'application/json',
                        ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
                            'chatId' => "{$pengajuPhone}@c.us",
                            'contentType' => 'string',
                            'content' => $message,
                        ]);
                        if ($responsePengaju->successful()) {
                            LogHelper::success("WhatsApp message sent to pengaju: {$pengajuPhone}");
                        } else {
                            LogHelper::error("Failed to send WhatsApp message to pengaju: {$pengajuPhone}");
                        }
                    } catch (\Exception $e) {
                        LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
                    }
                }  else {
                    LogHelper::error('No valid phone number found for pengaju.');
                }
            }
            DB::commit();
            LogHelper::success('Status approval general Affair berhasil diubah.');
            return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Status approval general Affair berhasil diubah.');
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
            if ($data->status_leader === 'Disetujui') {
                $data->status_purchasing = $validated['status_purchasing'];
            } else {
                LogHelper::error('Status purchasing tidak dapat diubah karena leader belum menyetujui.');
                return redirect()->back()->with('error', 'Status purchasing tidak dapat diubah karena leader belum menyetujui.');
            }
            $data->save();
            if ($data->dataUser->job_level == 4) {
                if ($data->dataUser->atasan_level3_id === null && $data->dataUser->atasan_level2_id === null) {
                    // Job level 4 tanpa atasan level 3 dan 2, kirim notifikasi ke Finance
                    $financeUser = User::where('name', 'REVIDYA CHRISDWIMAYA PUTRI')->first();
                    if ($financeUser && $financeUser->telephone) {
                        $financePhone = $financeUser->telephone;
                        $message = "Halo {$financeUser->name},\n\n";
                        $message .= "Pengajuan pembelian bahan dengan kode transaksi {$data->kode_transaksi} memerlukan persetujuan Anda sebagai Finance.\n\n";
                        $message .= "Tgl Pengajuan: {$data->tgl_pengajuan}\nPengaju: {$data->dataUser->name}\nDivisi: {$data->divisi}\nProject: {$data->tujuan}\nKeterangan: {$data->keterangan}\n\n";
                        $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
                        try{
                            $response = Http::withHeaders([
                                'x-api-key' => env('WHATSAPP_API_KEY'),
                                'Content-Type' => 'application/json',
                            ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
                                'chatId' => "{$financePhone}@c.us",
                                'contentType' => 'string',
                                'content' => $message,
                            ]);

                            if ($response->successful()) {
                                LogHelper::success("WhatsApp notification sent to Finance: {$financePhone}");
                            } else {
                                LogHelper::error("Failed to send WhatsApp notification to Finance: {$financePhone}");
                            }
                        } catch (\Exception $e) {
                            LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
                        }
                    } else {
                        LogHelper::error('No valid phone number found for Finance notification.');
                    }
                } else {
                    // Kirim notifikasi ke atasan level 2/manager
                    $managerUser = $data->dataUser->atasanLevel2;
                    if ($managerUser && $managerUser->telephone) {
                        $managerPhone = $managerUser->telephone;
                        $message = "Halo {$managerUser->name},\n\n";
                        $message .= "Pengajuan pembelian bahan dengan kode transaksi {$data->kode_transaksi} memerlukan persetujuan Anda sebagai Manager.\n\n";
                        $message .= "Tgl Pengajuan: {$data->tgl_pengajuan}\nPengaju: {$data->dataUser->name}\nDivisi: {$data->divisi}\nProject: {$data->tujuan}\nKeterangan: {$data->keterangan}\n\n";
                        $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
                        try{
                            $response = Http::withHeaders([
                                'x-api-key' => env('WHATSAPP_API_KEY'),
                                'Content-Type' => 'application/json',
                            ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
                                'chatId' => "{$managerPhone}@c.us",
                                'contentType' => 'string',
                                'content' => $message,
                            ]);

                            if ($response->successful()) {
                                LogHelper::success("WhatsApp notification sent to Manager: {$managerPhone}");
                            } else {
                                LogHelper::error("Failed to send WhatsApp notification to Manager: {$managerPhone}");
                            }
                        } catch (\Exception $e) {
                            LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
                        }
                    } else {
                        LogHelper::error('No valid phone number found for Manager notification.');
                    }
                }
            }else{
                // Kirim notifikasi ke atasan level 2/manager
                $managerUser = $data->dataUser->atasanLevel2;
                if ($managerUser && $managerUser->telephone) {
                    $managerPhone = $managerUser->telephone;
                    $message = "Halo {$managerUser->name},\n\n";
                    $message .= "Pengajuan pembelian bahan dengan kode transaksi {$data->kode_transaksi} memerlukan persetujuan Anda sebagai Manager.\n\n";
                    $message .= "Tgl Pengajuan: {$data->tgl_pengajuan}\nPengaju: {$data->dataUser->name}\nDivisi: {$data->divisi}\nProject: {$data->tujuan}\nKeterangan: {$data->keterangan}\n\n";
                    $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
                    try{
                        $response = Http::withHeaders([
                            'x-api-key' => env('WHATSAPP_API_KEY'),
                            'Content-Type' => 'application/json',
                        ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
                            'chatId' => "{$managerPhone}@c.us",
                            'contentType' => 'string',
                            'content' => $message,
                        ]);

                        if ($response->successful()) {
                            LogHelper::success("WhatsApp notification sent to Manager: {$managerPhone}");
                        } else {
                            LogHelper::error("Failed to send WhatsApp notification to Manager: {$managerPhone}");
                        }
                    } catch (\Exception $e) {
                        LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
                    }
                } else {
                    LogHelper::error('No valid phone number found for Manager notification.');
                }
            }

            // Kirim notifikasi ke Pengaju
            $pengajuPhone = $data->dataUser->telephone;
            if ($pengajuPhone) {
                $statusMessage = match ($data->status_purchasing) {
                    'Disetujui' => "telah *Disetujui* oleh Purchasing.",
                    'Ditolak' => "telah *Ditolak* oleh Purchasing.",
                    'Belum disetujui' => "masih *Menunggu Persetujuan* dari Purchasing.",
                    default => "dalam status yang tidak dikenal.",
                };
                $message = "Halo {$data->dataUser->name},\n\n";
                $message .= "Status pengajuan pembelian bahan Anda dengan Kode Transaksi {$data->kode_transaksi} {$statusMessage}\n\n";
                $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
                try{
                    $responsePengaju = Http::withHeaders([
                        'x-api-key' => env('WHATSAPP_API_KEY'),
                        'Content-Type' => 'application/json',
                    ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
                        'chatId' => "{$pengajuPhone}@c.us",
                        'contentType' => 'string',
                        'content' => $message,
                    ]);

                    if ($responsePengaju->successful()) {
                        LogHelper::success("WhatsApp message sent to pengaju: {$pengajuPhone}");
                    } else {
                        LogHelper::error("Failed to send WhatsApp message to pengaju: {$pengajuPhone}");
                    }
                } catch (\Exception $e) {
                    LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
                }
            }  else {
                LogHelper::error('No valid phone number found for pengaju.');
            }
            DB::commit();
            LogHelper::success('Status approval purchasing berhasil diubah.');
            return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Status approval purchasing berhasil diubah.');
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
        ]);
        try {
            DB::beginTransaction();
            $data = PembelianBahan::with([
                'dataUser.atasanLevel1',
                'dataUser.atasanLevel2',
                'dataUser.atasanLevel3',
                'pembelianBahanDetails.dataBahan.dataUnit',
            ])->findOrFail($id);

            if ($data->status_purchasing === 'Disetujui') {
                $data->status_manager = $validated['status_manager'];
            } else {
                LogHelper::error('Status manager tidak dapat diubah karena purchasing belum menyetujui.');
                return redirect()->back()->with('error', 'Status manager tidak dapat diubah karena purchasing belum menyetujui.');
            }
            $data->save();

            if ($data->status_manager === 'Disetujui') {
                $financeUser = User::where('name', 'REVIDYA CHRISDWIMAYA PUTRI')->first();
                if ($financeUser && $financeUser->telephone) {
                    $financePhone = $financeUser->telephone;
                    $message = "Halo {$financeUser->name},\n\n";
                    $message .= "Pengajuan pembelian bahan dengan kode transaksi {$data->kode_transaksi} memerlukan persetujuan Anda sebagai Finance.\n\n";
                    $message .= "Tgl Pengajuan: {$data->tgl_pengajuan}\nPengaju: {$data->dataUser->name}\nDivisi: {$data->divisi}\nProject: {$data->tujuan}\nKeterangan: {$data->keterangan}\n\n";
                    $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";

                    try {
                        // Send WhatsApp message to Finance
                        $response = Http::withHeaders([
                            'x-api-key' => env('WHATSAPP_API_KEY'),
                            'Content-Type' => 'application/json',
                        ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
                            'chatId' => "{$financePhone}@c.us",
                            'contentType' => 'string',
                            'content' => $message,
                        ]);

                        if ($response->successful()) {
                            LogHelper::success("WhatsApp notification sent to Finance: {$financePhone}");
                        } else {
                            LogHelper::error("Failed to send WhatsApp notification to Finance: {$financePhone}");
                        }
                    } catch (\Exception $e) {
                        LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
                    }
                } else {
                    LogHelper::error('No valid phone number found for Finance notification.');
                }
            }

            $pengajuPhone = $data->dataUser->telephone;
            if ($pengajuPhone) {
                $statusMessage = match ($data->status_manager) {
                    'Disetujui' => "telah *Disetujui* oleh Manager.",
                    'Ditolak' => "telah *Ditolak* oleh Manager.",
                    'Belum disetujui' => "masih *Menunggu Persetujuan* dari Manager.",
                    default => "dalam status yang tidak dikenal.",
                };
                $message = "Halo {$data->dataUser->name},\n\n";
                $message .= "Status pengajuan pembelian bahan Anda dengan Kode Transaksi {$data->kode_transaksi} {$statusMessage}\n\n";
                $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
                try{
                    $responsePengaju = Http::withHeaders([
                        'x-api-key' => env('WHATSAPP_API_KEY'),
                        'Content-Type' => 'application/json',
                    ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
                        'chatId' => "{$pengajuPhone}@c.us",
                        'contentType' => 'string',
                        'content' => $message,
                    ]);
                    if ($responsePengaju->successful()) {
                        LogHelper::success("WhatsApp message sent to pengaju: {$pengajuPhone}");
                    } else {
                        LogHelper::error("Failed to send WhatsApp message to pengaju: {$pengajuPhone}");
                    }
                } catch (\Exception $e) {
                    LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
                }
            }  else {
                LogHelper::error('No valid phone number found for pengaju.');
            }
            DB::commit();
            LogHelper::success('Status approval manager berhasil diubah.');
            return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Status approval manager berhasil diubah.');
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
        ]);
        try {
            DB::beginTransaction();
            $data = PembelianBahan::with([
                'dataUser.atasanLevel1',
                'dataUser.atasanLevel2',
                'dataUser.atasanLevel3',
                'pembelianBahanDetails.dataBahan.dataUnit',
            ])->findOrFail($id);

            if ($data->status_manager === 'Disetujui') {
                $data->status_finance = $validated['status_finance'];
            } else {
                LogHelper::error('Status finance tidak dapat diubah karena manager belum menyetujui.');
                return redirect()->back()->with('error', 'Status finance tidak dapat diubah karena manager belum menyetujui.');
            }
            $data->save();

            // Kirim notifikasi ke Pengaju
            $pengajuPhone = $data->dataUser->telephone;
            if ($pengajuPhone) {
                $statusMessage = match ($data->status_finance) {
                    'Disetujui' => "telah *Disetujui* oleh Finance.",
                    'Ditolak' => "telah *Ditolak* oleh Finance.",
                    'Belum disetujui' => "masih *Menunggu Persetujuan* dari Finance.",
                    default => "dalam status yang tidak dikenal.",
                };
                $message = "Halo {$data->dataUser->name},\n\n";
                $message .= "Status pengajuan pembelian bahan Anda dengan Kode Transaksi {$data->kode_transaksi} {$statusMessage}\n\n";

                $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
                try{
                    $responsePengaju = Http::withHeaders([
                        'x-api-key' => env('WHATSAPP_API_KEY'),
                        'Content-Type' => 'application/json',
                    ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
                        'chatId' => "{$pengajuPhone}@c.us",
                        'contentType' => 'string',
                        'content' => $message,
                    ]);
                    if ($responsePengaju->successful()) {
                        LogHelper::success("WhatsApp message sent to pengaju: {$pengajuPhone}");
                    } else {
                        LogHelper::error("Failed to send WhatsApp message to pengaju: {$pengajuPhone}");
                    }
                } catch (\Exception $e) {
                    LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
                }
            }  else {
                LogHelper::error('No valid phone number found for pengaju.');
            }
            DB::commit();
            LogHelper::success('Status approval finance berhasil diubah.');
            return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Status approval finance berhasil diubah.');
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
        ]);
        try {
            DB::beginTransaction();
            $data = PembelianBahan::with([
                'dataUser.atasanLevel1',
                'dataUser.atasanLevel2',
                'dataUser.atasanLevel3',
                'pembelianBahanDetails.dataBahan.dataUnit',
            ])->findOrFail($id);

            if ($data->status_finance === 'Disetujui') {
                $data->status_admin_manager = $validated['status_admin_manager'];
            } else {
                LogHelper::error('Status admin manager tidak dapat diubah karena finance belum menyetujui.');
                return redirect()->back()->with('error', 'Status admin manager tidak dapat diubah karena finance belum menyetujui.');
            }
            $data->save();
            // Kirim notifikasi ke Pengaju
            $pengajuPhone = $data->dataUser->telephone;
            if ($pengajuPhone) {
                $statusMessage = match ($data->status_admin_manager) {
                    'Disetujui' => "telah *Disetujui* oleh Manager Admin.",
                    'Ditolak' => "telah *Ditolak* oleh Manager Admin.",
                    'Belum disetujui' => "masih *Menunggu Persetujuan* dari Manager Admin.",
                    default => "dalam status yang tidak dikenal.",
                };
                $message = "Halo {$data->dataUser->name},\n\n";
                $message .= "Status pengajuan pembelian bahan Anda dengan Kode Transaksi {$data->kode_transaksi} {$statusMessage}\n\n";
                $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
                try{
                    $responsePengaju = Http::withHeaders([
                        'x-api-key' => env('WHATSAPP_API_KEY'),
                        'Content-Type' => 'application/json',
                    ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
                        'chatId' => "{$pengajuPhone}@c.us",
                        'contentType' => 'string',
                        'content' => $message,
                    ]);

                    if ($responsePengaju->successful()) {
                        LogHelper::success("WhatsApp message sent to pengaju: {$pengajuPhone}");
                    } else {
                        LogHelper::error("Failed to send WhatsApp message to pengaju: {$pengajuPhone}");
                    }
                } catch (\Exception $e) {
                    LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
                }
            }  else {
                LogHelper::error('No valid phone number found for pengaju.');
            }
            DB::commit();
            LogHelper::success('Status approval admin manager berhasil diubah.');
            return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Status approval admin manager berhasil diubah.');
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
        ]);

        try {
            DB::beginTransaction();
            $data = PembelianBahan::with([
                'dataUser.atasanLevel1',
                'dataUser.atasanLevel2',
                'dataUser.atasanLevel3',
                'pembelianBahanDetails.dataBahan.dataUnit',
            ])->findOrFail($id);

            if ($data->status_admin_manager !== 'Disetujui') {
                LogHelper::error('Status direktur tidak dapat diubah karena manager admin belum menyetujui.');
                return redirect()->back()->with('error', 'Status direktur tidak dapat diubah karena manager admin belum menyetujui.');
            }

            $data->status = $validated['status'];
            if ($validated['status'] === 'Disetujui') {
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
                            ->where('bahan_id', $detail->bahan_id)
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
                            // Update data yang sudah ada
                            $existingDetail->sub_total += $detail->jml_bahan * array_sum(array_column($groupedDetails, 'unit_price'));
                            $existingDetail->sub_total_usd += $detail->jml_bahan * array_sum(array_column($groupedDetailsUSD, 'unit_price_usd'));
                            $existingDetail->jml_bahan = $detail->jml_bahan;
                            // Gabungkan data details
                            $currentDetails = json_decode($existingDetail->details, true) ?? [];
                            $mergedDetails = array_merge($currentDetails, $groupedDetails);

                            $currentDetailsUSD = json_decode($existingDetail->details_usd, true) ?? [];
                            $mergedDetailsUSD = array_merge($currentDetailsUSD, $groupedDetailsUSD);

                            $existingDetail->details = json_encode(array_values($mergedDetails));
                            $existingDetail->details_usd = json_encode(array_values($mergedDetailsUSD));
                            $existingDetail->save();
                        } else {
                            // Buat data baru jika belum ada
                            PengajuanDetails::create([
                                'pengajuan_id' => $data->pengajuan_id,
                                'bahan_id' => $detail->bahan_id,
                                'qty' => array_sum(array_column($groupedDetails, 'qty')),
                                'jml_bahan' => $detail->jml_bahan,
                                'used_materials' => $detail->jml_bahan,
                                'details' => json_encode(array_values($groupedDetails)),
                                'details_usd' => json_encode(array_values($groupedDetailsUSD)),
                                'sub_total' => $detail->jml_bahan * array_sum(array_column($groupedDetails, 'unit_price')),
                                'sub_total_usd' => $detail->jml_bahan * array_sum(array_column($groupedDetailsUSD, 'unit_price_usd')),
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
                            $pengajuan->selesai_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');;
                            $pengajuan->status = 'Selesai';
                            $pengajuan->save();
                        }
                    }
                }
            }
            $data->save();
            // Kirim notifikasi ke Pengaju
            $pengajuPhone = $data->dataUser->telephone;
            if ($pengajuPhone) {
                $statusMessage = match ($data->status) {
                    'Disetujui' => "telah *Disetujui* oleh Direktur.",
                    'Ditolak' => "telah *Ditolak* oleh Direktur.",
                    'Belum disetujui' => "masih *Menunggu Persetujuan* dari Direktur.",
                    default => "dalam status yang tidak dikenal.",
                };
                $message = "Halo {$data->dataUser->name},\n\n";
                $message .= "Status pengajuan pembelian bahan Anda dengan Kode Transaksi {$data->kode_transaksi} {$statusMessage}\n\n";
                $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
                try{
                    $responsePengaju = Http::withHeaders([
                        'x-api-key' => env('WHATSAPP_API_KEY'),
                        'Content-Type' => 'application/json',
                    ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
                        'chatId' => "{$pengajuPhone}@c.us",
                        'contentType' => 'string',
                        'content' => $message,
                    ]);

                    if ($responsePengaju->successful()) {
                        LogHelper::success("WhatsApp message sent to pengaju: {$pengajuPhone}");
                    } else {
                        LogHelper::error("Failed to send WhatsApp message to pengaju: {$pengajuPhone}");
                    }
                } catch (\Exception $e) {
                    LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
                }
            }  else {
                LogHelper::error('No valid phone number found for pengaju.');
            }
            DB::commit();
            LogHelper::success('Status approval direktur berhasil diubah.');
            return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Status approval direktur berhasil diubah.');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan. Pesan error: $errorMessage");
        }
    }

    // public function update(Request $request, string $id)
    // {
    //     $validated = $request->validate([
    //         'status' => 'required',
    //     ]);

    //     try {
    //         DB::beginTransaction(); // Mulai transaksi

    //         $data = BahanKeluar::find($id);
    //         $details = BahanKeluarDetails::where('bahan_keluar_id', $id)->get();

    //         $pendingStockReductions = [];
    //         $groupedDetails = []; // Pastikan ini diinisialisasi

    //         if ($validated['status'] === 'Disetujui') {
    //             $tgl_keluar = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
    //             $data->tgl_keluar = $tgl_keluar;

    //             foreach ($details as $detail) {
    //                 $transactionDetails = json_decode($detail->details, true) ?? [];
    //                 if (empty($transactionDetails)) {
    //                     if ($data->produksi_id) {
    //                         // Check if the bahan_id already exists in ProduksiDetails
    //                         $existingDetail = ProduksiDetails::where('produksi_id', $data->produksi_id)
    //                         ->where('bahan_id', $detail->bahan_id)
    //                         ->first();

    //                         if (!$existingDetail) {
    //                             ProduksiDetails::create([
    //                                 'produksi_id' => $data->produksi_id,
    //                                 'bahan_id' => $detail->bahan_id,
    //                                 'qty' => 0, // Set qty to 0 if there are no transaction details
    //                                 'jml_bahan' => $detail->jml_bahan,
    //                                 'used_materials' => 0,
    //                                 'details' => json_encode([]), // Set details as an empty array
    //                                 'sub_total' => 0, // Set sub_total to 0 if details are null or empty
    //                             ]);
    //                         }
    //                          // Continue to the next detail
    //                     }
    //                     elseif ($data->projek_id) {
    //                         $existingDetail = ProjekDetails::where('projek_id', $data->projek_id)
    //                             ->where('bahan_id', $detail->bahan_id)
    //                             ->first();

    //                         if (!$existingDetail) {
    //                             ProjekDetails::create([
    //                                 'projek_id' => $data->projek_id,
    //                                 'bahan_id' => $detail->bahan_id,
    //                                 'qty' => 0,
    //                                 'jml_bahan' => $detail->jml_bahan,
    //                                 'used_materials' => 0,
    //                                 'details' => json_encode([]),
    //                                 'sub_total' => 0,
    //                             ]);
    //                         }
    //                     }
    //                     elseif ($data->projek_rnd_id) {
    //                         $existingDetail = ProjekRndDetails::where('projek_rnd_id', $data->projek_rnd_id)
    //                             ->where('bahan_id', $detail->bahan_id)
    //                             ->first();

    //                         if (!$existingDetail) {
    //                             ProjekRndDetails::create([
    //                                 'projek_rnd_id' => $data->projek_rnd_id,
    //                                 'bahan_id' => $detail->bahan_id,
    //                                 'qty' => 0,
    //                                 'jml_bahan' => $detail->jml_bahan,
    //                                 'used_materials' => 0,
    //                                 'details' => json_encode([]),
    //                                 'sub_total' => 0,
    //                             ]);
    //                         }
    //                     }
    //                     elseif ($data->pengajuan_id) {
    //                         $existingDetail = PengajuanDetails::where('pengajuan_id', $data->pengajuan_id)
    //                             ->where('bahan_id', $detail->bahan_id)
    //                             ->first();

    //                         if (!$existingDetail) {
    //                             PengajuanDetails::create([
    //                                 'pengajuan_id' => $data->pengajuan_id,
    //                                 'bahan_id' => $detail->bahan_id,
    //                                 'qty' => 0,
    //                                 'jml_bahan' => $detail->jml_bahan,
    //                                 'used_materials' => 0,
    //                                 'details' => json_encode([]),
    //                                 'sub_total' => 0,
    //                             ]);
    //                         }
    //                     }
    //                     continue;
    //                 }

    //                 // Aggregate quantities by unit_price
    //                 foreach ($transactionDetails as $transaksiDetail) {
    //                     $unitPrice = $transaksiDetail['unit_price'];
    //                     $qty = $transaksiDetail['qty'];

    //                     // Add or merge quantities by `unit_price`
    //                     if (isset($groupedDetails[$unitPrice])) {
    //                         $groupedDetails[$unitPrice]['qty'] += $qty;
    //                     } else {
    //                         $groupedDetails[$unitPrice] = [
    //                             'qty' => $qty,
    //                             'unit_price' => $unitPrice,
    //                         ];
    //                     }
    //                 }

    //                 if (is_array($transactionDetails)) {
    //                     $groupedDetails = [];
    //                     foreach ($transactionDetails as $transaksiDetail) {
    //                         $setengahJadiDetail = BahanSetengahjadiDetails::where('bahan_id', $detail->bahan_id)
    //                             ->whereHas('bahanSetengahjadi', function ($query) use ($transaksiDetail) {
    //                                 $query->where('kode_transaksi', $transaksiDetail['kode_transaksi']);
    //                             })
    //                             ->where('unit_price', $transaksiDetail['unit_price'])
    //                             ->first();

    //                         if ($setengahJadiDetail) {
    //                             if ($transaksiDetail['qty'] > $setengahJadiDetail->sisa) {
    //                                 throw new \Exception('Tolak pengajuan, Stok bahan setengah jadi tidak cukup!');
    //                             }

    //                             $unitPrice = $transaksiDetail['unit_price'];
    //                             if (isset($groupedDetails[$unitPrice])) {
    //                                 // Jika harga satuan sudah ada, tingkatkan qty
    //                                 $groupedDetails[$unitPrice]['qty'] += $transaksiDetail['qty'];
    //                             } else {
    //                                 // Buat entri baru jika harga satuan belum ada
    //                                 $groupedDetails[$unitPrice] = [
    //                                     'qty' => $transaksiDetail['qty'],
    //                                     'unit_price' => $unitPrice,
    //                                 ];
    //                             }

    //                             $setengahJadiDetail->sisa -= $transaksiDetail['qty'];
    //                             $setengahJadiDetail->sisa = max(0, $setengahJadiDetail->sisa);
    //                             $setengahJadiDetail->save();
    //                         } else {
    //                             $purchaseDetail = PurchaseDetail::where('bahan_id', $detail->bahan_id)
    //                                 ->whereHas('purchase', function ($query) use ($transaksiDetail) {
    //                                     $query->where('kode_transaksi', $transaksiDetail['kode_transaksi']);
    //                                 })
    //                                 ->where('unit_price', $transaksiDetail['unit_price'])
    //                                 ->first();

    //                             if ($purchaseDetail) {
    //                                 if ($transaksiDetail['qty'] > $purchaseDetail->sisa) {
    //                                     throw new \Exception('Tolak pengajuan, Lakukan pengajuan bahan kembali!');
    //                                 }

    //                                 $unitPrice = $transaksiDetail['unit_price'];
    //                                 if (isset($groupedDetails[$unitPrice])) {
    //                                     // Jika harga satuan sudah ada, tingkatkan qty
    //                                     $groupedDetails[$unitPrice]['qty'] += $transaksiDetail['qty'];
    //                                 } else {
    //                                     // Buat entri baru jika harga satuan belum ada
    //                                     $groupedDetails[$unitPrice] = [
    //                                         'qty' => $transaksiDetail['qty'],
    //                                         'unit_price' => $unitPrice,
    //                                     ];
    //                                 }

    //                                 $purchaseDetail->sisa -= $transaksiDetail['qty'];
    //                                 $purchaseDetail->sisa = max(0, $purchaseDetail->sisa);
    //                                 $purchaseDetail->save();
    //                             }
    //                         }
    //                     }

    //                     if ($data->produksi_id) {
    //                         foreach ($groupedDetails as $unitPrice => $group) {
    //                             $produksiDetail = ProduksiDetails::where('produksi_id', $data->produksi_id)
    //                                 ->where('bahan_id', $detail->bahan_id)
    //                                 ->first();

    //                             if ($produksiDetail) {
    //                                 // Update existing entry
    //                                 $produksiDetail->qty += $group['qty'];  // Use the aggregated qty from groupedDetails
    //                                 $produksiDetail->used_materials += $group['qty'];
    //                                 $produksiDetail->sub_total += $group['qty'] * $unitPrice;

    //                                 // Merge existing details with new grouped details
    //                                 $currentDetails = json_decode($produksiDetail->details, true) ?? [];
    //                                 $mergedDetails = [];

    //                                 foreach ($currentDetails as $existingDetail) {
    //                                     $price = $existingDetail['unit_price'];
    //                                     $mergedDetails[$price] = $existingDetail;
    //                                 }

    //                                 // Update or add new quantities in mergedDetails
    //                                 if (isset($mergedDetails[$unitPrice])) {
    //                                     $mergedDetails[$unitPrice]['qty'] += $group['qty'];
    //                                 } else {
    //                                     $mergedDetails[$unitPrice] = $group; // add new entry
    //                                 }

    //                                 // Update the details field
    //                                 $produksiDetail->details = json_encode(array_values($mergedDetails));
    //                                 $produksiDetail->save();
    //                             } else {
    //                                 // Create new entry
    //                                 ProduksiDetails::create([
    //                                     'produksi_id' => $data->produksi_id,
    //                                     'bahan_id' => $detail->bahan_id,
    //                                     'qty' => $group['qty'],
    //                                     'jml_bahan' => $detail->jml_bahan,
    //                                     'used_materials' => $group['qty'],
    //                                     'details' => json_encode([$group]), // use an array of groups
    //                                     'sub_total' => $group['qty'] * $unitPrice,
    //                                 ]);
    //                             }
    //                         }
    //                     } if ($data->projek_id) {
    //                         foreach ($groupedDetails as $unitPrice => $group) {
    //                             $projekDetail = ProjekDetails::where('projek_id', $data->projek_id)
    //                                 ->where('bahan_id', $detail->bahan_id)
    //                                 ->first();

    //                             if ($projekDetail) {
    //                                 // Update existing entry
    //                                 $projekDetail->qty += $group['qty'];
    //                                 $projekDetail->used_materials += $group['qty'];
    //                                 $projekDetail->sub_total += $group['qty'] * $unitPrice;

    //                                 if ($projekDetail->jml_bahan !== $detail->jml_bahan) {
    //                                     $projekDetail->jml_bahan = $detail->jml_bahan; // Update jml_bahan
    //                                 }

    //                                 // Merge existing details with new grouped details
    //                                 $currentDetails = json_decode($projekDetail->details, true) ?? [];
    //                                 $mergedDetails = [];

    //                                 foreach ($currentDetails as $existingDetail) {
    //                                     $price = $existingDetail['unit_price'];
    //                                     $mergedDetails[$price] = $existingDetail;
    //                                 }

    //                                 // Update or add new quantities in mergedDetails
    //                                 if (isset($mergedDetails[$unitPrice])) {
    //                                     $mergedDetails[$unitPrice]['qty'] += $group['qty'];
    //                                 } else {
    //                                     $mergedDetails[$unitPrice] = $group; // add new entry
    //                                 }

    //                                 // Update the details field
    //                                 $projekDetail->details = json_encode(array_values($mergedDetails));
    //                                 $projekDetail->save();
    //                             } else {
    //                                 // Create new entry
    //                                 ProjekDetails::create([
    //                                     'projek_id' => $data->projek_id,
    //                                     'bahan_id' => $detail->bahan_id,
    //                                     'qty' => $group['qty'],
    //                                     'jml_bahan' => $detail->jml_bahan,
    //                                     'used_materials' => $group['qty'],
    //                                     'details' => json_encode([$group]), // use an array of groups
    //                                     'sub_total' => $group['qty'] * $unitPrice,
    //                                 ]);
    //                             }
    //                         }
    //                     }if ($data->projek_rnd_id) {
    //                         foreach ($groupedDetails as $unitPrice => $group) {
    //                             $projekRndDetail = ProjekRndDetails::where('projek_rnd_id', $data->projek_rnd_id)
    //                                 ->where('bahan_id', $detail->bahan_id)
    //                                 ->first();

    //                             if ($projekRndDetail) {
    //                                 // Update existing entry
    //                                 $projekRndDetail->qty += $group['qty'];
    //                                 $projekRndDetail->used_materials += $group['qty'];
    //                                 $projekRndDetail->sub_total += $group['qty'] * $unitPrice;

    //                                 if ($projekRndDetail->jml_bahan !== $detail->jml_bahan) {
    //                                     $projekRndDetail->jml_bahan = $detail->jml_bahan; // Update jml_bahan
    //                                 }

    //                                 // Merge existing details with new grouped details
    //                                 $currentDetails = json_decode($projekRndDetail->details, true) ?? [];
    //                                 $mergedDetails = [];

    //                                 foreach ($currentDetails as $existingDetail) {
    //                                     $price = $existingDetail['unit_price'];
    //                                     $mergedDetails[$price] = $existingDetail;
    //                                 }

    //                                 // Update or add new quantities in mergedDetails
    //                                 if (isset($mergedDetails[$unitPrice])) {
    //                                     $mergedDetails[$unitPrice]['qty'] += $group['qty'];
    //                                 } else {
    //                                     $mergedDetails[$unitPrice] = $group; // add new entry
    //                                 }

    //                                 // Update the details field
    //                                 $projekRndDetail->details = json_encode(array_values($mergedDetails));
    //                                 $projekRndDetail->save();
    //                             } else {
    //                                 // Create new entry
    //                                 ProjekRndDetails::create([
    //                                     'projek_rnd_id' => $data->projek_rnd_id,
    //                                     'bahan_id' => $detail->bahan_id,
    //                                     'qty' => $group['qty'],
    //                                     'jml_bahan' => $detail->jml_bahan,
    //                                     'used_materials' => $group['qty'],
    //                                     'details' => json_encode([$group]), // use an array of groups
    //                                     'sub_total' => $group['qty'] * $unitPrice,
    //                                 ]);
    //                             }
    //                         }
    //                     }if ($data->pengajuan_id) {
    //                         foreach ($groupedDetails as $unitPrice => $group) {
    //                             $pengajuanDetail = PengajuanDetails::where('pengajuan_id', $data->pengajuan_id)
    //                                 ->where('bahan_id', $detail->bahan_id)
    //                                 ->first();

    //                             if ($pengajuanDetail) {
    //                                 // Update existing entry
    //                                 $pengajuanDetail->qty += $group['qty'];
    //                                 $pengajuanDetail->used_materials += $group['qty'];
    //                                 $pengajuanDetail->sub_total += $group['qty'] * $unitPrice;

    //                                 if ($pengajuanDetail->jml_bahan !== $detail->jml_bahan) {
    //                                     $pengajuanDetail->jml_bahan = $detail->jml_bahan; // Update jml_bahan
    //                                 }

    //                                 // Merge existing details with new grouped details
    //                                 $currentDetails = json_decode($pengajuanDetail->details, true) ?? [];
    //                                 $mergedDetails = [];

    //                                 foreach ($currentDetails as $existingDetail) {
    //                                     $price = $existingDetail['unit_price'];
    //                                     $mergedDetails[$price] = $existingDetail;
    //                                 }

    //                                 // Update or add new quantities in mergedDetails
    //                                 if (isset($mergedDetails[$unitPrice])) {
    //                                     $mergedDetails[$unitPrice]['qty'] += $group['qty'];
    //                                 } else {
    //                                     $mergedDetails[$unitPrice] = $group; // add new entry
    //                                 }

    //                                 // Update the details field
    //                                 $pengajuanDetail->details = json_encode(array_values($mergedDetails));
    //                                 $pengajuanDetail->save();
    //                             } else {
    //                                 // Create new entry
    //                                 PengajuanDetails::create([
    //                                     'pengajuan_id' => $data->pengajuan_id,
    //                                     'bahan_id' => $detail->bahan_id,
    //                                     'qty' => $group['qty'],
    //                                     'jml_bahan' => $detail->jml_bahan,
    //                                     'used_materials' => $group['qty'],
    //                                     'details' => json_encode([$group]), // use an array of groups
    //                                     'sub_total' => $group['qty'] * $unitPrice,
    //                                 ]);
    //                             }
    //                         }
    //                     }
    //                 }
    //             }

    //             // Kurangi stok
    //             foreach ($pendingStockReductions as $reduction) {
    //                 $reduction['detail']->sisa -= $reduction['qty'];
    //                 $reduction['detail']->sisa = max(0, $reduction['detail']->sisa);
    //                 $reduction['detail']->save();
    //             }
    //         }

    //         $data->status = $validated['status'];
    //         $data->save();

    //         // Kirim notifikasi ke Pengaju
    //         $pengajuPhone = $data->dataUser->telephone;
    //         if ($pengajuPhone) {
    //             $approvalLeader = $data->status_leader === 'Disetujui' ? '✅ Disetujui' : ($data->status_leader === 'Ditolak' ? '❌ Ditolak' : '⏳ Menunggu');
    //             $approvalPurchasing = $data->status_purchasing === 'Disetujui' ? '✅ Disetujui' : ($data->status_purchasing === 'Ditolak' ? '❌ Ditolak' : '⏳ Menunggu');
    //             $approvalManager = $data->status_manager === 'Disetujui' ? '✅ Disetujui' : ($data->status_manager === 'Ditolak' ? '❌ Ditolak' : '⏳ Menunggu');
    //             $approvalFinance = $data->status_finance === 'Disetujui' ? '✅ Disetujui' : ($data->status_finance === 'Ditolak' ? '❌ Ditolak' : '⏳ Menunggu');
    //             $approvalAdminManager = $data->status_admin_manager === 'Disetujui' ? '✅ Disetujui' : ($data->status_admin_manager === 'Ditolak' ? '❌ Ditolak' : '⏳ Menunggu');
    //             $approvalDirector = $data->status === 'Disetujui' ? '✅ Disetujui' : ($data->status_direktur === 'Ditolak' ? '❌ Ditolak' : '⏳ Menunggu');


    //             // Susun pesan untuk pengaju
    //             $message = "Halo {$data->dataUser->name},\n\n";
    //             if ($data->status_admin_manager === 'Disetujui') {
    //                 $message .= "Status pengajuan bahan Anda dengan Kode Transaksi {$data->kode_transaksi} telah disetujui oleh Direktur.\n";
    //                 $message .= "Tahap berikutnya adalah Cetak/Simpan Dokumen Pengajuan, Kemudian ambil bahan ke bagian Purchasing.\n\n";
    //             } elseif ($data->status_admin_manager === 'Ditolak') {
    //                 $message .= "Maaf, pengajuan bahan Anda dengan Kode Transaksi {$data->kode_transaksi} telah ditolak oleh Direktur.\n";
    //                 $message .= "Mohon periksa kembali untuk mengetahui alasan penolakan.\n\n";
    //             }

    //             $message .= "Tahapan Pengajuan:\n";
    //             $message .= "1. Approval Leader: {$approvalLeader}\n";
    //             $message .= "2. Approval Purchasing: {$approvalPurchasing}\n";
    //             $message .= "3. Approval Manager: {$approvalManager}\n";
    //             $message .= "4. Approval Finance: {$approvalFinance}\n";
    //             $message .= "5. Approval Manager Admin: {$approvalAdminManager}\n";
    //             $message .= "6. Approval Direktur: {$approvalDirector}\n\n";
    //             $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
    //             try{
    //                 // Kirim pesan WhatsApp ke pengaju
    //                 $responsePengaju = Http::withHeaders([
    //                     'x-api-key' => env('WHATSAPP_API_KEY'),
    //                     'Content-Type' => 'application/json',
    //                 ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
    //                     'chatId' => "{$pengajuPhone}@c.us",
    //                     'contentType' => 'string',
    //                     'content' => $message,
    //                 ]);

    //                 if ($responsePengaju->successful()) {
    //                     LogHelper::success("WhatsApp message sent to pengaju: {$pengajuPhone}");
    //                 } else {
    //                     LogHelper::error("Failed to send WhatsApp message to pengaju: {$pengajuPhone}");
    //                 }
    //             } catch (\Exception $e) {
    //                 LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
    //             }
    //         } else {
    //             LogHelper::error('No valid phone number found for pengaju.');
    //         }
    //         DB::commit();
    //         LogHelper::success('Berhasil Mengubah Status pembelian bahan!');
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         $errorMessage = $e->getMessage();
    //         $errorColumn = '';
    //         if (strpos($errorMessage, 'tgl_keluar') !== false) {
    //             $errorColumn = 'tgl_keluar';
    //         } elseif (strpos($errorMessage, 'status') !== false) {
    //             $errorColumn = 'status';
    //         }
    //         LogHelper::error($e->getMessage());
    //         return redirect()->back()->with('error', "Terjadi kesalahan pada kolom: $errorColumn. Pesan error: $errorMessage");
    //     }

    //     return redirect()->route('pembelian-bahan.index')->with('success', 'Status berhasil diubah.');
    // }

    // public function updatepengambilan(Request $request, string $id)
    // {
    //     $validated = $request->validate([
    //         'status' => 'required|string|in:Belum Diambil,Sudah Diambil',
    //     ]);
    //     try {
    //         $data = BahanKeluar::findOrFail($id);
    //         $data->status_pengambilan = $validated['status'];
    //         $data->save();
    //         LogHelper::success('Berhasil Mengubah Status pengambilan pembelian bahan!');
    //         return redirect()->route('pembelian-bahan.index')->with('success', 'Status pengambilan berhasil diubah.');
    //     } catch (\Exception $e) {
    //         LogHelper::error("Error updating status pengambilan: " . $e->getMessage());
    //         return redirect()->back()->with('error', 'Terjadi kesalahan saat mengubah status.');
    //     }
    // }

    public function destroy(string $id)
    {
        try{
            $data = PembelianBahan::find($id);
            if (!$data) {
                return redirect()->back()->with('gagal', 'Transaksi tidak ditemukan.');
            }
            $data->delete();
            LogHelper::success('Berhasil Menghapus Pengajuan Pembelian Bahan!');
            return redirect()->route('pengajuan-pembelian-bahan.index')->with('success', 'Berhasil Menghapus Pengajuan Pembelian Bahan!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
