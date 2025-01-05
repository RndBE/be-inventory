<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Models\User;
use App\Models\Bahan;
use App\Models\Produk;
use App\Models\BahanJadi;
use App\Models\Pengajuan;
use App\Helpers\LogHelper;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use Illuminate\Http\Request;
use App\Models\DetailProduksi;
use App\Models\PembelianBahan;
use App\Models\ProdukProduksi;
use App\Models\PurchaseDetail;
use App\Models\ProduksiDetails;
use App\Exports\PengajuanExport;
use App\Models\BahanJadiDetails;
use App\Models\PengajuanDetails;
use App\Models\BahanReturDetails;
use App\Models\BahanRusakDetails;
use App\Models\BahanSetengahjadi;
use App\Models\BahanKeluarDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\PembelianBahanDetails;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Support\Facades\Validator;

class PengajuanController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-pengajuan', ['only' => ['index']]);
        $this->middleware('permission:selesai-pengajuan', ['only' => ['updateStatus']]);
        $this->middleware('permission:tambah-pengajuan', ['only' => ['create','store']]);
        $this->middleware('permission:edit-pengajuan', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-pengajuan', ['only' => ['destroy']]);
    }

    // public function export($pengajuan_id)
    // {
    //     // Ambil nama proyek berdasarkan `pengajuan_id`
    //     $pengajuan = Pengajuan::findOrFail($pengajuan_id); // Mengambil pengajuan dengan id terkait

    //     // Gunakan nama proyek di nama file ekspor
    //     $fileName = 'HPP_Project_' . $pengajuan->keterangan . '_be-inventory.xlsx';

    //     return Excel::download(new PengajuanExport($pengajuan_id), $fileName);
    // }



    public function index()
    {
        return view('pages.pengajuan.index');
    }

    public function create()
    {
        $units = Unit::all();
        $produkProduksi = ProdukProduksi::all();
        return view('pages.pengajuan.create', compact('units', 'produkProduksi'));
    }

    public function store(Request $request)
    {
        try {
            // dd($request->all());
            DB::beginTransaction();
            // Validasi input
            $cartItems = json_decode($request->cartItems, true);
            $validator = Validator::make([
                'divisi' => $request->divisi,
                'project' => $request->project,
                'keterangan' => $request->keterangan,
                'jenis_pengajuan' => $request->jenis_pengajuan,
                'cartItems' => $cartItems
            ], [
                'divisi' => 'required',
                'project' => 'required',
                'keterangan' => 'required',
                'jenis_pengajuan' => 'required',
                'cartItems' => 'required|array',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $tujuan = $request->project;
            $user = Auth::user();
            $jenisPengajuan = $request->jenis_pengajuan;

            $purchasingUser = User::whereHas('dataJobPosition', function ($query) {
                $query->where('nama', 'Purchasing');
            })->where('job_level', 3)->first();

            $generalManagerUser = User::whereHas('dataJobPosition', function ($query) {
                $query->where('nama', 'Secretary');
            })->where('job_level', 4)->first();

            $lastTransaction = PembelianBahan::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number = ($lastTransaction ? intval(substr($lastTransaction->kode_transaksi, 6)) : 0) + 1;
            $kode_transaksi = 'KPB - ' . str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT). ' PBL';
            $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            $lastTransactionProjek = Pengajuan::orderByRaw('CAST(SUBSTRING(kode_pengajuan, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number_produksi = ($lastTransactionProjek ? intval(substr($lastTransactionProjek->kode_pengajuan, 6)) : 0) + 1;
            $kode_pengajuan = 'PBL - ' . str_pad($new_transaction_number_produksi, 5, '0', STR_PAD_LEFT);

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
                    // Kirim notifikasi ke Secretary
                    $targetPhone = $generalManagerUser ? $generalManagerUser->telephone : null;
                    $recipientName = $generalManagerUser ? $generalManagerUser->name : 'Secretary';
                } elseif ($user->job_level == 4 && $user->atasan_level3_id === null && $user->atasan_level2_id === null) {
                    // Job level 4 dan atasan_level3_id, atasan_level2_id null
                    $status_leader = 'Disetujui';
                    $status_manager = 'Disetujui'; // Menunggu approval manager
                    // Kirim notifikasi ke Secretary
                    $targetPhone = $generalManagerUser ? $generalManagerUser->telephone : null;
                    $recipientName = $generalManagerUser ? $generalManagerUser->name : 'Secretary';
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
                    // Job level lainnya, kirim ke Secretary
                    $status_leader = 'Belum disetujui';
                    $status_manager = 'Belum disetujui'; // Menunggu approval manager
                    $targetPhone = $generalManagerUser ? $generalManagerUser->telephone : null;
                    $recipientName = $generalManagerUser ? $generalManagerUser->name : 'Secretary';
                }
            }



            // Simpan data pengajuan
            $pengajuan = Pengajuan::create([
                'kode_pengajuan' => $kode_pengajuan,
                'mulai_pengajuan' => $tgl_pengajuan,
                'divisi' => $request->divisi,
                'pengaju' => $user->name,
                'project' => $request->project,
                'keterangan' => $request->keterangan,
                'jenis_pengajuan' => $request->jenis_pengajuan,
                'status' => 'Dalam Proses'
            ]);

            $pembelian_bahan = PembelianBahan::create([
                'kode_transaksi' => $kode_transaksi,
                'pengajuan_id' => $pengajuan->id,
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

            // Group items by bahan_id dan simpan
            foreach ($cartItems as $item) {
                PembelianBahanDetails::create([
                    'pembelian_bahan_id' => $pembelian_bahan->id,
                    'bahan_id' => $item['id'],
                    'qty' => $item['qty'],
                    'jml_bahan' => $item['jml_bahan'],
                    'used_materials' => 0,
                    'details' => json_encode($item['details']),
                    'sub_total' => $item['sub_total'],
                    'spesifikasi' => $item['spesifikasi'],
                    'penanggungjawabaset' => $item['penanggungjawabaset'],
                    'alasan' => $item['alasan'],
                ]);
            }

            // Kirim notifikasi jika nomor telepon valid
            if ($targetPhone) {
                $message = "Halo {$recipientName},\n\n";
                $message .= "Pengajuan pembelian bahan dengan kode transaksi $kode_transaksi memerlukan persetujuan Anda.\n\n";
                $message .= "Tgl Pengajuan: " . $tgl_pengajuan . "\nPengaju: {$user->name}\nDivisi: {$request->divisi}\nProject: {$request->project}\nKeterangan: {$request->keterangan}\n\n";
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
                        LogHelper::success("WhatsApp notification sent to: {$targetPhone}");
                    } else {
                        LogHelper::error("Failed to send WhatsApp notification to: {$targetPhone}");
                    }
                } catch (\Exception $e) {
					LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
				}
            } else {
                LogHelper::error('No valid phone number found for WhatsApp notification.');
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
        $bahanPengajuan = Bahan::whereHas('jenisBahan', function ($query) {
            $query->where('nama', 'like', '%Produksi%');
        })->get();

        $pengajuan = Pengajuan::with(['pengajuanDetails.dataBahan', 'bahanKeluar'])->findOrFail($id);

        // Ambil bahan yang ada di pengajuanDetails
        $existingBahanIds = $pengajuan->pengajuanDetails->pluck('dataBahan.id')->toArray();

        $isComplete = true;
        if ($pengajuan->pengajuanDetails && count($pengajuan->pengajuanDetails) > 0) {
            foreach ($pengajuan->pengajuanDetails as $detail) {
                $kebutuhan = $detail->jml_bahan - $detail->used_materials;
                if ($kebutuhan > 0) {
                    $isComplete = false;
                    break;
                }
            }
        } else {
            $isComplete = false;
        }

        return view('pages.pengajuan.edit', [
            'pengajuanId' => $id,
            'bahanPengajuan' => $bahanPengajuan,
            'pengajuan' => $pengajuan,
            'units' => $units,
            'existingBahanIds' => $existingBahanIds,
            'isComplete' => $isComplete,
        ]);
    }



    public function update(Request $request, $id)
    {
        try {
            //dd($request->all());
            $pengajuanDetails = json_decode($request->pengajuanDetails, true) ?? [];
            $bahanRusak = json_decode($request->bahanRusak, true) ?? [];
            $bahanRetur = json_decode($request->bahanRetur, true) ?? [];
            $pengajuan = Pengajuan::findOrFail($id);

            $tujuan = $pengajuan->project;
            $user = Auth::user();

            $purchasingUser = User::whereHas('dataJobPosition', function ($query) {
                $query->where('nama', 'Purchasing');
            })->where('job_level', 3)->first();

            $lastTransaction = BahanKeluar::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
            if ($lastTransaction) {
                $last_transaction_number = intval(substr($lastTransaction->kode_transaksi, 6));
            } else {
                $last_transaction_number = 0;
            }
            $new_transaction_number = $last_transaction_number + 1;
            $formatted_number = str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT);
            $kode_transaksi = 'KBK - ' . $formatted_number;
            $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

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

            // Kelompokkan item berdasarkan bahan_id dan jumlah
            $groupedItems = [];
            $totalQty = 0;  // Variabel untuk menghitung total qty

            foreach ($pengajuanDetails as $item) {
                if (!isset($groupedItems[$item['id']])) {
                    $groupedItems[$item['id']] = [
                        'qty' => 0,
                        'jml_bahan' => 0,
                        'details' => $item['details'],
                        'sub_total' => 0,
                    ];
                }
                $groupedItems[$item['id']]['qty'] += $item['qty'];
                $groupedItems[$item['id']]['jml_bahan'] += $item['jml_bahan'];
                $groupedItems[$item['id']]['sub_total'] += $item['sub_total'];
                $totalQty += $item['qty'];  // Tambahkan qty item ke total qty
            }

            if ($totalQty !== 0) {
                // Simpan data ke Bahan Keluar
                $bahan_keluar = new BahanKeluar();
                $bahan_keluar->kode_transaksi = $kode_transaksi;
                $bahan_keluar->pengajuan_id = $pengajuan->id;
                $bahan_keluar->tgl_pengajuan = $tgl_pengajuan;
                $bahan_keluar->tujuan = $tujuan;
                $bahan_keluar->keterangan = $pengajuan->keterangan;
                $bahan_keluar->status_pengambilan = 'Belum Diambil';
                $bahan_keluar->divisi = $pengajuan->divisi;
                $bahan_keluar->pengaju = $user->id;
                $bahan_keluar->status_pengambilan = 'Belum Diambil';
                $bahan_keluar->status = 'Belum disetujui';
                $bahan_keluar->status_leader = $status_leader;
                $bahan_keluar->status_manager = $status_manager;
                $bahan_keluar->save();

                // Simpan data ke Bahan Keluar Detail dan Produksi Detail
                foreach ($groupedItems as $bahan_id => $details) {
                    BahanKeluarDetails::create([
                        'bahan_keluar_id' => $bahan_keluar->id,
                        'bahan_id' => $bahan_id,
                        'qty' => $details['qty'],
                        'jml_bahan' => $details['jml_bahan'],
                        'used_materials' => 0,
                        'details' => json_encode($details['details']),
                        'sub_total' => $details['sub_total'],
                    ]);
                }

                // Kirim notifikasi jika nomor telepon valid
                if ($targetPhone) {
                    $message = "Halo {$recipientName},\n\n";
                    $message .= "Pengajuan bahan keluar dengan kode transaksi $kode_transaksi memerlukan persetujuan Anda.\n\n";
                    $message .= "Tgl Pengajuan: " . $tgl_pengajuan . "\nPengaju: {$user->name}\nDivisi: {$pengajuan->divisi}\nProject: {$pengajuan->project}\nKeterangan: {$pengajuan->keterangan}\n\n";
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
                            LogHelper::success("WhatsApp notification sent to: {$targetPhone}");
                        } else {
                            LogHelper::error("Failed to send WhatsApp notification to: {$targetPhone}");
                        }
                    } catch (\Exception $e) {
                        LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
                    }
                } else {
                    LogHelper::error('No valid phone number found for WhatsApp notification.');
                }
            }

            // Save bahan rusak if available
            if (!empty($bahanRusak)) {
                $lastTransaction = BahanRusak::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
                if ($lastTransaction) {
                    $last_transaction_number = intval(substr($lastTransaction->kode_transaksi, 6));
                } else {
                    $last_transaction_number = 0;
                }
                $new_transaction_number = $last_transaction_number + 1;
                $formatted_number = str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT);
                $kode_transaksi = 'BR - ' . $formatted_number;

                $bahanRusakRecord = BahanRusak::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                    'pengajuan_id' => $pengajuan->id,
                    'status' => 'Belum disetujui',
                ]);

                foreach ($bahanRusak as $item) {
                    $bahan_id = $item['id'];
                    $qtyRusak = $item['qty'] ?? 0;
                    $unit_price = $item['unit_price'] ?? 0;
                    $sub_total = $qtyRusak * $unit_price;

                    BahanRusakDetails::create([
                        'bahan_rusak_id' => $bahanRusakRecord->id,
                        'bahan_id' => $bahan_id,
                        'qty' => $qtyRusak,
                        'unit_price' => $unit_price,
                        'sub_total' => $sub_total,
                    ]);
                }
                $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                $message = "Tanggal *" . $tgl_pengajuan . "* \n\n";
                $message .= "Kode Transaksi: $kode_transaksi\n";
                $message .= "Pengajuan bahan rusak telah ditambahkan dan memerlukan persetujuan.\n\n";
                $message .= "\nPesan Otomatis:\n";
                $message .= "https://inventory.beacontelemetry.com/";

                try{
                    $response = Http::withHeaders([
                        'x-api-key' => env('WHATSAPP_API_KEY'),
                        'Content-Type' => 'application/json',
                    ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
                        'chatId' => '6281127006443@c.us',
                        'contentType' => 'string',
                        'content' => $message,
                    ]);
                    if ($response->successful()) {
                        LogHelper::success('WhatsApp message sent for approval!');
                    } else {
                        LogHelper::error('Failed to send WhatsApp message.');
                    }
                } catch (\Exception $e) {
                    LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
                }
            }

            if (!empty($bahanRetur)) {
                $lastTransaction = BahanRetur::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
                if ($lastTransaction) {
                    $last_transaction_number = intval(substr($lastTransaction->kode_transaksi, 6));
                } else {
                    $last_transaction_number = 0;
                }
                $new_transaction_number = $last_transaction_number + 1;
                $formatted_number = str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT);
                $kode_transaksi = 'BR - ' . $formatted_number;

                $bahanReturRecord = BahanRetur::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                    'pengajuan_id' => $pengajuan->id,
                    'tujuan' => $tujuan,
                    'divisi' => $pengajuan->divisi,
                    'status' => 'Belum disetujui',
                ]);

                foreach ($bahanRetur as $item) {
                    $bahan_id = $item['id'];
                    $qtyRetur = $item['qty'] ?? 0;
                    $unit_price = $item['unit_price'] ?? 0;
                    $sub_total = $qtyRetur * $unit_price;

                    BahanReturDetails::create([
                        'bahan_retur_id' => $bahanReturRecord->id,
                        'bahan_id' => $bahan_id,
                        'qty' => $qtyRetur,
                        'unit_price' => $unit_price,
                        'sub_total' => $sub_total,
                    ]);
                }
                $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                $message = "Tanggal *" . $tgl_pengajuan . "* \n\n";
                $message .= "Kode Transaksi: $kode_transaksi\n";
                $message .= "Pengajuan bahan retur telah ditambahkan dan memerlukan persetujuan.\n\n";
                $message .= "\nPesan Otomatis:\n";
                $message .= "https://inventory.beacontelemetry.com/";

                try{
                    $response = Http::withHeaders([
                        'x-api-key' => env('WHATSAPP_API_KEY'),
                        'Content-Type' => 'application/json',
                    ])->post('http://103.82.241.100:3000/client/sendMessage/beacon', [
                        'chatId' => '6281127006443@c.us',
                        'contentType' => 'string',
                        'content' => $message,
                    ]);
                    if ($response->successful()) {
                        LogHelper::success('WhatsApp message sent for approval!');
                    } else {
                        LogHelper::error('Failed to send WhatsApp message.');
                    }
                } catch (\Exception $e) {
                    LogHelper::error('Error sending WhatsApp message: ' . $e->getMessage());
                }
            }

            LogHelper::success('Berhasil Mengubah Detail Pengajuan!');
            return redirect()->back()->with('success', 'Pengajuan berhasil diperbarui!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try{
            $pengajuan = Pengajuan::findOrFail($id);
            //dd($pengajuan);
            $pengajuan->status = 'Selesai';
            $pengajuan->selesai_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $pengajuan->save();
            LogHelper::success('Berhasil menyelesaikan pengajuan!');
            return redirect()->back()->with('error', 'Pengajuan tidak bisa diupdate ke selesai.');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy(string $id)
    {
        try{
            $pengajuan = Pengajuan::find($id);
            //dd($pengajuan);
            if (!$pengajuan) {
                return redirect()->back()->with('gagal', 'Pengajuan tidak ditemukan.');
            }
            $pengajuan->delete();
            LogHelper::success('Pengajuan pembelian bahan berhasil dihapus!');
            return redirect()->back()->with('success', 'Pengajuan pembelian bahan berhasil dihapus.');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

}
