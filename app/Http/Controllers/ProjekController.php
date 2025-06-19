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

class ProjekController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-projek', ['only' => ['index']]);
        $this->middleware('permission:selesai-projek', ['only' => ['updateStatus']]);
        $this->middleware('permission:tambah-projek', ['only' => ['create','store']]);
        $this->middleware('permission:edit-projek', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-projek', ['only' => ['destroy']]);
    }

    public function export($projek_id)
    {
        $projek = Projek::findOrFail($projek_id);
        $fileName = 'HPP_Project_' . $projek->nama_projek . '_be-inventory.xlsx';
        return Excel::download(new ProjekExport($projek_id), $fileName);
    }



    public function index()
    {
        return view('pages.projek.index');
    }

    public function create()
    {
        $units = Unit::all();
        $produkProduksi = ProdukProduksi::all();

        // Ambil ID kontrak yang sudah digunakan dalam tabel projek
        $usedKontrakIds = Projek::pluck('kontrak_id')->toArray();

        $kontraks = Kontrak::all()->sortBy(function ($kontrak) {
        $parts = explode('/', $kontrak->kode_kontrak);
        $nomor = isset($parts[0]) ? (int) $parts[0] : 0; // Bagian nomor
        $tahun = isset($parts[5]) ? (int) $parts[5] : 0;  // Bagian tahun
            return [$tahun, $nomor]; // Urutkan berdasarkan tahun, lalu nomor
        });

        return view('pages.projek.create', compact('units', 'produkProduksi', 'kontraks', 'usedKontrakIds'));
    }

    public function store(Request $request)
    {
        try {
            // dd($request->all());
            $cartItems = json_decode($request->cartItems, true);
            $validator = Validator::make([
                'kontrak_id' => $request->kontrak_id,
                'mulai_projek' => $request->mulai_projek,
                'keterangan' => $request->keterangan,
                'cartItems' => $cartItems
            ], [
                'kontrak_id' => 'required',
                'mulai_projek' => 'required',
                'keterangan' => 'required',
                'cartItems' => 'required|array',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $kontrak = Kontrak::find($request->kontrak_id);
            if (!$kontrak) {
                return redirect()->back()->with('error', 'Kontrak tidak ditemukan!')->withInput();
            }
            $tujuan = $kontrak->nama_kontrak;
            $user = Auth::user();

            $purchasingUser = User::whereHas('dataJobPosition', function ($query) {
                $query->where('nama', 'Purchasing');
            })->where('job_level', 3)->first();


            // Create transaction code for BahanKeluar
            $lastTransaction = BahanKeluar::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number = ($lastTransaction ? intval(substr($lastTransaction->kode_transaksi, 6)) : 0) + 1;
            $kode_transaksi = 'KBK - ' . str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT). ' PJPro';
            $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            // Create transaction code for Projek
            $lastTransactionProjek = Projek::orderByRaw('CAST(SUBSTRING(kode_projek, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number_produksi = ($lastTransactionProjek ? intval(substr($lastTransactionProjek->kode_projek, 6)) : 0) + 1;
            $kode_projek = 'PRJ - ' . str_pad($new_transaction_number_produksi, 5, '0', STR_PAD_LEFT);

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

            $projek = Projek::create([
                'kode_projek' => $kode_projek,
                'kontrak_id' => $request->kontrak_id,
                'pengaju' => $user->name,
                'keterangan' => $request->keterangan,
                'mulai_projek' => $request->mulai_projek,
                'status' => 'Dalam Proses'
            ]);

            $bahan_keluar = BahanKeluar::create([
                'kode_transaksi' => $kode_transaksi,
                'projek_id' => $projek->id,
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
            LogHelper::success('Berhasil Menambahkan Pengajuan Proyek!');
            return redirect()->back()->with('success', 'Berhasil Menambahkan Pengajuan Proyek!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menambahkan data: ' . $e->getMessage());
        }
    }


    public function edit(string $id)
    {
        $units = Unit::all();
        $kontraks = Kontrak::all();
        $bahanProjek = Bahan::whereHas('jenisBahan', function ($query) {
            $query->where('nama', 'like', '%Produksi%');
        })->get();

        $projek = Projek::with([
            'projekDetails.dataBahan',
            'projekDetails.dataProduk', // Tambahkan ini untuk memuat produk
            'bahanKeluar'
        ])->findOrFail($id);
        // dd($projek->projekDetails);


        // Ambil bahan yang ada di projekDetails
        $existingBahanIds = $projek->projekDetails->pluck('dataBahan.id')->toArray();

        $isComplete = true;
        if ($projek->projekDetails && count($projek->projekDetails) > 0) {
            foreach ($projek->projekDetails as $detail) {
                $kebutuhan = $detail->jml_bahan - $detail->used_materials;
                if ($kebutuhan > 0) {
                    $isComplete = false;
                    break;
                }
            }
        } else {
            $isComplete = false;
        }

        return view('pages.projek.edit', [
            'projekId' => $id,
            'bahanProjek' => $bahanProjek,
            'kontraks' => $kontraks,
            'projek' => $projek,
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
            $projekDetails = json_decode($request->projekDetails, true) ?? [];
            $bahanRusak = json_decode($request->bahanRusak, true) ?? [];
            $bahanRetur = json_decode($request->bahanRetur, true) ?? [];
            $projek = Projek::findOrFail($id);

            $projek->update([
                'keterangan' => $validatedData['keterangan'],
            ]);

            $tujuan = $projek->dataKontrak->nama_kontrak;
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
            $kode_transaksi = 'KBK - ' . $formatted_number. ' PJPro';
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

            foreach ($projekDetails as $item) {
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
                    'projek_id' => $projek->id,
                    'tgl_pengajuan' => $tgl_pengajuan,
                    'tujuan' => $tujuan,
                    'keterangan' => $projek->keterangan,
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
                    $message .= "Tgl Pengajuan: " . $tgl_pengajuan . "\nPengaju: {$user->name}\nDivisi: Teknisi\nProject: {$tujuan}\nKeterangan: {$projek->keterangan}\n\n";
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
                $kode_transaksi = 'BRS - ' . $formatted_number. ' PJPro';

                $bahanRusakRecord = BahanRusak::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                    'projek_id' => $projek->id,
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
                $kode_transaksi = 'BRT - ' . $formatted_number. ' PJPro';

                $bahanReturRecord = BahanRetur::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                    'projek_id' => $projek->id,
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

            LogHelper::success('Berhasil Mengubah Detail Projek!');
            return redirect()->back()->with('success', 'Projek berhasil diperbarui!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try{
            $projek = Projek::findOrFail($id);
            //dd($projek);
            $projek->status = 'Selesai';
            $projek->selesai_projek = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $projek->save();
            LogHelper::success('Berhasil menyelesaikan projek!');
            return redirect()->back()->with('error', 'Projek tidak bisa diupdate ke selesai.');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy(string $id)
    {
        try{
            $projek = Projek::find($id);
            if (!$projek) {
                return redirect()->back()->with('gagal', 'Projek tidak ditemukan.');
            }
            $projek->delete();
            LogHelper::success('Projek berhasil dihapus!');
            return redirect()->back()->with('success', 'Projek berhasil dihapus!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

}
