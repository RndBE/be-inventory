<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\User;
use App\Models\Purchase;
use App\Helpers\LogHelper;
use App\Models\BahanRetur;
use Illuminate\Http\Request;
use App\Models\ProjekDetails;
use App\Models\PurchaseDetail;
use App\Models\ProduksiDetails;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PengajuanDetails;
use App\Models\ProjekRndDetails;
use App\Models\BahanReturDetails;
use App\Models\BahanSetengahjadi;
use Illuminate\Support\Facades\DB;
use App\Models\GaransiProjekDetails;
use App\Models\PengambilanBahanDetails;
use App\Models\BahanSetengahjadiDetails;

class BahanReturController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-bahan-retur', ['only' => ['index']]);
        $this->middleware('permission:detail-bahan-retur', ['only' => ['show']]);
        $this->middleware('permission:edit-bahan-retur', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-bahan-retur', ['only' => ['destroy']]);
    }

    public function index()
    {
        $bahan_returs = BahanRetur::with('bahanReturDetails')->get();
        return view('pages.bahan-returs.index', compact('bahan_returs'));
    }


    public function downloadPdf(int $id)
    {
        try {
            $bahanRetur = BahanRetur::with([
                'produksiS','projek','garansiProjek','projekRnd','pengajuan','pengambilanBahan',
                'bahanReturDetails.dataBahan.dataUnit',
                'bahanReturDetails.dataProduk',
            ])->findOrFail($id);

            $hasProduk = $bahanRetur->bahanReturDetails->filter(function ($detail) {
                return !empty($detail->dataProduk) && !empty($detail->dataProduk->id);
            })->isNotEmpty();

            $tandaTanganPengaju = $bahanRetur->dataUser->tanda_tangan ?? null;

            $tandaTanganLeader = null;
            $tandaTanganManager = $bahanRetur->dataUser->atasanLevel2->tanda_tangan ?? null;
            $tandaTanganDirektur = $bahanRetur->dataUser->atasanLevel1->tanda_tangan ?? null;

            if ($bahanRetur->produksiS) {
                $pengaju = $bahanRetur->produksiS->pengaju ?? null;
            } elseif ($bahanRetur->projek) {
                $pengaju = $bahanRetur->projek->pengaju ?? null;
            }elseif ($bahanRetur->garansiProjek) {
                $pengaju = $bahanRetur->garansiProjek->pengaju ?? null;
            }elseif ($bahanRetur->projekRnd) {
                $pengaju = $bahanRetur->projekRnd->pengaju ?? null;
            }elseif ($bahanRetur->pengajuan) {
                $pengaju = $bahanRetur->pengajuan->pengaju ?? null;
            }elseif ($bahanRetur->pengambilanBahan) {
                $pengaju = $bahanRetur->pengambilanBahan->pengaju ?? null;
            }

            if ($pengaju) {
                // Cari user berdasarkan nama
                $user = User::where('name', $pengaju)->first();

                if ($user && $user->atasanLevel2) {
                    $atasanLevel2 = $user->atasanLevel2->name;
                }
            }

            // $leaderName = $bahanRetur->dataUser->atasanLevel3 ? $bahanRetur->dataUser->atasanLevel3->name : null;
            // $managerName = $bahanRetur->dataUser->atasanLevel2 ? $bahanRetur->dataUser->atasanLevel2->name : null;

            // if (!$leaderName && $managerName) {
            //     $leaderName = $managerName;
            // }

            $purchasingUser = cache()->remember('purchasing_user', 60, function () {
                return User::where('job_level', 3)
                    ->whereHas('dataJobPosition', function ($query) {
                        $query->where('nama', 'Purchasing');
                    })->first();
            });

            $hardwareManager = cache()->remember('hardware_manager', 60, function () {
                return User::where('job_level', 2)
                    ->whereHas('dataJobPosition', function ($query) {
                        $query->where('nama', 'Engineer Manager');
                    })->first();
            });

            $tandaTanganPurchasing = $purchasingUser->tanda_tangan ?? null;
            $namaManager = $hardwareManager->name ?? null;

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

            $pdf = Pdf::loadView('pages.bahan-returs.pdf', compact(
                'bahanRetur',
                'purchasingUser',
                'pengaju',
                'adminManagerceUser',
                'atasanLevel2',
                'namaManager',
                'hasProduk'
            ))->setPaper('letter', 'portrait');
            return $pdf->stream("bahan_keluar_{$id}.pdf");

            LogHelper::success('Berhasil generating PDF for BahanRetur ID {$id}!');
            return $pdf->download("bahan_keluar_{$id}.pdf");

        } catch (\Exception $e) {
            LogHelper::error("Error generating PDF for BahanRetur ID {$id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengunduh PDF.');
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            DB::beginTransaction();
            $validated = $request->validate([
                'status' => 'required',
            ]);

            $bahanRetur = BahanRetur::findOrFail($id);
            $bahanReturDetails = BahanReturDetails::where('bahan_retur_id', $id)->get();

            if ($validated['status'] === 'Disetujui') {
                $groupedDetails = [];

                // Langkah 1: Kelompokkan bahanReturDetails berdasarkan bahan_id dan unit_price
                foreach ($bahanReturDetails as $returDetail) {
                    $key = $returDetail->bahan_id ?? $returDetail->produk_id;
                    $unitPrice = $returDetail->unit_price;
                    $serialNumber = $returDetail->serial_number ?? null;

                    if (!isset($groupedDetails[$key][$unitPrice])) {
                        $groupedDetails[$key][$unitPrice] = [
                            'bahan_id' => $returDetail->bahan_id,
                            'produk_id' => $returDetail->produk_id,
                            'qty' => 0,
                            'unit_price' => $unitPrice,
                            'serial_number' => $serialNumber,
                        ];
                    }
                    $groupedDetails[$key][$unitPrice]['qty'] += $returDetail->qty;
                }

                // Langkah 2: Kurangi qty di ProduksiDetails dan ProjekDetails sesuai groupedDetails
                foreach ($groupedDetails as $bahanId => $detailsByPrice) {
                    // Update untuk ProduksiDetails
                    $produksiDetail = ProduksiDetails::where('produksi_id', $bahanRetur->produksi_id)
                        ->where('bahan_id', $bahanId)
                        ->first();

                    if ($produksiDetail) {
                        $currentDetails = json_decode($produksiDetail->details, true) ?? [];

                        foreach ($detailsByPrice as $unitPrice => $qtyData) {
                            foreach ($currentDetails as $key => &$entry) {
                                if ($entry['unit_price'] == $unitPrice) {
                                    $entry['qty'] -= $qtyData['qty'];
                                    if ($entry['qty'] <= 0) unset($currentDetails[$key]);
                                    break;
                                }
                            }
                        }

                        // Kurangi qty dan used_materials pada ProduksiDetails
                        $totalQtyReduction = array_sum(array_column($detailsByPrice, 'qty'));
                        $produksiDetail->qty -= $totalQtyReduction;
                        $produksiDetail->used_materials -= $totalQtyReduction;

                        // Pastikan qty dan used_materials tidak negatif
                        $produksiDetail->qty = max(0, $produksiDetail->qty);
                        $produksiDetail->used_materials = max(0, $produksiDetail->used_materials);

                        $produksiDetail->sub_total = 0;
                        foreach ($currentDetails as $detail) {
                            $produksiDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                        }

                        $produksiDetail->details = json_encode(array_values($currentDetails));
                        $produksiDetail->save();
                    }

                    // Update untuk ProjekDetails
                    $projekDetail = ProjekDetails::where('projek_id', $bahanRetur->projek_id)
                    ->where(function ($query) use ($bahanId) {
                        $query->where('bahan_id', $bahanId)
                                ->orWhere('produk_id', $bahanId);
                    })
                    ->first();

                    if ($projekDetail) {
                        $currentDetails = json_decode($projekDetail->details, true) ?? [];

                        foreach ($detailsByPrice as $unitPrice => $qtyData) {
                            foreach ($currentDetails as $key => &$entry) {
                                if ($entry['unit_price'] == $unitPrice) {
                                    $entry['qty'] -= $qtyData['qty'];
                                    // Jika qty menjadi 0 atau negatif, hapus entry dari details
                                    if ($entry['qty'] <= 0) {
                                        unset($currentDetails[$key]);
                                    }
                                    break;
                                }
                            }
                        }

                        $totalQtyReduction = array_sum(array_column($detailsByPrice, 'qty'));
                        $projekDetail->qty = max(0, $projekDetail->qty - $totalQtyReduction);
                        $projekDetail->used_materials = max(0, $projekDetail->used_materials - $totalQtyReduction);

                        // Hitung ulang sub_total hanya jika masih ada entri di details
                        if (!empty($currentDetails)) {
                            $projekDetail->sub_total = 0;
                            foreach ($currentDetails as $detail) {
                                $projekDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                            }

                            // Simpan details yang sudah diperbarui
                            $projekDetail->details = json_encode(array_values($currentDetails));
                            $projekDetail->save();
                        } else {
                            // Jika tidak ada entry tersisa, hapus ProjekDetails
                            $projekDetail->delete();
                        }
                    }

                    // Update untuk GaransiProjekDetails
                    $garansiProjekDetail = GaransiProjekDetails::where('garansi_projek_id', $bahanRetur->garansi_projek_id)
                    ->where(function ($query) use ($bahanId) {
                        $query->where('bahan_id', $bahanId)
                                ->orWhere('produk_id', $bahanId);
                    })
                    ->first();

                    if ($garansiProjekDetail) {
                        $currentDetails = json_decode($garansiProjekDetail->details, true) ?? [];

                        foreach ($detailsByPrice as $unitPrice => $qtyData) {
                            foreach ($currentDetails as $key => &$entry) {
                                if ($entry['unit_price'] == $unitPrice) {
                                    $entry['qty'] -= $qtyData['qty'];
                                    // Jika qty menjadi 0 atau negatif, hapus entry dari details
                                    if ($entry['qty'] <= 0) {
                                        unset($currentDetails[$key]);
                                    }
                                    break;
                                }
                            }
                        }

                        $totalQtyReduction = array_sum(array_column($detailsByPrice, 'qty'));
                        $garansiProjekDetail->qty = max(0, $garansiProjekDetail->qty - $totalQtyReduction);
                        $garansiProjekDetail->used_materials = max(0, $garansiProjekDetail->used_materials - $totalQtyReduction);

                        // Hitung ulang sub_total hanya jika masih ada entri di details
                        if (!empty($currentDetails)) {
                            $garansiProjekDetail->sub_total = 0;
                            foreach ($currentDetails as $detail) {
                                $garansiProjekDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                            }

                            // Simpan details yang sudah diperbarui
                            $garansiProjekDetail->details = json_encode(array_values($currentDetails));
                            $garansiProjekDetail->save();
                        } else {
                            // Jika tidak ada entry tersisa, hapus GaransiProjekDetails
                            $garansiProjekDetail->delete();
                        }
                    }

                    // Update untuk GaransiProjekDetails
                    $projekRndDetail = ProjekRndDetails::where('projek_rnd_id', $bahanRetur->projek_rnd_id)
                    ->where(function ($query) use ($bahanId) {
                        $query->where('bahan_id', $bahanId)
                                ->orWhere('produk_id', $bahanId);
                    })
                    ->first();

                    if ($projekRndDetail) {
                        $currentDetails = json_decode($projekRndDetail->details, true) ?? [];

                        foreach ($detailsByPrice as $unitPrice => $qtyData) {
                            foreach ($currentDetails as $key => &$entry) {
                                if ($entry['unit_price'] == $unitPrice) {
                                    $entry['qty'] -= $qtyData['qty'];
                                    if ($entry['qty'] <= 0) {
                                        unset($currentDetails[$key]);
                                    }
                                    break;
                                }
                            }
                        }

                        $totalQtyReduction = array_sum(array_column($detailsByPrice, 'qty'));
                        $projekRndDetail->qty -= $totalQtyReduction;
                        $projekRndDetail->used_materials -= $totalQtyReduction;

                        $projekRndDetail->qty = max(0, $projekRndDetail->qty);
                        $projekRndDetail->used_materials = max(0, $projekRndDetail->used_materials);

                        // Hitung ulang sub_total hanya jika masih ada entri di details
                        if (!empty($currentDetails)) {
                            $projekRndDetail->sub_total = 0;
                            foreach ($currentDetails as $detail) {
                                $projekRndDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                            }

                            // Simpan details yang sudah diperbarui
                            $projekRndDetail->details = json_encode(array_values($currentDetails));
                            $projekRndDetail->save();
                        } else {
                            // Jika tidak ada entry tersisa, hapus projekRndDetails
                            $projekRndDetail->delete();
                        }
                    }

                    // Update untuk PengajuanDetails
                    $pengajuanDetail = PengajuanDetails::where('pengajuan_id', $bahanRetur->pengajuan_id)
                        ->where('bahan_id', $bahanId)
                        ->first();

                    if ($pengajuanDetail) {
                        $currentDetails = json_decode($pengajuanDetail->details, true) ?? [];

                        foreach ($detailsByPrice as $unitPrice => $qtyData) {
                            foreach ($currentDetails as $key => &$entry) {
                                if ($entry['unit_price'] == $unitPrice) {
                                    $entry['qty'] -= $qtyData['qty'];
                                    if ($entry['qty'] <= 0) {
                                        unset($currentDetails[$key]);
                                    }
                                    break;
                                }
                            }
                        }

                        $totalQtyReduction = array_sum(array_column($detailsByPrice, 'qty'));
                        $pengajuanDetail->qty -= $totalQtyReduction;
                        $pengajuanDetail->used_materials -= $totalQtyReduction;

                        $pengajuanDetail->qty = max(0, $pengajuanDetail->qty);
                        $pengajuanDetail->used_materials = max(0, $pengajuanDetail->used_materials);

                        // Hitung ulang sub_total hanya jika masih ada entri di details
                        if (!empty($currentDetails)) {
                            $pengajuanDetail->sub_total = 0;
                            foreach ($currentDetails as $detail) {
                                $pengajuanDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                            }

                            // Simpan details yang sudah diperbarui
                            $pengajuanDetail->details = json_encode(array_values($currentDetails));
                            $pengajuanDetail->save();
                        } else {
                            // Jika tidak ada entry tersisa, hapus pengajuanDetails
                            $pengajuanDetail->delete();
                        }
                    }

                    // Update untuk PengambilanBahanDetails
                    $pengambilanBahanDetail = PengambilanBahanDetails::where('pengambilan_bahan_id', $bahanRetur->pengambilan_bahan_id)
                        ->where('bahan_id', $bahanId)
                        ->first();
                    if ($pengambilanBahanDetail) {
                        $currentDetails = json_decode($pengambilanBahanDetail->details, true) ?? [];
                        foreach ($detailsByPrice as $unitPrice => $qtyData) {
                            foreach ($currentDetails as $key => &$entry) {
                                if ($entry['unit_price'] == $unitPrice) {
                                    $entry['qty'] -= $qtyData['qty'];
                                    if ($entry['qty'] <= 0) {
                                        unset($currentDetails[$key]);
                                    }
                                    break;
                                }
                            }
                        }
                        $totalQtyReduction = array_sum(array_column($detailsByPrice, 'qty'));
                        $pengambilanBahanDetail->qty -= $totalQtyReduction;
                        $pengambilanBahanDetail->used_materials -= $totalQtyReduction;
                        $pengambilanBahanDetail->qty = max(0, $pengambilanBahanDetail->qty);
                        $pengambilanBahanDetail->used_materials = max(0, $pengambilanBahanDetail->used_materials);

                        // Hitung ulang sub_total hanya jika masih ada entri di details
                        if (!empty($currentDetails)) {
                            $pengambilanBahanDetail->sub_total = 0;
                            foreach ($currentDetails as $detail) {
                                $pengambilanBahanDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                            }

                            // Simpan details yang sudah diperbarui
                            $pengambilanBahanDetail->details = json_encode(array_values($currentDetails));
                            $pengambilanBahanDetail->save();
                        } else {
                            // Jika tidak ada entry tersisa, hapus pengambilanBahanDetails
                            $pengambilanBahanDetail->delete();
                        }
                    }
                }

                foreach ($bahanReturDetails as $returDetail) {
                    // dd($bahanRetur->kode_transaksi);
                    if ($returDetail->produk_id) { // Jika produk_id ada, kembalikan ke BahanSetengahjadi
                        $bahanSetengahJadi = BahanSetengahjadi::firstOrCreate([
                            'kode_transaksi' => $bahanRetur->kode_transaksi,
                            'tgl_masuk' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        ]);

                        BahanSetengahjadiDetails::create([
                            'bahan_setengahjadi_id' => $bahanSetengahJadi->id,
                            'nama_bahan' => $returDetail->dataProduk->nama_bahan,
                            'serial_number' => $returDetail->serial_number,
                            'qty' => $returDetail->qty,
                            'sisa' => $returDetail->qty,
                            'unit_price' => $returDetail->unit_price,
                            'sub_total' => $returDetail->qty * $returDetail->unit_price,
                        ]);
                    } else { // Jika bahan_id ada, tambahkan ke Purchase seperti biasa
                        $purchase = Purchase::firstOrCreate(
                            ['kode_transaksi' => $bahanRetur->kode_transaksi],
                            ['tgl_masuk' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')]
                        );

                        PurchaseDetail::create([
                            'purchase_id' => $purchase->id,
                            'bahan_id' => $returDetail->bahan_id,
                            'qty' => $returDetail->qty,
                            'sisa' => $returDetail->qty,
                            'unit_price' => $returDetail->unit_price,
                            'sub_total' => $returDetail->qty * $returDetail->unit_price,
                        ]);
                    }
                }

                $bahanRetur->status = $validated['status'];
                $bahanRetur->tgl_diterima = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                $bahanRetur->save();
                DB::commit();
                LogHelper::success('Berhasil Mengubah Status Bahan Retur!');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Pesan error: $errorMessage");
        }
        return redirect()->route('bahan-returs.index')->with('success', 'Status berhasil diubah.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $data = BahanRetur::find($id);
            if (!$data) {
                return redirect()->back()->with('gagal', 'Transaksi tidak ditemukan.');
            }
            $data->delete();
            LogHelper::success('Berhasil Menghapus Pengajuan Bahan Retur!');
            return redirect()->route('bahan-returs.index')->with('success', 'Berhasil Menghapus Pengajuan Bahan Retur!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
