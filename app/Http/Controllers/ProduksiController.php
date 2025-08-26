<?php

namespace App\Http\Controllers;

use Throwable;
use Carbon\Carbon;
use App\Models\Unit;
use App\Models\User;
use App\Models\Bahan;
use App\Models\Produksi;
use App\Models\BahanJadi;
use App\Helpers\LogHelper;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use Illuminate\Http\Request;
use App\Models\DetailProduksi;
use App\Models\ProdukProduksi;
use App\Models\PurchaseDetail;
use App\Exports\ProduksiExport;
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

class ProduksiController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-proses-produksi', ['only' => ['index']]);
        $this->middleware('permission:selesai-proses-produksi', ['only' => ['updateStatus']]);
        $this->middleware('permission:tambah-proses-produksi', ['only' => ['create','store']]);
        $this->middleware('permission:edit-proses-produksi', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-proses-produksi', ['only' => ['destroy']]);
    }

    public function export($produksi_id)
    {
        $produksi = Produksi::findOrFail($produksi_id);
        $fileName = 'HPP_Produksi_' . $produksi->dataBahan->nama_bahan . '_be-inventory.xlsx';

        return Excel::download(new ProduksiExport($produksi_id), $fileName);
    }

    public function index()
    {
        return view('pages.produksis.index');
    }

    public function create()
    {
        $units = Unit::all();
        $produkProduksi = ProdukProduksi::all();
        return view('pages.produksis.create', compact('units', 'produkProduksi'));
    }

    public function store(Request $request)
    {
        try {
            // dd($request->all());
            $cartItems = json_decode($request->cartItems, true);
            $validator = Validator::make([
                'bahan_id' => $request->bahan_id,
                'jml_produksi' => $request->jml_produksi,
                'mulai_produksi' => $request->mulai_produksi,
                'keterangan' => $request->keterangan,
                // 'jenis_produksi' => $request->jenis_produksi,
                'cartItems' => $cartItems
            ], [
                'bahan_id' => 'required',
                'jml_produksi' => 'required',
                'mulai_produksi' => 'required',
                'keterangan' => 'required',
                // 'jenis_produksi' => 'required',
                'cartItems' => 'required|array',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $produk = Bahan::find($request->bahan_id);
            //dd($produk->nama_bahan);
            if ($produk) {
                $tujuan = $produk->nama_bahan;
            } else {
                $tujuan = null;
            }
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
            $kode_transaksi = 'KBK - ' . $formatted_number. ' Prod';
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

            // Simpan data ke Produksi
            $produksi = new Produksi();
            // Generate kode produksi
            $prefix = "PRD";
            $timestamp = Carbon::now('Asia/Jakarta')->format('YmdHis'); // format dengan jam Jakarta
            // Cari nomor urut terakhir di hari yang sama (Jakarta time)
            $lastProduksi = Produksi::whereDate('created_at', Carbon::now('Asia/Jakarta')->toDateString())
                ->orderBy('id', 'desc')
                ->first();
            if ($lastProduksi) {
                $lastNumber = (int) substr($lastProduksi->kode_produksi, -4);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            $nomorUrut = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $kodeProduksi = $prefix . "-" . $timestamp . "-" . $nomorUrut;

            $produksi->kode_produksi = $kodeProduksi;
            $produksi->bahan_id = $request->bahan_id;
            $produksi->pengaju = $user->name;
            $produksi->keterangan = $request->keterangan;
            $produksi->jml_produksi = $request->jml_produksi;
            $produksi->mulai_produksi = $request->mulai_produksi;
            $produksi->jenis_produksi = 'Produk Setengah Jadi';
            $produksi->status = 'Dalam proses';
            $produksi->save();

            // Simpan data ke Bahan Keluar
            $bahan_keluar = new BahanKeluar();
            $bahan_keluar->kode_transaksi = $kode_transaksi;
            $bahan_keluar->produksi_id = $produksi->id;
            $bahan_keluar->pengaju = $user->id;
            $bahan_keluar->keterangan = $request->keterangan;
            $bahan_keluar->tgl_pengajuan = $tgl_pengajuan;
            $bahan_keluar->tujuan = 'Produksi '.$tujuan;
            $bahan_keluar->divisi = 'Produksi';
            $bahan_keluar->status_pengambilan = 'Belum Diambil';
            $bahan_keluar->status = 'Belum disetujui';
            $bahan_keluar->status_leader = $status_leader;
            $bahan_keluar->save();

            // Kelompokkan item berdasarkan bahan_id dan jumlah
            $groupedItems = [];
            foreach ($cartItems as $item) {
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
            }

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

            if ($targetPhone) {
                $message = "Halo {$recipientName},\n\n";
                $message .= "Pengajuan bahan keluar dengan kode transaksi $kode_transaksi memerlukan persetujuan Anda.\n\n";
                $message .= "Tgl Pengajuan: " . $tgl_pengajuan . "\nPengaju: {$user->name}\nDivisi: Produksi\nProject: Produksi $tujuan\nKeterangan: {$request->keterangan}\n\n";
                $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";

                SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
            } else {
                LogHelper::error('No valid phone number found for WhatsApp notification.');
            }

            LogHelper::success('Berhasil Menambahkan Pengajuan Produksi!');
            return redirect()->back()->with('success', 'Berhasil Menambahkan Pengajuan Produksi!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menambahkan data: ' . $e->getMessage());
        }
    }

    public function edit(string $id)
    {
        $units = Unit::all();
        $bahanProduksi = Bahan::whereHas('jenisBahan', function ($query) {
            $query->where('nama', 'like', '%Produksi%');
        })->get();

        $produksi = Produksi::with(['produksiDetails.dataBahan', 'bahanKeluar'])->findOrFail($id);

        // Kondisi untuk tombol "Selesai"
        $isComplete = true;
        $canInputKodeProduksi = true;
        if (is_null($produksi->kode_produksi)) {
            $isComplete = false;
        } else {
            if ($produksi->produksiDetails && count($produksi->produksiDetails) > 0) {
                foreach ($produksi->produksiDetails as $detail) {
                    $kebutuhan = $detail->jml_bahan - $detail->used_materials;
                    if ($kebutuhan > 0) {
                        $isComplete = false;
                        // $canInputKodeProduksi = false;
                        break;
                    }
                }
            }else {
                $isComplete = false;
                // $canInputKodeProduksi = false;
            }
        }

        return view('pages.produksis.edit', [
            'produksiId' => $produksi->id,
            'bahanProduksi' => $bahanProduksi,
            'produksi' => $produksi,
            'units' => $units,
            'id' => $id,
            'isComplete' => $isComplete,
            'canInputKodeProduksi' => $canInputKodeProduksi,
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            // dd($request->all());
            $cartItems = json_decode($request->produksiDetails, true) ?? [];
            $bahanRusak = json_decode($request->bahanRusak, true) ?? [];
            $bahanRetur = json_decode($request->bahanRetur, true) ?? [];
            $produksi = Produksi::findOrFail($id);
            $validator = Validator::make($request->all(), [
                // 'kode_produksi' => 'nullable',
                'keterangan' => 'nullable|string|max:255',
                'serial_number' => 'nullable|string|max:255', // Setiap item dalam array harus string
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $produksi->update([
                // 'kode_produksi' => $request->kode_produksi,
                'keterangan' => $request->keterangan,
                'serial_number' => $request->serial_number,
            ]);

            $produk = $request->produk_id;
            //dd($produk);
            if ($produk) {
                $tujuan = $produk;
            } else {
                $tujuan = null;
            }
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
            $kode_transaksi = 'KBK - ' . $formatted_number. ' Prod';
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

            foreach ($cartItems as $item) {
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
                $bahan_keluar->produksi_id = $produksi->id;
                $bahan_keluar->tgl_pengajuan = $tgl_pengajuan;
                $bahan_keluar->tujuan = 'Produksi '.$tujuan;
                $bahan_keluar->keterangan = $produksi->keterangan;
                $bahan_keluar->divisi = 'Produksi';
                $bahan_keluar->pengaju = $user->id;
                $bahan_keluar->status_pengambilan = 'Belum Diambil';
                $bahan_keluar->status = 'Belum disetujui';
                $bahan_keluar->status_leader = $status_leader;
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

                if ($targetPhone) {
                    $message = "Halo {$recipientName},\n\n";
                    $message .= "Pengajuan bahan keluar dengan kode transaksi $kode_transaksi memerlukan persetujuan Anda.\n\n";
                    $message .= "Tgl Pengajuan: " . $tgl_pengajuan . "\nPengaju: {$user->name}\nDivisi: Produksi\nProject: Produksi $tujuan\nKeterangan: {$produksi->keterangan}\n\n";
                    $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";

                    SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
                } else {
                    LogHelper::error('No valid phone number found for WhatsApp notification.');
                }
            }

            if (!empty($bahanRusak)) {
                $lastTransaction = BahanRusak::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
                if ($lastTransaction) {
                    $last_transaction_number = intval(substr($lastTransaction->kode_transaksi, 6));
                } else {
                    $last_transaction_number = 0;
                }
                $new_transaction_number = $last_transaction_number + 1;
                $formatted_number = str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT);
                $kode_transaksi = 'BRS - ' . $formatted_number. ' Prod';

                $bahanRusakRecord = BahanRusak::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                    'produksi_id' => $produksi->id,
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
                $kode_transaksi = 'BRT - ' . $formatted_number. ' Prod';

                $bahanReturRecord = BahanRetur::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                    'produksi_id' => $produksi->id,
                    'tujuan' => 'Produksi '.$tujuan,
                    'divisi' => 'Produksi',
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

            LogHelper::success('Berhasil Mengubah Detail Produksi!');
            return redirect()->back()->with('success', 'Produksi berhasil diperbarui!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $produksi = Produksi::findOrFail($id);

            if ($produksi->status !== 'Selesai') {
                if ($produksi->jenis_produksi === 'Produk Setengah Jadi') {
                    try {
                        DB::beginTransaction();

                        // Insert data ke tabel bahan_setengahjadi
                        $bahanSetengahJadi = new BahanSetengahjadi();
                        $bahanSetengahJadi->tgl_masuk = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                        $bahanSetengahJadi->kode_transaksi = $produksi->kode_produksi;
                        $bahanSetengahJadi->produksi_id = $produksi->id;
                        $bahanSetengahJadi->save();

                        // Hitung total produksi
                        $produksiTotal = $produksi->produksiDetails->sum('sub_total');
                        $unitPrice = $produksiTotal / $produksi->jml_produksi;

                        // Ambil serial number dari kolom `serial_number` di tabel `produksi`
                        $serialNumbers = explode(',', $produksi->serial_number);
                        $serialNumbers = array_map('trim', $serialNumbers); // Hilangkan spasi ekstra
                        // dd($serialNumbers);
                        if (count($serialNumbers) < $produksi->jml_produksi) {
                            DB::rollBack();
                            return redirect()->back()->with('error', "Jumlah serial number kurang. Harap lengkapi serial number sebelum menyelesaikan produksi.");
                        }

                        // Loop sebanyak jumlah produksi untuk membuat serial number unik
                        for ($i = 0; $i < $produksi->jml_produksi; $i++) {
                            $bahanSetengahJadiDetail = new BahanSetengahjadiDetails();
                            $bahanSetengahJadiDetail->bahan_setengahjadi_id = $bahanSetengahJadi->id;
                            // $bahanSetengahJadiDetail->bahan_id = $produksi->bahan_id;
                            $bahanSetengahJadiDetail->nama_bahan = $produksi->dataBahan->nama_bahan;
                            $bahanSetengahJadiDetail->qty = 1;
                            $bahanSetengahJadiDetail->sisa = 1;
                            $bahanSetengahJadiDetail->unit_price = $unitPrice;
                            $bahanSetengahJadiDetail->sub_total = $unitPrice;
                            $bahanSetengahJadiDetail->serial_number = $serialNumbers[$i] ?? null;

                            $bahanSetengahJadiDetail->save();
                        }

                        // Update status produksi menjadi "Selesai"
                        $produksi->status = 'Selesai';
                        $produksi->selesai_produksi = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                        $produksi->save();

                        DB::commit();

                        LogHelper::success('Berhasil Menyelesaikan Produksi Produk Setengah Jadi!');
                        return redirect()->back()->with('success', 'Produksi telah selesai.');
                    } catch (\Exception $e) {
                        DB::rollBack();
                        LogHelper::error($e->getMessage());
                        return redirect()->back()->with('error', "Gagal update status produksi. Simpan Kode Produksi dahulu!");
                    }
                }
            }
            return redirect()->back()->with('error', 'Produksi tidak bisa diupdate ke selesai.');
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }




    public function destroy(string $id)
    {
        try{
            $produksi = Produksi::find($id);
            if (!$produksi) {
                return redirect()->back()->with('gagal', 'Produksi tidak ditemukan.');
            }

            // Menghapus semua bahan keluar yang terkait dengan produksi_id
            BahanKeluar::where('produksi_id', $produksi->id)->delete();
            BahanRetur::where('produksi_id', $produksi->id)->delete();
            BahanRusak::where('produksi_id', $produksi->id)->delete();
            // Menghapus produksi
            $produksi->delete();

            return redirect()->back()->with('success', 'Produksi dan semua bahan terkait berhasil dihapus.');
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
