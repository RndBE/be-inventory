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
use App\Models\BahanSetengahjadiDetails;
use App\Models\PengambilanBahan;
use Illuminate\Support\Facades\Validator;

class PengambilanBahanController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-pengambilan', ['only' => ['index']]);
        $this->middleware('permission:selesai-pengambilan', ['only' => ['updateStatus']]);
        $this->middleware('permission:tambah-pengambilan', ['only' => ['create','store']]);
        $this->middleware('permission:edit-pengambilan-bahan', ['only' => ['edit','update']]);
        $this->middleware('permission:hapus-pengambilan', ['only' => ['destroy']]);
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
        return view('pages.pengambilan.index');
    }

    public function create()
    {
        $units = Unit::all();
        $produkProduksi = ProdukProduksi::all();
        return view('pages.pengambilan.create', compact('units', 'produkProduksi'));
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
                'cartItems' => $cartItems
            ], [
                'divisi' => 'required',
                'project' => 'required',
                'keterangan' => 'required',
                'cartItems' => 'required|array',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $tujuan = $request->project;
            $user = Auth::user();

            $purchasingUser = User::whereHas('dataJobPosition', function ($query) {
                $query->where('nama', 'Purchasing');
            })->where('job_level', 3)->first();

            $lastTransaction = BahanKeluar::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number = ($lastTransaction ? intval(substr($lastTransaction->kode_transaksi, 6)) : 0) + 1;
            $kode_transaksi = 'KBK - ' . str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT). ' PB';
            $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            $lastTransactionProjek = PengambilanBahan::orderByRaw('CAST(SUBSTRING(kode_pengajuan, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number_produksi = ($lastTransactionProjek ? intval(substr($lastTransactionProjek->kode_pengajuan, 6)) : 0) + 1;
            $kode_pengajuan = 'PB - ' . str_pad($new_transaction_number_produksi, 5, '0', STR_PAD_LEFT);

            if ($user->job_level == 3 && $user->atasan_level3_id === null) {
                // Job level 3 dan atasan_level3_id null
                $status_leader = 'Disetujui';
                // Kirim notifikasi ke Purchasing
                $targetPhone = $purchasingUser ? $purchasingUser->telephone : null;
                $recipientName = $purchasingUser ? $purchasingUser->name : 'Purchasing';
            } elseif ($user->job_level == 4 && $user->atasan_level3_id === null && $user->atasan_level2_id === null) {
                // Job level 4 dan atasan_level3_id, atasan_level2_id null
                $status_leader = 'Disetujui';
                // Kirim notifikasi ke Purchasing
                $targetPhone = $purchasingUser ? $purchasingUser->telephone : null;
                $recipientName = $purchasingUser ? $purchasingUser->name : 'Purchasing';
            } elseif ($user->job_level == 4 && $user->atasan_level3_id === null) {
                // Job level 4 dan atasan_level3_id null
                $status_leader = 'Belum disetujui';
                // Kirim notifikasi ke atasan level 2
                $targetPhone = $user->atasanLevel2 ? $user->atasanLevel2->telephone : null;
                $recipientName = $user->atasanLevel2 ? $user->atasanLevel2->name : 'Manager';
            } elseif ($user->job_level == 4) {
                // Job level 4 dan atasan_level3_id tidak null
                $status_leader = 'Belum disetujui';
                // Kirim notifikasi ke atasan level 3
                $targetPhone = $user->atasanLevel3 ? $user->atasanLevel3->telephone : null;
                $recipientName = $user->atasanLevel3 ? $user->atasanLevel3->name : 'Leader';
            } else {
                // Job level lainnya, kirim ke Purchasing
                $status_leader = 'Belum disetujui';
                $targetPhone = $purchasingUser ? $purchasingUser->telephone : null;
                $recipientName = $purchasingUser ? $purchasingUser->name : 'Purchasing';
            }

            // Simpan data pengajuan
            $pengambilan_bahan = PengambilanBahan::create([
                'kode_pengajuan' => $kode_pengajuan,
                'mulai_pengajuan' => $tgl_pengajuan,
                'divisi' => $request->divisi,
                'pengaju' => $user->name,
                'project' => $request->project,
                'keterangan' => $request->keterangan,
                'status' => 'Dalam Proses'
            ]);

            $bahan_keluar = BahanKeluar::create([
                'kode_transaksi' => $kode_transaksi,
                'pengambilan_bahan_id' => $pengambilan_bahan->id,
                'tgl_pengajuan' => $tgl_pengajuan,
                'tujuan' => $tujuan,
                'keterangan' => $request->keterangan,
                'divisi' => $request->divisi,
                'pengaju' => $user->id,
                'status_pengambilan' => 'Belum Diambil',
                'status' => 'Belum disetujui',
                'status_leader' => $status_leader,
            ]);

            // Group items by bahan_id dan simpan
            foreach ($cartItems as $item) {
                BahanKeluarDetails::create([
                    'bahan_keluar_id' => $bahan_keluar->id,
                    'bahan_id' => $item['id'],
                    'qty' => $item['qty'],
                    'jml_bahan' => $item['jml_bahan'],
                    'used_materials' => 0,
                    'details' => json_encode($item['details']),
                    'sub_total' => $item['sub_total'],
                ]);
            }

            // Kirim notifikasi jika nomor telepon valid
            if ($targetPhone) {
                $message = "Halo {$recipientName},\n\n";
                $message .= "Pengajuan bahan keluar dengan kode transaksi $kode_transaksi memerlukan persetujuan Anda.\n\n";
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
            LogHelper::success('Berhasil Menambahkan Pengajuan Pengambilan Bahan!');
            return redirect()->back()->with('success', 'Berhasil Menambahkan Pengajuan Pengambilan Bahan!');
        } catch (\Exception $e) {
            DB::rollBack();
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menambahkan data: ' . $e->getMessage());
        }
    }

    public function edit(string $id)
    {
        $units = Unit::all();
        $bahanPengambilanBahan = Bahan::whereHas('jenisBahan', function ($query) {
            $query->where('nama', 'like', '%Produksi%');
        })->get();

        $pengambilanBahan = PengambilanBahan::with(['pengambilanBahanDetails.dataBahan', 'bahanKeluar'])->findOrFail($id);

        // Ambil bahan yang ada di pengambilanBahanDetails
        $existingBahanIds = $pengambilanBahan->pengambilanBahanDetails->pluck('dataBahan.id')->toArray();

        $isComplete = true;
        if ($pengambilanBahan->pengambilanBahanDetails && count($pengambilanBahan->pengambilanBahanDetails) > 0) {
            foreach ($pengambilanBahan->pengambilanBahanDetails as $detail) {
                $kebutuhan = $detail->jml_bahan - $detail->used_materials;
                if ($kebutuhan > 0) {
                    $isComplete = false;
                    break;
                }
            }
        } else {
            $isComplete = false;
        }

        return view('pages.pengambilan.edit', [
            'pengambilanBahanId' => $id,
            'bahanPengambilanBahan' => $bahanPengambilanBahan,
            'pengambilanBahan' => $pengambilanBahan,
            'units' => $units,
            'existingBahanIds' => $existingBahanIds,
            'isComplete' => $isComplete,
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            // dd($request->all());
            $pengambilanBahanDetails = json_decode($request->pengambilanBahanDetails, true) ?? [];
            $bahanRusak = json_decode($request->bahanRusak, true) ?? [];
            $bahanRetur = json_decode($request->bahanRetur, true) ?? [];
            $pengambilanBahan = PengambilanBahan::findOrFail($id);

            $tujuan = $pengambilanBahan->project;
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
            $kode_transaksi = 'KBK - ' . $formatted_number. ' PB';
            $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            if ($user->job_level == 3 && $user->atasan_level3_id === null) {
                // Job level 3 dan atasan_level3_id null
                $status_leader = 'Disetujui';
                // Kirim notifikasi ke Purchasing
                $targetPhone = $purchasingUser ? $purchasingUser->telephone : null;
                $recipientName = $purchasingUser ? $purchasingUser->name : 'Purchasing';
            } elseif ($user->job_level == 4 && $user->atasan_level3_id === null && $user->atasan_level2_id === null) {
                // Job level 4 dan atasan_level3_id, atasan_level2_id null
                $status_leader = 'Disetujui';
                // Kirim notifikasi ke Purchasing
                $targetPhone = $purchasingUser ? $purchasingUser->telephone : null;
                $recipientName = $purchasingUser ? $purchasingUser->name : 'Purchasing';
            } elseif ($user->job_level == 4 && $user->atasan_level3_id === null) {
                // Job level 4 dan atasan_level3_id null
                $status_leader = 'Belum disetujui';
                // Kirim notifikasi ke atasan level 2
                $targetPhone = $user->atasanLevel2 ? $user->atasanLevel2->telephone : null;
                $recipientName = $user->atasanLevel2 ? $user->atasanLevel2->name : 'Manager';
            } elseif ($user->job_level == 4) {
                // Job level 4 dan atasan_level3_id tidak null
                $status_leader = 'Belum disetujui';
                // Kirim notifikasi ke atasan level 3
                $targetPhone = $user->atasanLevel3 ? $user->atasanLevel3->telephone : null;
                $recipientName = $user->atasanLevel3 ? $user->atasanLevel3->name : 'Leader';
            } else {
                // Job level lainnya, kirim ke Purchasing
                $status_leader = 'Belum disetujui';
                $targetPhone = $purchasingUser ? $purchasingUser->telephone : null;
                $recipientName = $purchasingUser ? $purchasingUser->name : 'Purchasing';
            }

            // Kelompokkan item berdasarkan bahan_id dan jumlah
            $groupedItems = [];
            $totalQty = 0;  // Variabel untuk menghitung total qty

            foreach ($pengambilanBahanDetails as $item) {
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
                $bahan_keluar = BahanKeluar::create([
                    'kode_transaksi' => $kode_transaksi,
                    'pengambilan_bahan_id' => $pengambilanBahan->id,
                    'tgl_pengajuan' => $tgl_pengajuan,
                    'tujuan' => $tujuan,
                    'keterangan' => $pengambilanBahan->keterangan,
                    'divisi' => $pengambilanBahan->divisi,
                    'pengaju' => $user->id,
                    'status_pengambilan' => 'Belum Diambil',
                    'status' => 'Belum disetujui',
                    'status_leader' => $status_leader,
                ]);

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
                    $message .= "Tgl Pengajuan: " . $tgl_pengajuan . "\nPengaju: {$user->name}\nDivisi: {$pengambilanBahan->divisi}\nProject: {$pengambilanBahan->project}\nKeterangan: {$pengambilanBahan->keterangan}\n\n";
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
                $kode_transaksi = 'BRS - ' . $formatted_number. ' PB';

                $bahanRusakRecord = BahanRusak::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                    'pengambilan_bahan_id' => $pengambilanBahan->id,
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
                $targetPhone = $purchasingUser->telephone;
                $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                if ($targetPhone) {
                    $message = "Halo {$purchasingUser->name},\n\n";
                    $message = "Tanggal *" . $tgl_pengajuan . "* \n\n";
                    $message .= "Kode Transaksi: $kode_transaksi\n";
                    $message .= "Pengajuan bahan rusak telah ditambahkan dan memerlukan persetujuan.\n\n";
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
                $kode_transaksi = 'BRT - ' . $formatted_number. ' PB';

                $bahanReturRecord = BahanRetur::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                    'pengambilan_bahan_id' => $pengambilanBahan->id,
                    'tujuan' => $tujuan,
                    'divisi' => $pengambilanBahan->divisi,
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
                $targetPhone = $purchasingUser->telephone;
                $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                if ($targetPhone) {
                    $message = "Halo {$purchasingUser->name},\n\n";
                    $message = "Tanggal *" . $tgl_pengajuan . "* \n\n";
                    $message = "Tanggal *" . $tgl_pengajuan . "* \n\n";
                    $message .= "Kode Transaksi: $kode_transaksi\n";
                    $message .= "Pengajuan bahan retur telah ditambahkan dan memerlukan persetujuan.\n\n";
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
            $pengambilanBahan = PengambilanBahan::findOrFail($id);
            //dd($pengambilanBahan);
            $pengambilanBahan->status = 'Selesai';
            $pengambilanBahan->selesai_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $pengambilanBahan->save();
            LogHelper::success('Berhasil menyelesaikan pengajuan pengambilan bahan!');
            return redirect()->back()->with('error', 'Pengajuan pengambilan bahan tidak bisa diupdate ke selesai.');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy(string $id)
    {
        try{
            $pengambilan_bahan = PengambilanBahan::find($id);
            if (!$pengambilan_bahan) {
                return redirect()->back()->with('gagal', 'Pengambilan bahan tidak ditemukan.');
            }
            $pengambilan_bahan->delete();
            LogHelper::success('Pengambilan Bahan berhasil dihapus!');
            return redirect()->back()->with('success', 'Pengambilan Bahan berhasil dihapus.');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

}
