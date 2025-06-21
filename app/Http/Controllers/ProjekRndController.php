<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Models\User;
use App\Models\Bahan;
use App\Models\ProjekRnd;
use App\Helpers\LogHelper;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use Illuminate\Http\Request;
use App\Models\ProdukProduksi;
use App\Exports\ProjekRndExport;
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

class ProjekRndController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-projek-rnd', ['only' => ['index']]);
        $this->middleware('permission:selesai-projek-rnd', ['only' => ['updateStatus']]);
        $this->middleware('permission:tambah-projek-rnd', ['only' => ['create','store']]);
        $this->middleware('permission:edit-projek-rnd', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-projek-rnd', ['only' => ['destroy']]);
    }

    public function export($projek_rnd_id)
    {
        $projek_rnd = ProjekRnd::findOrFail($projek_rnd_id);
        $fileName = 'HPP_Projek_Rnd_' . 'Riset'.$projek_rnd->nama_projek_rnd . '_be-inventory.xlsx';
        return Excel::download(new ProjekRndExport($projek_rnd_id), $fileName);
    }

    public function index()
    {
        return view('pages.projek-rnd.index');
    }

    public function create()
    {
        $units = Unit::all();
        $bahans = Bahan::whereHas('jenisBahan', function($query) {
            $query->where('nama', 'Projek RnD');
        })->get();
        return view('pages.projek-rnd.create', compact('units', 'bahans'));
    }

    public function store(Request $request)
    {
        try {
            // dd($request->all());
            $cartItems = json_decode($request->cartItems, true);
            $validator = Validator::make([
                'nama_projek_rnd' => $request->nama_projek_rnd,
                // 'bahan_id' => $request->bahan_id,
                'mulai_projek_rnd' => $request->mulai_projek_rnd,
                'keterangan' => $request->keterangan,
                'cartItems' => $cartItems
            ], [
                'nama_projek_rnd' => 'required',
                // 'bahan_id' => 'required',
                'mulai_projek_rnd' => 'required',
                'keterangan' => 'required',
                'cartItems' => 'required|array',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $tujuan = 'Proyek/Riset '. $request->nama_projek_rnd;
            $user = Auth::user();

            $purchasingUser = User::whereHas('dataJobPosition', function ($query) {
                $query->where('nama', 'Purchasing');
            })->where('job_level', 3)->first();

            $lastTransaction = BahanKeluar::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number = ($lastTransaction ? intval(substr($lastTransaction->kode_transaksi, 6)) : 0) + 1;
            $kode_transaksi = 'KBK - ' . str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT). ' PJRnD';
            $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            $lastTransactionProjek = ProjekRnd::orderByRaw('CAST(SUBSTRING(kode_projek_rnd, 10) AS UNSIGNED) DESC')->first();
            $new_transaction_number_produksi = ($lastTransactionProjek ? intval(substr($lastTransactionProjek->kode_projek_rnd, 9)) : 0) + 1;
            $kode_projek_rnd = 'PJRnD - ' . str_pad($new_transaction_number_produksi, 5, '0', STR_PAD_LEFT);
            // dd($kode_projek_rnd);

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

            $projek_rnd = ProjekRnd::create([
                'kode_projek_rnd' => $kode_projek_rnd,
                // 'bahan_id' => $request->bahan_id,
                'nama_projek_rnd' => $request->nama_projek_rnd,
                'pengaju' => $user->name,
                'keterangan' => $request->keterangan,
                'mulai_projek_rnd' => $request->mulai_projek_rnd,
                'status' => 'Dalam Proses'
            ]);

            $bahan_keluar = BahanKeluar::create([
                'kode_transaksi' => $kode_transaksi,
                'projek_rnd_id' => $projek_rnd->id,
                'tgl_pengajuan' => $tgl_pengajuan,
                'tujuan' => $tujuan,
                'keterangan' => $request->keterangan,
                'divisi' => 'RnD',
                'pengaju' => $user->id,
                'status_pengambilan' => 'Belum Diambil',
                'status' => 'Belum disetujui',
                'status_leader' => $status_leader,
            ]);

            // Group items by bahan_id
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
                $message .= "Tgl Pengajuan: " . $tgl_pengajuan . "\nPengaju: {$user->name}\nDivisi: RnD\nProject: {$tujuan}\nKeterangan: {$request->keterangan}\n\n";
                $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";

                SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
            } else {
                LogHelper::error('No valid phone number found for WhatsApp notification.');
            }
            $request->session()->forget('cartItems');
            LogHelper::success('Berhasil Menambahkan Pengajuan Proyek RnD!');
            return redirect()->back()->with('success', 'Berhasil Menambahkan Pengajuan Proyek RnD!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menambahkan data: ' . $e->getMessage());
        }
    }


    public function edit(string $id)
    {
        $units = Unit::all();
        $bahanProjek = Bahan::whereHas('jenisBahan', function ($query) {
            $query->where('nama', 'like', '%Produksi%');
        })->get();
        $projek_rnd = ProjekRnd::with([
            'projekRndDetails.dataBahan',
            'projekRndDetails.dataProduk',
            'bahanKeluar'
            ])->findOrFail($id);

        $isComplete = true;
        if ($projek_rnd->projekRndDetails && count($projek_rnd->projekRndDetails) > 0) {
            foreach ($projek_rnd->projekRndDetails as $detail) {
                $kebutuhan = $detail->jml_bahan - $detail->used_materials;
                if ($kebutuhan > 0) {
                    $isComplete = false;
                    break;
                }
            }
        } else {
            $isComplete = false;
        }
        return view('pages.projek-rnd.edit', [
            'projekId' => $projek_rnd->id,
            'bahanProjek' => $bahanProjek,
            'projek_rnd' => $projek_rnd,
            'units' => $units,
            'id' => $id,
            'isComplete' => $isComplete,
        ]);
    }


    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'nama_projek_rnd' => 'required|string|max:255',
            'keterangan' => 'required|string|max:255', // Validasi keterangan
            'serial_number' => 'nullable|string|max:255',
        ]);
        try {
            //dd($request->all());
            $projekRndDetails = json_decode($request->projekRndDetails, true) ?? [];
            $bahanRusak = json_decode($request->bahanRusak, true) ?? [];
            $bahanRetur = json_decode($request->bahanRetur, true) ?? [];
            $projek_rnd = ProjekRnd::findOrFail($id);

            $projek_rnd->update([
                'nama_projek_rnd' => $validatedData['nama_projek_rnd'],
                'keterangan' => $validatedData['keterangan'],
                'serial_number' => $validatedData['serial_number'],
            ]);

            $tujuan = 'Proyek/Riset '. $projek_rnd->nama_projek_rnd;
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
            $kode_transaksi = 'KBK - ' . $formatted_number. ' PJRnD';
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

            foreach ($projekRndDetails as $item) {
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
                    'projek_rnd_id' => $projek_rnd->id,
                    'tgl_pengajuan' => $tgl_pengajuan,
                    'tujuan' => $tujuan,
                    'keterangan' => $projek_rnd->keterangan,
                    'divisi' => 'RnD',
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
                    $message .= "Tgl Pengajuan: " . $tgl_pengajuan . "\nPengaju: {$user->name}\nDivisi: RnD\nProject: {$tujuan}\nKeterangan: {$projek_rnd->keterangan}\n\n";
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
                $kode_transaksi = 'BRS - ' . $formatted_number. ' PJRnD';

                $bahanRusakRecord = BahanRusak::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                    'projek_rnd_id' => $projek_rnd->id,
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
                $kode_transaksi = 'BRT - ' . $formatted_number. ' PJRnD';

                $bahanReturRecord = BahanRetur::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                    'projek_rnd_id' => $projek_rnd->id,
                    'tujuan' => 'Projek ' . $tujuan,
                    'divisi' => 'RnD',
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

            LogHelper::success('Berhasil Mengubah Detail Projek RnD!');
            return redirect()->back()->with('success', 'Projek RnD berhasil diperbarui!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $projek_rnd = ProjekRnd::findOrFail($id);

            // Jika user memilih "Tidak", biarkan proyek tetap berjalan
            if ($request->status === 'batal') {
                $projek_rnd->status = 'Tidak dilanjutkan';
                $projek_rnd->selesai_projek_rnd = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                $projek_rnd->save();
                LogHelper::success('Berhasil membatalkan proyek RnD.');
                return redirect()->back()->with('success', 'Projek RnD tidak dilanjutkan.');
            }

            //Jika proyek belum selesai, lanjutkan proses penyimpanan
            if ($request->status === 'selesai') {
                if (empty($projek_rnd->serial_number)) {
                    return redirect()->back()->with('error', 'Silakan isi dan Simpan Serial Number sebelum menyelesaikan proyek RnD.');
                }
                try {
                    DB::beginTransaction();

                    // Simpan ke bahan_setengahjadi
                    $bahanSetengahJadi = new BahanSetengahjadi();
                    $bahanSetengahJadi->tgl_masuk = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                    $bahanSetengahJadi->kode_transaksi = $projek_rnd->kode_projek_rnd;
                    $bahanSetengahJadi->projek_rnd_id = $projek_rnd->id;
                    $bahanSetengahJadi->save();

                    $projekRndTotal = $projek_rnd->projekRndDetails->sum('sub_total');

                    // Simpan ke bahan_setengahjadi_details
                    $bahanSetengahJadiDetail = new BahanSetengahjadiDetails();
                    $bahanSetengahJadiDetail->bahan_setengahjadi_id = $bahanSetengahJadi->id;
                    $bahanSetengahJadiDetail->nama_bahan = $projek_rnd->nama_projek_rnd;
                    $bahanSetengahJadiDetail->qty = 1;
                    $bahanSetengahJadiDetail->sisa = 1;
                    $bahanSetengahJadiDetail->unit_price = $projekRndTotal;
                    $bahanSetengahJadiDetail->sub_total = $projekRndTotal;
                    $bahanSetengahJadiDetail->serial_number = $projek_rnd->serial_number;
                    $bahanSetengahJadiDetail->save();

                    // Update status proyek menjadi selesai
                    $projek_rnd->status = 'Selesai';
                    $projek_rnd->selesai_projek_rnd = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                    $projek_rnd->save();

                    DB::commit();

                    LogHelper::success('Berhasil menyelesaikan Projek RnD!');
                    return redirect()->back()->with('success', 'Projek RnD telah selesai.');
                } catch (\Exception $e) {
                    DB::rollBack();
                    LogHelper::error($e->getMessage());
                    return redirect()->back()->with('error', 'Gagal menyelesaikan Projek RnD.');
                }
            }

            return redirect()->back()->with('error', 'Projek RnD sudah selesai sebelumnya.');
        } catch (\Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }


    public function destroy(string $id)
    {
        try{
            $projek_rnd = ProjekRnd::find($id);
            //dd($projek_rnd);
            if (!$projek_rnd) {
                return redirect()->back()->with('gagal', 'Projek RnD tidak ditemukan.');
            }
            $projek_rnd->delete();
            LogHelper::success('Projek RnD berhasil dihapus!');
            return redirect()->back()->with('success', 'Projek RnD berhasil dihapus!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
