<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Models\User;
use App\Models\Bahan;
use App\Helpers\LogHelper;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\ProdukJadi;
use App\Models\BahanKeluar;
use Illuminate\Http\Request;
use App\Models\BahanReturDetails;
use App\Models\BahanRusakDetails;
use App\Models\BahanKeluarDetails;
use App\Models\ProduksiProdukJadi;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendWhatsAppNotification;
use Illuminate\Support\Facades\Validator;

class ProduksiProdukJadiController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-proses-produksi', ['only' => ['index']]);
        $this->middleware('permission:selesai-proses-produksi', ['only' => ['updateStatus']]);
        $this->middleware('permission:tambah-proses-produksi', ['only' => ['create','store']]);
        $this->middleware('permission:edit-proses-produksi', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-proses-produksi', ['only' => ['destroy']]);
    }

    public function info($id)
    {
        $produksi = ProduksiProdukJadi::with([
            'dataBahan',
            'bahanKeluar',
            'bahanKeluar.dataUser',
            'bahanKeluar.bahanKeluarDetails' => function ($query) {
                $query->where('qty', '>', 0)
                    ->with(['dataBahan', 'dataProduk', 'purchase']);
            },
            'dataBahanRetur.bahanReturDetails' => function ($query) {
                $query->where('qty', '>', 0)
                    ->with(['dataBahan', 'dataProduk']);
            },
            'dataBahanRusak.bahanRusakDetails' => function ($query) {
                $query->where('qty', '>', 0)
                    ->with(['dataBahan', 'dataProduk']);
            }
        ])->findOrFail($id);
        // dd($produksi);

        return view('pages.produksi-produk-jadi.info', compact('produksi'));
    }


    public function index()
    {
        return view('pages.produksi-produk-jadi.index');
    }

    public function create()
    {
        $units = Unit::all();
        $produkJadi = ProdukJadi::all();

        return view('pages.produksi-produk-jadi.create', compact('units', 'produkJadi'));
    }

    public function store(Request $request)
    {
        try {
            // dd($request->all());
            $cartItems = json_decode($request->cartItems, true);
            $validator = Validator::make([
                'produk_jadi_id' => $request->produk_jadi_id,
                'mulai_produksi' => $request->mulai_produksi,
                'jml_produksi' => $request->jml_produksi,
                'keterangan' => $request->keterangan,
                'cartItems' => $cartItems
            ], [
                'produk_jadi_id' => 'required',
                'mulai_produksi' => 'required',
                'jml_produksi' => 'required',
                'keterangan' => 'required',
                'cartItems' => 'required|array',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $produkJadis = ProdukJadi::find($request->produk_jadi_id);
            if (!$produkJadis) {
                return redirect()->back()->with('error', 'Produk jadi tidak ditemukan!')->withInput();
            }
            $tujuan = $produkJadis->nama_produk;
            $user = Auth::user();

            $purchasingUser = User::whereHas('dataJobPosition', function ($query) {
                $query->where('nama', 'Purchasing');
            })->where('job_level', 3)->first();

            // Create transaction code for BahanKeluar
            $lastTransaction = BahanKeluar::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number = ($lastTransaction ? intval(substr($lastTransaction->kode_transaksi, 6)) : 0) + 1;
            $kode_transaksi = 'KBK - ' . str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT). ' - '. $tujuan;
            $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            // Create transaction code for Produksi Produk Jadi
            // $lastTransactionProduksiProdukJadi = ProduksiProdukJadi::orderByRaw('CAST(SUBSTRING(kode_produksi, 7) AS UNSIGNED) DESC')->first();
            // $new_transaction_number_produksi = ($lastTransactionProduksiProdukJadi ? intval(substr($lastTransactionProduksiProdukJadi->kode_produksi, 6)) : 0) + 1;
            // $kode_produksi = $tujuan . '- ' . str_pad($new_transaction_number_produksi, 5, '0', STR_PAD_LEFT);
            $lastTransactionProduksiProdukJadi = ProduksiProdukJadi::orderBy('id', 'desc')->first();
            if ($lastTransactionProduksiProdukJadi) {
                // ambil 5 digit terakhir
                $lastNumber = intval(substr($lastTransactionProduksiProdukJadi->kode_produksi, -5));
            } else {
                $lastNumber = 0;
            }
            $newNumber = $lastNumber + 1;
            // kode produk jadi (bisa pakai kode unik, bukan nama)
            $prefix = $produkJadis->kode_produksi ?? $tujuan;
            $kode_produksi = $prefix . '-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);


            // dd($kode_produksi);

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

            $produksi_produk_jadi = ProduksiProdukJadi::create([
                'kode_produksi' => $kode_produksi,
                'produk_jadi_id' => $request->produk_jadi_id,
                'pengaju' => $user->name,
                'jml_produksi' => $request->jml_produksi,
                'keterangan' => $request->keterangan,
                'mulai_produksi' => $request->mulai_produksi,
                'status' => 'Dalam Proses'
            ]);

            $bahan_keluar = BahanKeluar::create([
                'kode_transaksi' => $kode_transaksi,
                'produksi_produk_jadi_id' => $produksi_produk_jadi->id,
                'tgl_pengajuan' => $tgl_pengajuan,
                'tujuan' => $tujuan,
                'keterangan' => $request->keterangan,
                'divisi' => $user->dataJobPosition->nama,
                'pengaju' => $user->id,
                'status_pengambilan' => 'Belum Diambil',
                'status' => 'Belum disetujui',
                'status_leader' => $status_leader,
            ]);

            $groupedItems = [];
            foreach ($cartItems as $item) {
                $bahan_id = $item['bahan_id'] ?? null;
                $produk_id = $item['produk_id'] ?? null;
                $serial_number = $item['serial_number'] ?? null;

                $final_bahan_id = $bahan_id ?? $produk_id;
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
            LogHelper::success('Berhasil Menambahkan Pengajuan Produksi Produk Jadi!');
            return redirect()->back()->with('success', 'Berhasil Menambahkan Pengajuan Produksi Produk Jadi!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menambahkan data: ' . $e->getMessage());
        }
    }

    public function edit(string $id)
    {
        $units = Unit::all();
        $produkJadis = ProdukJadi::all();
        $bahanProduksiProdukJadi = Bahan::whereHas('jenisBahan', function ($query) {
            $query->where('nama', 'like', '%Produksi%');
        })->get();

        $produksiProdukJadis = ProduksiProdukJadi::with([
            'produksiProdukJadiDetails.dataBahan',
            'produksiProdukJadiDetails.dataProduk', // Tambahkan ini untuk memuat produk
            'bahanKeluar'
        ])->findOrFail($id);
        // dd($produksiProdukJadis->produksiProdukJadiDetails);


        // Ambil bahan yang ada di produksiProdukJadiDetails
        $existingBahanIds = $produksiProdukJadis->produksiProdukJadiDetails->pluck('dataBahan.id')->toArray();

        $isComplete = true;
        if ($produksiProdukJadis->produksiProdukJadiDetails && count($produksiProdukJadis->produksiProdukJadiDetails) > 0) {
            foreach ($produksiProdukJadis->produksiProdukJadiDetails as $detail) {
                $kebutuhan = $detail->qty - $detail->used_materials;
                if ($kebutuhan > 0) {
                    $isComplete = false;
                    break;
                }
            }
        } else {
            $isComplete = false;
        }

        return view('pages.produksi-produk-jadi.edit', [
            'produksiProdukJadiId' => $id,
            'bahanProduksiProdukJadi' => $bahanProduksiProdukJadi,
            'produkJadis' => $produkJadis,
            'produksiProdukJadis' => $produksiProdukJadis,
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
            $produksiProdukJadiDetails = json_decode($request->produksiProdukJadiDetails, true) ?? [];
            $bahanRusak = json_decode($request->bahanRusak, true) ?? [];
            $bahanRetur = json_decode($request->bahanRetur, true) ?? [];
            $produksiProdukJadi = ProduksiProdukJadi::findOrFail($id);

            $produksiProdukJadi->update([
                'keterangan' => $validatedData['keterangan'],
            ]);

            $tujuan = $produksiProdukJadi->dataProdukJadi->nama_produk;
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
            $kode_transaksi = 'KBK - ' . $formatted_number. ' - '. $tujuan;
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

            foreach ($produksiProdukJadiDetails as $item) {
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

                $groupedItems[$key]['qty'] += $item['qty'];
                $groupedItems[$key]['jml_bahan'] += $item['jml_bahan'];
                $groupedItems[$key]['sub_total'] += $item['sub_total'];
                $totalQty += $item['qty']; // Tambahkan qty item ke total qty
            }

            if ($totalQty !== 0) {

                $bahan_keluar = BahanKeluar::create([
                    'kode_transaksi' => $kode_transaksi,
                    'produksi_produk_jadi_id' => $produksiProdukJadi->id,
                    'tgl_pengajuan' => $tgl_pengajuan,
                    'tujuan' => $tujuan,
                    'keterangan' => $produksiProdukJadi->keterangan,
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
                    $message .= "Tgl Pengajuan: " . $tgl_pengajuan . "\nPengaju: {$user->name}\nDivisi: Teknisi\nProject: {$tujuan}\nKeterangan: {$produksiProdukJadi->keterangan}\n\n";
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
                $kode_transaksi = 'BRS - ' . $formatted_number. ' - '. $tujuan;

                $bahanRusakRecord = BahanRusak::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                    'produksi_produk_jadi_id' => $produksiProdukJadi->id,
                    'status' => 'Belum disetujui',
                ]);

                foreach ($bahanRusak as $item) {
                    $bahan_id = $item['bahan_id'] ?? null;
                    $produk_id = $item['produk_id'] ?? null;
                    $serial_number = $item['serial_number'] ?? null;
                    $qtyRusak = $item['qty'] ?? 0;
                    $unit_price = $item['unit_price'] ?? 0;
                    $sub_total = $qtyRusak * $unit_price;

                    BahanRusakDetails::create([
                        'bahan_rusak_id' => $bahanRusakRecord->id,
                        'bahan_id' => $bahan_id,
                        'produk_id' => $produk_id,
                        'serial_number' => $serial_number,
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
                $kode_transaksi = 'BRT - ' . $formatted_number. ' - '. $tujuan;

                $bahanReturRecord = BahanRetur::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                    'produksi_produk_jadi_id' => $produksiProdukJadi->id,
                    'tujuan' => 'Produksi ' . $tujuan,
                    'divisi' => 'Teknisi',
                    'status' => 'Belum disetujui',
                ]);

                foreach ($bahanRetur as $item) {
                    $bahan_id = $item['bahan_id'] ?? null;
                    $produk_id = $item['produk_id'] ?? null;
                    $serial_number = $item['serial_number'] ?? null;
                    $qtyRetur = $item['qty'] ?? 0;
                    $unit_price = $item['unit_price'] ?? 0;
                    $sub_total = $qtyRetur * $unit_price;

                    BahanReturDetails::create([
                        'bahan_retur_id' => $bahanReturRecord->id,
                        'bahan_id' => $bahan_id,
                        'produk_id' => $produk_id,
                        'serial_number' => $serial_number,
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

            LogHelper::success('Berhasil Mengubah Detail Produksi produk jadi!');
            return redirect()->back()->with('success', 'Produksi produk jadi berhasil diperbarui!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try{
            $produksiProdukjadi = ProduksiProdukJadi::findOrFail($id);
            //dd($produksiProdukjadi);
            $produksiProdukjadi->status = 'Selesai';
            $produksiProdukjadi->selesai_produksi = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $produksiProdukjadi->save();
            LogHelper::success('Berhasil menyelesaikan produksi!');
            return redirect()->back()->with('error', 'Produksi tidak bisa diupdate ke selesai.');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy(string $id)
    {
        try{
            $produksi_produk_jadi = ProduksiProdukJadi::find($id);
            if (!$produksi_produk_jadi) {
                return redirect()->back()->with('gagal', 'Produksi produk jadi tidak ditemukan.');
            }
            $produksi_produk_jadi->delete();
            LogHelper::success('Produksi produk jadi berhasil dihapus!');
            return redirect()->back()->with('success', 'Produksi produk jadi berhasil dihapus!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
