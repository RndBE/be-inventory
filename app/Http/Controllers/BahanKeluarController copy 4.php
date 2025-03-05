<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Models\User;
use App\Models\Bahan;
use App\Models\Projek;
use App\Models\StokRnd;
use App\Models\Produksi;
use App\Helpers\LogHelper;
use App\Models\BahanKeluar;
use App\Models\StokProduksi;
use Illuminate\Http\Request;
use App\Models\ProjekDetails;
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
use App\Jobs\SendWhatsAppApproveLeader;
use App\Models\PengambilanBahanDetails;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Support\Facades\Validator;

class BahanKeluarController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-bahan-keluar', ['only' => ['index']]);
        $this->middleware('permission:detail-bahan-keluar', ['only' => ['show']]);
        $this->middleware('permission:tambah-bahan-keluar', ['only' => ['create','store']]);
        $this->middleware('permission:edit-bahan-keluar', ['only' => ['update','edit']]);
        $this->middleware('permission:edit-pengambilan', ['only' => ['updatepengambilan']]);
        $this->middleware('permission:edit-approve-leader', ['only' => ['updateApprovalLeader']]);

        $this->middleware('permission:hapus-bahan-keluar', ['only' => ['destroy']]);
    }

    public function downloadPdf(int $id)
    {
        try {
            $bahanKeluar = BahanKeluar::with([
                'dataUser.atasanLevel1',
                'dataUser.atasanLevel2',
                'dataUser.atasanLevel3',
                'bahanKeluarDetails.dataBahan.dataUnit',
            ])->findOrFail($id);

            $tandaTanganPengaju = $bahanKeluar->dataUser->tanda_tangan ?? null;

            $tandaTanganLeader = null;
            $tandaTanganManager = $bahanKeluar->dataUser->atasanLevel2->tanda_tangan ?? null;
            $tandaTanganDirektur = $bahanKeluar->dataUser->atasanLevel1->tanda_tangan ?? null;

            if ($bahanKeluar->dataUser->atasanLevel3) {
                $tandaTanganLeader = $bahanKeluar->dataUser->atasanLevel3->tanda_tangan ?? null;
            } elseif ($bahanKeluar->dataUser->atasanLevel2) {
                $tandaTanganLeader = $bahanKeluar->dataUser->atasanLevel2->tanda_tangan ?? null;
            }

            $leaderName = $bahanKeluar->dataUser->atasanLevel3 ? $bahanKeluar->dataUser->atasanLevel3->name : null;
            $managerName = $bahanKeluar->dataUser->atasanLevel2 ? $bahanKeluar->dataUser->atasanLevel2->name : null;

            if (!$leaderName && $managerName) {
                $leaderName = $managerName;
            }

            $purchasingUser = cache()->remember('purchasing_user', 60, function () {
                return User::where('job_level', 3)
                    ->whereHas('dataJobPosition', function ($query) {
                        $query->where('nama', 'Purchasing');
                    })->first();
            });
            $tandaTanganPurchasing = $purchasingUser->tanda_tangan ?? null;

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

            $pdf = Pdf::loadView('pages.bahan-keluars.pdf', compact(
                'bahanKeluar',
                'purchasingUser',
                'leaderName',
                'managerName'
            ))->setPaper('letter', 'portrait');
            return $pdf->stream("bahan_keluar_{$id}.pdf");

            LogHelper::success('Berhasil generating PDF for BahanKeluar ID {$id}!');
            return $pdf->download("bahan_keluar_{$id}.pdf");

        } catch (\Exception $e) {
            LogHelper::error("Error generating PDF for BahanKeluar ID {$id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengunduh PDF.');
        }
    }

    public function index()
    {
        $bahan_keluars = BahanKeluar::with('bahanKeluarDetails')->get();
        return view('pages.bahan-keluars.index', compact('bahan_keluars'));
    }

    public function create()
    {
        return view('pages.bahan-keluars.create');
    }

    public function edit(string $id)
    {
        $units = Unit::all();

        $bahan_keluar = BahanKeluar::with(['bahanKeluarDetails'])->findOrFail($id);

        return view('pages.bahan-keluars.edit', [
            'bahanKeluarId' => $id,
            'bahan_keluar' => $bahan_keluar,
            'units' => $units,
        ]);
    }

    public function update(Request $request, string $id)
    {
        // dd($request->all());
        $validatedData = $request->validate([
            'bahanKeluarDetails' => 'required|string',
        ]);
        $bahanKeluarDetails = json_decode($validatedData['bahanKeluarDetails'], true);
        if (!is_array($bahanKeluarDetails)) {
            return redirect()->back()->with('error', 'Data bahan keluar tidak valid.');
        }
        try {
            $invalidBahan = []; // Array untuk menyimpan bahan atau produk dengan stok kosong

            // Validasi semua bahan sebelum memulai transaksi
            foreach ($bahanKeluarDetails as $item) {
                if (empty($item['details']) || $item['sub_total'] == 0) {
                    $bahanId = $item['bahan_id'] ?? null;
                    $produkId = $item['produk_id'] ?? null;

                    if ($bahanId) {
                        $bahan = Bahan::find($bahanId); // Ambil nama bahan berdasarkan ID
                        $bahanNama = $bahan ? $bahan->nama_bahan : 'Tidak diketahui';
                        $invalidBahan[] = "Bahan: $bahanNama";
                    } elseif ($produkId) {
                        $produk = BahanSetengahjadiDetails::find($produkId); // Ambil nama produk berdasarkan ID
                        $produkNama = $produk ? $produk->nama_bahan : 'Tidak diketahui';
                        $invalidBahan[] = "Produk: $produkNama";
                    }
                }
            }

            // Jika ada bahan dengan stok kosong, batalkan transaksi
            if (!empty($invalidBahan)) {
                $bahanList = implode(', ', $invalidBahan);
                LogHelper::error("Transaksi dibatalkan: Stok kosong atau tidak valid untuk bahan berikut: $bahanList.");
                $bahanKeluar  = BahanKeluar::find($id);
                $pengajuPhone = $bahanKeluar->dataUser->telephone;
                if ($pengajuPhone) {
                    // Kirim pesan WhatsApp ke pengaju tentang bahan yang stoknya kosong
                    try {
                        $message = "Halo {$bahanKeluar->dataUser->name},\n\n";
                        $message .= "Kode Transaksi {$bahanKeluar->kode_transaksi} dibatalkan karena stok kosong atau tidak valid untuk bahan berikut: $bahanList.\n\n";
                        $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";

                        // Kirim pesan WhatsApp ke pengaju
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
                }

                return redirect()->route('bahan-keluars.index')->with(
                    'error',
                    "Transaksi dibatalkan: Stok kosong atau tidak valid untuk bahan berikut: $bahanList."
                );
            }

            DB::beginTransaction(); // Mulai transaksi
            // Menyimpan/Update bahan atau produk yang tersedia di gudang ke tabel bahan_keluar_details
            foreach ($bahanKeluarDetails as $item) {
                BahanKeluarDetails::updateOrCreate(
                    [
                        'bahan_keluar_id' => $id,
                        'bahan_id' => $item['bahan_id'] ?? null, // Gunakan bahan_id jika ada
                        'produk_id' => $item['produk_id'] ?? null, // Gunakan produk_id jika bahan_id tidak ada
                        'serial_number' => $item['serial_number'] ?? null // Tambahkan serial number ke kondisi pencarian
                    ],
                    [
                        'qty' => $item['qty'],
                        'jml_bahan' => $item['jml_bahan'],
                        'used_materials' => 0,
                        'details' => json_encode($item['details']),
                        'sub_total' => $item['sub_total'],
                    ]
                );
            }


            // Cari apakah ada data bahan keluar dengan id tersebut
            $bahanKeluar  = BahanKeluar::find($id);
            if (!$bahanKeluar) {
                throw new \Exception('Bahan Keluar tidak ditemukan.');
            }
            // Jika id ditemukan maka simpan status dan tgl_keluar di tabel bahan_keluars
            $bahanKeluar->status = 'Disetujui';
            $bahanKeluar->tgl_keluar = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $bahanKeluar->save();
            // Ambil data bahan_keluar_details
            $details = BahanKeluarDetails::where('bahan_keluar_id', $id)->get();
            $pendingStockReductions = []; // Inisialisasi data

            foreach ($details as $detail) {
                $transactionDetails = json_decode($detail->details, true) ?? []; // Ubah JSON menjadi array
                $groupedDetails = []; // Inisialisasi data
                // Mengecek apakah transactionDetails kosong atau tidak ada informasi transaksi untuk bahan tersebut.
                if (empty($transactionDetails)) {
                    if ($bahanKeluar->projek_id) {
                        // Melakukan pencarian pada tabel projek_details
                        $existingDetail = ProjekDetails::where('projek_id', $bahanKeluar->projek_id)
                        ->where(function ($query) use ($detail) {
                            $query->where('bahan_id', $detail->bahan_id)
                                ->orWhere('produk_id', $detail->produk_id);
                        })
                        ->first();
                        // Jika tidak ditemukan entri sebelumnya, Dibuatkan entri baru di tabel projek_details dengan data default
                        if (!$existingDetail) {
                            ProjekDetails::create([
                                'projek_id' => $bahanKeluar->projek_id,
                                'bahan_id' => $detail->bahan_id ?? null,
                                'produk_id' => $detail->produk_id ?? null,
                                'qty' => 0,
                                'jml_bahan' => $detail->jml_bahan,
                                'used_materials' => 0,
                                'details' => json_encode([]),
                                'sub_total' => 0,
                            ]);
                        }
                    }
                }

                // Melakukan iterasi untuk setiap detail dalam array $transactionDetails
                foreach ($transactionDetails as $transaksiDetail) {
                    $unitPrice = $transaksiDetail['unit_price']; // Menyimpan harga satuan dari detail transaksi saat ini
                    $qty = $transaksiDetail['qty']; // Menyimpan jumlah bahan (qty) dari detail transaksi saat ini.
                    $serialNumber = $transaksiDetail['serial_number'] ?? null; // Ambil serial number jika ada

                    // Mengelompokkan jumlah kuantitas (qty) berdasarkan harga satuan (unit_price) dalam data detail transaksi.
                    // Cek Apakah Harga Satuan Sudah Ada di $groupedDetails
                    // Jika sudah ada maka Jumlah (qty) ditambahkan ke nilai sebelumnya
                    // Jika belum ada, Membuat entri baru di $groupedDetails dengan kunci unit_price dan menyimpan qty dan unit_price
                    if (isset($groupedDetails[$unitPrice])) {
                        $groupedDetails[$unitPrice]['qty'] += $qty;
                    } else {
                        $groupedDetails[$unitPrice] = [
                            'qty' => $qty,
                            'unit_price' => $unitPrice,
                            'serial_number' => $serialNumber,
                        ];
                    }
                }
                // Memeriksa apakah variabel $transactionDetails adalah sebuah array. Jika ya, kode di dalam blok if akan dieksekusi.
                if (is_array($transactionDetails)) {
                    $groupedDetails = []; // Mendeklarasikan array kosong $groupedDetails, yang akan digunakan untuk menyimpan transaksi yang telah dikelompokkan berdasarkan harga satuan (unit_price).
                    foreach ($transactionDetails as $transaksiDetail) {
                        if ($detail->produk_id) {
                            // Jika bahan setengah jadi (menggunakan produk_id)
                            $setengahJadiDetail = BahanSetengahjadiDetails::where('produk_id', $detail->produk_id)
                                ->whereHas('bahanSetengahjadi', function ($query) use ($transaksiDetail) {
                                    $query->where('kode_transaksi', $transaksiDetail['kode_transaksi']);
                                })
                                ->where('unit_price', $transaksiDetail['unit_price'])
                                ->where('serial_number', $transaksiDetail['serial_number'] ?? null)
                                ->first();

                            if ($setengahJadiDetail) {
                                if ($transaksiDetail['qty'] > $setengahJadiDetail->sisa) {
                                    throw new \Exception('Tolak pengajuan, Stok bahan setengah jadi tidak cukup!');
                                }

                                // Pengelompokan berdasarkan harga satuan
                                $unitPrice = $transaksiDetail['unit_price'];
                                if (isset($groupedDetails[$unitPrice])) {
                                    $groupedDetails[$unitPrice]['qty'] += $transaksiDetail['qty'];
                                    if (!isset($groupedDetails[$unitPrice]['serial_number'])) {
                                        $groupedDetails[$unitPrice]['serial_number'] = $transaksiDetail['serial_number'] ?? null;
                                    }
                                } else {
                                    $groupedDetails[$unitPrice] = [
                                        'qty' => $transaksiDetail['qty'],
                                        'unit_price' => $unitPrice,
                                        'serial_number' => $transaksiDetail['serial_number'] ?? null,
                                    ];
                                }

                                // Kurangi stok bahan setengah jadi
                                $setengahJadiDetail->sisa -= $transaksiDetail['qty'];
                                $setengahJadiDetail->sisa = max(0, $setengahJadiDetail->sisa);
                                $setengahJadiDetail->save();
                            } else {
                                throw new \Exception('Bahan setengah jadi tidak ditemukan!');
                            }
                        } elseif ($detail->bahan_id) {
                            // Jika bahan dari pembelian (menggunakan bahan_id)
                            $purchaseDetail = PurchaseDetail::where('bahan_id', $detail->bahan_id)
                                ->whereHas('purchase', function ($query) use ($transaksiDetail) {
                                    $query->where('kode_transaksi', $transaksiDetail['kode_transaksi']);
                                })
                                ->where('unit_price', $transaksiDetail['unit_price'])
                                ->first();

                            if ($purchaseDetail) {
                                if ($transaksiDetail['qty'] > $purchaseDetail->sisa) {
                                    throw new \Exception('Tolak pengajuan, Lakukan pengajuan bahan kembali!');
                                }

                                // Pengelompokan berdasarkan harga satuan
                                $unitPrice = $transaksiDetail['unit_price'];
                                if (isset($groupedDetails[$unitPrice])) {
                                    $groupedDetails[$unitPrice]['qty'] += $transaksiDetail['qty'];
                                } else {
                                    $groupedDetails[$unitPrice] = [
                                        'qty' => $transaksiDetail['qty'],
                                        'unit_price' => $unitPrice,
                                    ];
                                }

                                // Kurangi stok bahan dari purchase_details
                                $purchaseDetail->sisa -= $transaksiDetail['qty'];
                                $purchaseDetail->sisa = max(0, $purchaseDetail->sisa);
                                $purchaseDetail->save();
                            } else {
                                throw new \Exception('Bahan dari pembelian tidak ditemukan!');
                            }
                        } else {
                            throw new \Exception('Bahan atau produk tidak valid!');
                        }
                    }
                    if ($bahanKeluar->projek_id) {
                        // Iterasi array groupedDetails untuk mengelola bahan keluar
                        foreach ($groupedDetails as $unitPrice => $group) {
                            if ($detail->produk_id) {
                                // Jika bahan setengah jadi (menggunakan produk_id)
                                $projekDetailQuery = ProjekDetails::where('projek_id', $bahanKeluar->projek_id)
                                    ->where('produk_id', $detail->produk_id);
                            } elseif ($detail->bahan_id) {
                                // Jika bahan dari pembelian (menggunakan bahan_id)
                                $projekDetailQuery = ProjekDetails::where('projek_id', $bahanKeluar->projek_id)
                                    ->where('bahan_id', $detail->bahan_id);
                            } else {
                                continue; // Jika tidak ada produk_id atau bahan_id, skip iterasi
                            }

                            // Tambahkan kondisi serial_number jika ada
                            if (!empty($group['serial_number'])) {
                                $projekDetailQuery->where('serial_number', $group['serial_number']);
                            }

                            // Eksekusi query
                            $projekDetail = $projekDetailQuery->first();

                            if ($projekDetail) {
                                // Update existing entry jika bahan_id / produk_id & serial_number sama
                                $projekDetail->qty += $group['qty'];
                                $projekDetail->used_materials += $group['qty'];
                                $projekDetail->sub_total += $group['qty'] * $unitPrice;

                                if ($projekDetail->jml_bahan !== $detail->jml_bahan) {
                                    $projekDetail->jml_bahan = $detail->jml_bahan;
                                }

                                // Update details field
                                $currentDetails = json_decode($projekDetail->details, true) ?? [];
                                $mergedDetails = [];

                                foreach ($currentDetails as $existingDetail) {
                                    $price = $existingDetail['unit_price'];
                                    $mergedDetails[$price] = $existingDetail;
                                }

                                // Memperbarui atau menambah detail transaksi baru ke dalam array yang sudah ada.
                                if (isset($mergedDetails[$unitPrice])) {
                                    $mergedDetails[$unitPrice]['qty'] += $group['qty'];
                                } else {
                                    $mergedDetails[$unitPrice] = $group;
                                }

                                $projekDetail->details = json_encode(array_values($mergedDetails));
                                $projekDetail->save();
                            } else {
                                // Buat entri baru jika produk_id / bahan_id sama tapi serial_number berbeda atau tidak ada
                                ProjekDetails::create([
                                    'projek_id' => $bahanKeluar->projek_id,
                                    'produk_id' => $detail->produk_id ?? null,
                                    'bahan_id' => $detail->bahan_id ?? null,
                                    'serial_number' => $group['serial_number'] ?? null, // Tambahkan serial number jika ada
                                    'qty' => $group['qty'],
                                    'jml_bahan' => $detail->jml_bahan,
                                    'used_materials' => $group['qty'],
                                    'details' => json_encode([$group]),
                                    'sub_total' => $group['qty'] * $unitPrice,
                                ]);
                            }
                        }
                    }

                }
            }
            // Kode ini digunakan untuk mengurangi stok bahan sesuai dengan transaksi yang dilakukan
            // dan memastikan bahwa stok tidak menjadi negatif.
            foreach ($pendingStockReductions as $reduction) {
                $reduction['detail']->sisa -= $reduction['qty'];
                $reduction['detail']->sisa = max(0, $reduction['detail']->sisa); // Setelah pengurangan stok, kode ini memastikan bahwa stok tidak bisa bernilai negatif.
                $reduction['detail']->save();
            }
            DB::commit();
            LogHelper::success('Berhasil Mengubah Status Bahan Keluar!');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Pesan error: $errorMessage");
        }
        return redirect()->route('bahan-keluars.index')->with('success', 'Status berhasil diubah.');
    }

    public function show(string $id)
    {
        $bahankeluar = BahanKeluar::with('bahanKeluarDetails.dataBahan.dataUnit')->findOrFail($id); // Mengambil detail pembelian
        return view('pages.bahan-keluars.show', [
            'kode_transaksi' => $bahankeluar->kode_transaksi,
            'tgl_keluar' => $bahankeluar->tgl_keluar,
            'divisi' => $bahankeluar->divisi,
            'bahanKeluarDetails' => $bahankeluar->bahanKeluarDetails,
        ]);
    }

    public function updateApprovalLeader(Request $request, int $id)
    {
        $validated = $request->validate([
            'status_leader' => 'required|string|in:Belum disetujui,Disetujui,Ditolak',
        ]);
        try {
            DB::beginTransaction();
            $data = BahanKeluar::with([
                'dataUser.atasanLevel1',
                'dataUser.atasanLevel2',
                'dataUser.atasanLevel3',
                'bahanKeluarDetails.dataBahan.dataUnit',
            ])->findOrFail($id);

            $data->status_leader = $validated['status_leader'];
            $data->save();

            if ($data->status_leader === 'Disetujui') {

                $purchasingUsers = User::whereHas('dataJobPosition', function ($query) {
                    $query->where('nama', 'Purchasing');
                })->where('job_level', 3)->first();

                $targetPhone = $purchasingUsers->telephone;
                //dd($targetPhone);
                if ($targetPhone) {
                    $message = "Halo {$purchasingUsers->name},\n\n";
                    $message .= "Pengajuan bahan keluar dengan kode transaksi {$data->kode_transaksi} memerlukan persetujuan Anda sebagai Purchasing.\n\n";
                    $message .= "Tgl Pengajuan: {$data->tgl_pengajuan}\nPengaju: {$data->dataUser->name}\nDivisi: {$data->divisi}\nProject: {$data->tujuan}\nKeterangan: {$data->keterangan}\n\n";
                    $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
                    SendWhatsAppApproveLeader::dispatch($targetPhone, $message);
                    LogHelper::success("Pesan sedang dikirim.");
                } else {
                    LogHelper::error('No valid phone number found for WhatsApp notification.');
                }

                // Mengirim notifikasi ke pengaju tentang tahap approval
                $pengajuPhone = $data->dataUser->telephone;
                if ($pengajuPhone) {
                    $message = "Halo {$data->dataUser->name},\n\n";
                    $message .= "Status pengajuan bahan Anda dengan Kode Transaksi {$data->kode_transaksi} telah disetujui oleh Leader.\n\n";

                    $message .= "Pesan Otomatis:\nhttps://inventory.beacontelemetry.com/";
                    SendWhatsAppApproveLeader::dispatch($pengajuPhone, $message);
                    LogHelper::success("Pesan sedang dikirim.");
                }  else {
                    LogHelper::error('No valid phone number found for pengaju.');
                }
            }
            DB::commit();
            LogHelper::success("Status approval leader berhasil diubah.");
            return redirect()->route('bahan-keluars.index')->with('success', 'Status approval leader berhasil diubah.');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan. Pesan error: $errorMessage");
        }
    }

    // public function update1(Request $request, string $id)
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
    //         LogHelper::success('Berhasil Mengubah Status Bahan Keluar!');
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

    //     return redirect()->route('bahan-keluars.index')->with('success', 'Status berhasil diubah.');
    // }

    public function sendWhatsApp($id)
    {
        SendWhatsAppMessage::dispatch($id);
        LogHelper::success("Pesan sedang dikirim.");
        return redirect()->back()->with('success', 'Pesan sedang dikirim.');
    }

    public function updatepengambilan(Request $request, string $id)
    {
        $validated = $request->validate([
            'status_pengambilan' => 'required|string|in:Belum Diambil,Sudah Diambil',
        ]);
        try {
            $data = BahanKeluar::findOrFail($id);
            // Jika status_pengambilan sudah diubah menjadi 'Sudah Diambil', update tgl_keluar
            if ($validated['status_pengambilan'] === 'Sudah Diambil') {
                $tgl_keluar = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                $data->tgl_keluar = $tgl_keluar;
            }
            // Update status_pengambilan
            $data->status_pengambilan = $validated['status_pengambilan'];
            $data->save();
            LogHelper::success('Berhasil Mengubah Status pengambilan Bahan Keluar!');
            return redirect()->route('bahan-keluars.index')->with('success', 'Status pengambilan berhasil diubah.');
        } catch (\Exception $e) {
            LogHelper::error("Error updating status pengambilan: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengubah status.');
        }
    }


    public function destroy(string $id)
    {
        try{
            $data = BahanKeluar::find($id);
            if (!$data) {
                return redirect()->back()->with('gagal', 'Transaksi tidak ditemukan.');
            }
            $data->delete();
            LogHelper::success('Berhasil Menghapus Pengajuan Bahan Keluar!');
            return redirect()->route('bahan-keluars.index')->with('success', 'Berhasil Menghapus Pengajuan Bahan Keluar!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
