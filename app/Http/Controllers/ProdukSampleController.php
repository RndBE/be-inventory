<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Models\User;
use App\Models\Bahan;
use App\Models\Produk;
use App\Models\Projek;
use App\Models\Kontrak;
use App\Models\BahanJadi;
use App\Helpers\LogHelper;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use App\Models\ProdukSample;
use Illuminate\Http\Request;
use App\Exports\ProjekExport;
use App\Models\ProjekDetails;
use App\Models\DetailProduksi;
use App\Models\ProdukProduksi;
use App\Models\PurchaseDetail;
use App\Models\ProduksiDetails;
use App\Models\BahanJadiDetails;
use App\Models\BahanReturDetails;
use App\Models\BahanRusakDetails;
use App\Models\BahanSetengahjadi;
use App\Models\BahanKeluarDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use App\Jobs\SendWhatsAppNotification;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Support\Facades\Validator;

class ProdukSampleController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-produk-sample', ['only' => ['index']]);
        $this->middleware('permission:selesai-produk-sample', ['only' => ['updateStatus']]);
        $this->middleware('permission:tambah-produk-sample', ['only' => ['create','store']]);
        $this->middleware('permission:edit-produk-sample', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-produk-sample', ['only' => ['destroy']]);
    }

    public function export($produkSample_id)
    {
        $produkSample = Projek::findOrFail($produkSample_id);
        $fileName = 'HPP_Project_' . $produkSample->nama_projek . '_be-inventory.xlsx';
        return Excel::download(new ProjekExport($produkSample_id), $fileName);
    }

    public function index()
    {
        return view('pages.produk-sample.index');
    }

    public function create()
    {
        $units = Unit::all();
        $produkProduksi = ProdukProduksi::all();

        return view('pages.produk-sample.create', compact('units', 'produkProduksi'));
    }

    public function store(Request $request)
    {
        try {
            // dd($request->all());
            $cartItems = json_decode($request->cartItems, true);
            $validator = Validator::make([
                'nama_produk_sample' => $request->nama_produk_sample,
                'mulai_produk_sample' => $request->mulai_produk_sample,
                'keterangan' => $request->keterangan,
                'cartItems' => $cartItems
            ], [
                'nama_produk_sample' => 'required',
                'mulai_produk_sample' => 'required',
                'keterangan' => 'required',
                'cartItems' => 'required|array',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $tujuan = $request->nama_produk_sample;
            $user = Auth::user();

            $purchasingUser = User::whereHas('dataJobPosition', function ($query) {
                $query->where('nama', 'Purchasing');
            })->where('job_level', 3)->first();


            // Create transaction code for BahanKeluar
            $lastTransaction = BahanKeluar::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number = ($lastTransaction ? intval(substr($lastTransaction->kode_transaksi, 6)) : 0) + 1;
            $kode_transaksi = 'KBK - ' . str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT). ' PRS';
            $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            // Create transaction code for Produk Sample
            $lastTransactionProdukSample = ProdukSample::orderByRaw('CAST(SUBSTRING(kode_produk_sample, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number_produk = ($lastTransactionProdukSample ? intval(substr($lastTransactionProdukSample->kode_produk_sample, 6)) : 0) + 1;
            $kode_produk_sample = 'PRS - ' . str_pad($new_transaction_number_produk, 5, '0', STR_PAD_LEFT);

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

            $produkSample = ProdukSample::create([
                'kode_produk_sample' => $kode_produk_sample,
                'nama_produk_sample' => $request->nama_produk_sample,
                'pengaju' => $user->name,
                'keterangan' => $request->keterangan,
                'mulai_produk_sample' => $request->mulai_produk_sample,
                'status' => 'Dalam Proses'
            ]);

            $bahan_keluar = BahanKeluar::create([
                'kode_transaksi' => $kode_transaksi,
                'produk_sample_id' => $produkSample->id,
                'tgl_pengajuan' => $tgl_pengajuan,
                'tujuan' => $tujuan,
                'keterangan' => $request->keterangan,
                'divisi' => $user->dataJobPosition->nama,
                'pengaju' => $user->id,
                'status_pengambilan' => 'Belum Diambil',
                'status' => 'Belum disetujui',
                'status_leader' => $status_leader,
            ]);

            // Group items by bahan_id
            // Group items by bahan_id and serial_number
            $groupedItems = [];
            foreach ($cartItems as $item) {
                $bahan_id = $item['bahan_id'] ?? null;
                $produk_id = $item['produk_id'] ?? null;
                $serial_number = $item['serial_number'] ?? null;

                $final_bahan_id = $bahan_id ?? $produk_id;
                // Gunakan kunci unik berdasarkan bahan_id dan serial_number
                $key = $final_bahan_id . ($serial_number ?? '');

                if (!isset($groupedItems[$key])) {
                    $groupedItems[$key] = [
                        'bahan_id' => $bahan_id,
                        'produk_id' => $produk_id,
                        'serial_number' => $serial_number,
                        'qty' => 0,
                        'jml_bahan' => 0,
                        'details' => $item['details'] ?? [],
                        'sub_total' => 0,
                    ];
                }

                $groupedItems[$key]['qty'] += $item['qty'] ?? 0;
                $groupedItems[$key]['jml_bahan'] += $item['jml_bahan'] ?? 0;
                $groupedItems[$key]['sub_total'] += $item['sub_total'] ?? 0;
            }

            // Save items to BahanKeluarDetails and ProjekDetails
            foreach ($groupedItems as $details) {
                BahanKeluarDetails::create([
                    'bahan_keluar_id' => $bahan_keluar->id,
                    'bahan_id' => $details['bahan_id'],
                    'produk_id' => $details['produk_id'],
                    'serial_number' => $details['serial_number'],
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
                $message .= "Tgl Pengajuan: " . $tgl_pengajuan . "\nPengaju: {$user->name}\nDivisi: Teknisi\nProject: {$tujuan}\nKeterangan: {$request->keterangan}\n\n";
                $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";

                SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
            } else {
                LogHelper::error('No valid phone number found for WhatsApp notification.');
            }

            $request->session()->forget('cartItems');
            LogHelper::success('Berhasil Menambahkan Pengajuan Produk Sample!');
            return redirect()->back()->with('success', 'Berhasil Menambahkan Pengajuan Produk Sample!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menambahkan data: ' . $e->getMessage());
        }
    }

    public function edit(string $id)
    {
        $units = Unit::all();
        $bahanProdukSample = Bahan::whereHas('jenisBahan', function ($query) {
            $query->where('nama', 'like', '%Produksi%');
        })->get();

        $produkSample = ProdukSample::with([
            'produkSampleDetails.dataBahan',
            'produkSampleDetails.dataProduk', // Tambahkan ini untuk memuat produk
            'bahanKeluar'
        ])->findOrFail($id);
        // dd($produkSample->produkSampleDetails);


        // Ambil bahan yang ada di produkSampleDetails
        $existingBahanIds = $produkSample->produkSampleDetails->pluck('dataBahan.id')->toArray();

        $isComplete = true;
        if ($produkSample->produkSampleDetails && count($produkSample->produkSampleDetails) > 0) {
            foreach ($produkSample->produkSampleDetails as $detail) {
                $kebutuhan = $detail->jml_bahan - $detail->used_materials;
                if ($kebutuhan > 0) {
                    $isComplete = false;
                    break;
                }
            }
        } else {
            $isComplete = false;
        }

        return view('pages.produk-sample.edit', [
            'produkSampleId' => $id,
            'bahanProdukSample' => $bahanProdukSample,
            'produkSample' => $produkSample,
            'units' => $units,
            'existingBahanIds' => $existingBahanIds,
            'isComplete' => $isComplete,
        ]);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $validatedData = $request->validate([
            'keterangan' => 'required|string|max:255', // Validasi keterangan
        ]);
        try {
            // dd($request->all());
            $produkSampleDetails = json_decode($request->produkSampleDetails, true) ?? [];
            $bahanRusak = json_decode($request->bahanRusak, true) ?? [];
            $bahanRetur = json_decode($request->bahanRetur, true) ?? [];
            $produkSample = ProdukSample::findOrFail($id);

            $produkSample->update([
                'keterangan' => $validatedData['keterangan'],
            ]);

            $tujuan = $produkSample->nama_produk_sample;
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
            $kode_transaksi = 'KBK - ' . $formatted_number. ' PRS';
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

            foreach ($produkSampleDetails as $item) {
                $bahan_id = $item['bahan_id'] ?? null;
                $produk_id = $item['produk_id'] ?? null;
                $serial_number = $item['serial_number'] ?? null;

                $final_bahan_id = $bahan_id ?? $produk_id;
                // Gunakan kunci unik berdasarkan bahan_id dan serial_number
                $key = $final_bahan_id . ($serial_number ?? '');

                if (!isset($groupedItems[$key])) {
                    $groupedItems[$key] = [
                        'bahan_id' => $bahan_id,
                        'produk_id' => $produk_id,
                        'serial_number' => $serial_number,
                        'qty' => 0,
                        'jml_bahan' => 0,
                        'details' => $item['details'],
                        'sub_total' => 0,
                    ];
                }

                $groupedItems[$key]['qty'] += (int) $item['qty'];
                $groupedItems[$key]['jml_bahan'] += (int) $item['jml_bahan'];
                $groupedItems[$key]['sub_total'] += (float) $item['sub_total'];
                $totalQty += (int) $item['qty']; // Tambahkan qty item ke total qty
            }

            if ($totalQty !== 0) {

                $bahan_keluar = BahanKeluar::create([
                    'kode_transaksi' => $kode_transaksi,
                    'produk_sample_id' => $produkSample->id,
                    'tgl_pengajuan' => $tgl_pengajuan,
                    'tujuan' => $tujuan,
                    'keterangan' => $produkSample->keterangan,
                    'divisi' => 'Teknisi',
                    'pengaju' => $user->id,
                    'status_pengambilan' => 'Belum Diambil',
                    'status' => 'Belum disetujui',
                    'status_leader' => $status_leader,
                ]);

                // Simpan data ke Bahan Keluar Detail dan Produksi Detail
                foreach ($groupedItems as $bahan_id => $details) {
                    BahanKeluarDetails::create([
                        'bahan_keluar_id' => $bahan_keluar->id,
                        'bahan_id' => $details['bahan_id'],
                        'produk_id' => $details['produk_id'],
                        'serial_number' => $details['serial_number'],
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
                    $message .= "Tgl Pengajuan: " . $tgl_pengajuan . "\nPengaju: {$user->name}\nDivisi: Teknisi\nProject: {$tujuan}\nKeterangan: {$produkSample->keterangan}\n\n";
                    $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";

                    SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
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
                $kode_transaksi = 'BRS - ' . $formatted_number. ' PRS';

                $bahanRusakRecord = BahanRusak::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                    'produk_sample_id' => $produkSample->id,
                    'status' => 'Belum disetujui',
                ]);

                foreach ($bahanRusak as $item) {
                    $bahan_id = $item['bahan_id'] ?? null; // Ambil bahan_id jika ada
                    $produk_id = $item['produk_id'] ?? null; // Ambil produk_id jika ada
                    $serial_number = $item['serial_number'] ?? null; // Ambil serial number jika ada
                    $qtyRusak = $item['qty'] ?? 0;
                    $unit_price = $item['unit_price'] ?? 0;
                    $sub_total = $qtyRusak * $unit_price;

                    BahanRusakDetails::create([
                        'bahan_rusak_id' => $bahanRusakRecord->id,
                        'bahan_id' => $bahan_id, // Bisa null jika produk
                        'produk_id' => $produk_id, // Bisa null jika bahan
                        'serial_number' => $serial_number, // Tambahkan serial number untuk produk
                        'qty' => $qtyRusak,
                        'unit_price' => $unit_price,
                        'sub_total' => $sub_total,
                    ]);
                }
                $targetPhone = $purchasingUser->telephone;
                $recipientName = $purchasingUser->name;
                $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                if ($targetPhone) {
                    $message = "Halo {$purchasingUser->name},\n\n";
                    $message .= "Tanggal *" . $tgl_pengajuan . "* \n\n";
                    $message .= "Kode Transaksi: $kode_transaksi\n";
                    $message .= "Pengajuan bahan rusak telah ditambahkan dan memerlukan persetujuan.\n\n";
                    $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";

                    SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
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
                $kode_transaksi = 'BRT - ' . $formatted_number. ' PRS';

                $bahanReturRecord = BahanRetur::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                    'produk_sample_id' => $produkSample->id,
                    'tujuan' => 'Projek ' . $tujuan,
                    'divisi' => 'Teknisi',
                    'status' => 'Belum disetujui',
                ]);

                foreach ($bahanRetur as $item) {
                    $bahan_id = $item['bahan_id'] ?? null; // Ambil bahan_id jika ada
                    $produk_id = $item['produk_id'] ?? null; // Ambil produk_id jika ada
                    $serial_number = $item['serial_number'] ?? null; // Ambil serial number jika ada
                    $qtyRetur = $item['qty'] ?? 0;
                    $unit_price = $item['unit_price'] ?? 0;
                    $sub_total = $qtyRetur * $unit_price;

                    BahanReturDetails::create([
                        'bahan_retur_id' => $bahanReturRecord->id,
                        'bahan_id' => $bahan_id, // Bisa null jika produk
                        'produk_id' => $produk_id, // Bisa null jika bahan
                        'serial_number' => $serial_number, // Tambahkan serial number untuk produk
                        'qty' => $qtyRetur,
                        'unit_price' => $unit_price,
                        'sub_total' => $sub_total,
                    ]);
                }
                $targetPhone = $purchasingUser->telephone;
                $recipientName = $purchasingUser->name;
                $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                if ($targetPhone) {
                    $message = "Halo {$purchasingUser->name},\n\n";
                    $message .= "Tanggal *" . $tgl_pengajuan . "* \n\n";
                    $message .= "Kode Transaksi: $kode_transaksi\n";
                    $message .= "Pengajuan bahan retur telah ditambahkan dan memerlukan persetujuan.\n\n";
                    $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";

                    SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
                } else {
                    LogHelper::error('No valid phone number found for WhatsApp notification.');
                }
            }

            LogHelper::success('Berhasil Mengubah Detail Produk Sample!');
            return redirect()->back()->with('success', 'Produk sample berhasil diperbarui!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try{
            $produkSample = ProdukSample::findOrFail($id);
            //dd($produkSample);
            $produkSample->status = 'Selesai';
            $produkSample->selesai_produk_sample = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $produkSample->save();
            LogHelper::success('Berhasil menyelesaikan produk sample!');
            return redirect()->back()->with('error', 'Produk sample tidak bisa diupdate ke selesai.');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy(string $id)
    {
        try{
            $produkSample = ProdukSample::find($id);
            if (!$produkSample) {
                return redirect()->back()->with('gagal', 'Produk sample tidak ditemukan.');
            }
            $produkSample->delete();
            LogHelper::success('Produk sample berhasil dihapus!');
            return redirect()->back()->with('success', 'Produk sample berhasil dihapus!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

}
