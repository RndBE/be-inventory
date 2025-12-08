<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\User;
use App\Helpers\LogHelper;
use App\Models\BahanRusak;
use Illuminate\Http\Request;
use App\Models\ProjekDetails;
use App\Models\ProduksiDetails;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PengajuanDetails;
use App\Models\ProjekRndDetails;
use App\Models\BahanRusakDetails;
use Illuminate\Support\Facades\DB;
use App\Models\ProdukSampleDetails;
use App\Models\GaransiProjekDetails;
use App\Models\PengambilanBahanDetails;
use App\Models\ProduksiProdukJadiDetails;

class BahanRusakController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-bahan-rusak', ['only' => ['index']]);
        $this->middleware('permission:detail-bahan-rusak', ['only' => ['show']]);
        $this->middleware('permission:edit-bahan-rusak', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-bahan-rusak', ['only' => ['destroy']]);
    }

    public function downloadPdf(int $id)
    {
        try {
            $bahanRusak = BahanRusak::with([
                'produksiS','projek','garansiProjek','projekRnd','pengajuan','pengambilanBahan','produkSample',
                'bahanRusakDetails.dataBahan.dataUnit',
                'bahanRusakDetails.dataProduk', 'produksiProdukJadi',
            ])->findOrFail($id);

            // $hasProduk = $bahanRusak->bahanRusakDetails->filter(function ($detail) {
            //     return !empty($detail->dataProduk) && !empty($detail->dataProduk->id);
            // })->isNotEmpty();
            $hasProduk = $bahanRusak->bahanRusakDetails->filter(function ($detail) {
                return (
                    (!empty($detail->dataProduk) && !empty($detail->dataProduk->id)) ||
                    (!empty($detail->dataProdukJadi) && !empty($detail->dataProdukJadi->id))
                );
            })->isNotEmpty();


            $tandaTanganPengaju = $bahanRusak->dataUser->tanda_tangan ?? null;

            $tandaTanganLeader = null;
            $tandaTanganManager = $bahanRusak->dataUser->atasanLevel2->tanda_tangan ?? null;
            $tandaTanganDirektur = $bahanRusak->dataUser->atasanLevel1->tanda_tangan ?? null;

            // Default null
            $pengaju = null;
            $divisi = null;
            $tujuan = null;

            if ($bahanRusak->produksiS) {
                $pengaju = $bahanRusak->produksiS->pengaju ?? null;
                $tujuan = 'Produksi '. $bahanRusak->produksiS->dataBahan->nama_bahan .'/'.$bahanRusak->produksiS->keterangan ?? null;
            } elseif ($bahanRusak->projek) {
                $pengaju = $bahanRusak->projek->pengaju ?? null;
                $tujuan = $bahanRusak->projek->dataKontrak->nama_kontrak .'/'. $bahanRusak->projek->keterangan ?? null;
            }elseif ($bahanRusak->produksiProdukJadi) {
                $pengaju = $bahanRusak->produksiProdukJadi->pengaju ?? null;
                $tujuan = 'Produksi '. $bahanRusak->produksiProdukJadi->dataProdukJadi->nama_produk .'/'. $bahanRusak->produksiProdukJadi->keterangan ?? null;
            }elseif ($bahanRusak->garansiProjek) {
                $pengaju = $bahanRusak->garansiProjek->pengaju ?? null;
                $tujuan = $bahanRusak->garansiProjek->dataKontrak->nama_kontrak .'/'. $bahanRusak->garansiProjek->keterangan ?? null;
            }elseif ($bahanRusak->projekRnd) {
                $pengaju = $bahanRusak->projekRnd->pengaju ?? null;
                $tujuan = $bahanRusak->projekRnd->nama_projek_rnd.'/'.$bahanRusak->projekRnd->keterangan ?? null;
            }elseif ($bahanRusak->pengajuan) {
                $pengaju = $bahanRusak->pengajuan->pengaju ?? null;
                $tujuan = $bahanRusak->pengajuan->tujuan ?? null;
            }elseif ($bahanRusak->pengambilanBahan) {
                $pengaju = $bahanRusak->pengambilanBahan->pengaju ?? null;
                $tujuan = $bahanRusak->pengambilanBahan->project.'/'.$bahanRusak->pengambilanBahan->keterangan ?? null;
            }elseif ($bahanRusak->produkSample) {
                $pengaju = $bahanRusak->produkSample->pengaju ?? null;
                $tujuan = $bahanRusak->produkSample->nama_produk_sample.'/'.$bahanRusak->produkSample->keterangan ?? null;
            }

            if ($pengaju) {
                // Cari user berdasarkan nama
                $user = User::where('name', $pengaju)->first();

                if ($user && $user->atasanLevel2) {
                    $atasanLevel2 = $user->atasanLevel2->name;
                    $divisi = $user->dataJobPosition->nama ?? $user->divisi ?? null;
                }
            }

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
                return User::where('name', 'MARITZA ISYAURA PUTRI RIZMA')->first();
            });
            $tandaTanganFinance = $financeUser->tanda_tangan ?? null;

            $adminManagerceUser = cache()->remember('admin_manager_user', 60, function () {
                return User::where('job_level', 2)
                    ->whereHas('dataJobPosition', function ($query) {
                        $query->where('nama', 'Admin Manager');
                    })->first();
            });
            $tandaTanganAdminManager = $adminManagerceUser->tanda_tangan ?? null;

            $pdf = Pdf::loadView('pages.bahan-rusaks.pdf', compact(
                'bahanRusak',
                'purchasingUser',
                'pengaju',
                'divisi',
                'tujuan',
                'adminManagerceUser',
                'atasanLevel2',
                'namaManager',
                'hasProduk'
            ))->setPaper('letter', 'portrait');
            return $pdf->stream("bahan_rusak_{$id}.pdf");

            LogHelper::success('Berhasil generating PDF for bahan rusak ID {$id}!');
            return $pdf->download("bahan_rusak_{$id}.pdf");

        } catch (\Exception $e) {
            LogHelper::error("Error generating PDF for bahan rusak ID {$id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengunduh PDF.');
        }
    }

    public function index()
    {
        $bahanRusaks = BahanRusak::with('bahanRusakDetails')->get();
        return view('pages.bahan-rusaks.index', compact('bahanRusaks'));
    }

    public function show($id)
    {
        $bahanRusak = BahanRusak::with('bahanRusakDetails.dataBahan.dataUnit')->findOrFail($id);
        return view('pages.bahan-rusaks.show', [
            'kode_transaksi' => $bahanRusak->kode_transaksi,
            'tgl_pengajuan' => $bahanRusak->tgl_pengajuan ?? null,
            'tgl_diterima' => $bahanRusak->tgl_diterima ?? null,
            'kode_produksi' => $bahanRusak->produksiS ? $bahanRusak->produksiS->kode_produksi : null,
            'kode_produksi_produk_jadi' => $bahanRusak->produksiProdukJadi ? $bahanRusak->produksiProdukJadi->kode_produksi : null,
            'kode_projek' => $bahanRusak->projek ? $bahanRusak->projek->kode_projek : null,
            'kode_projek_rnd' => $bahanRusak->projekRnd ? $bahanRusak->projekRnd->kode_projek_rnd : null,
            'kode_produk_sample' => $bahanRusak->produkSample ? $bahanRusak->produkSample->kode_produk_sample : null,
            'bahanRusakDetails' => $bahanRusak->bahanRusakDetails,
        ]);
    }

    public function update(Request $request, string $id)
    {
        try {
            DB::beginTransaction();
            $validated = $request->validate([
                'status' => 'required',
            ]);

            $bahanRusak = BahanRusak::findOrFail($id);
            $bahanRusakDetails = BahanRusakDetails::where('bahan_rusak_id', $id)->get();

            // Jika status bahan rusak disetujui
            if ($validated['status'] === 'Disetujui') {
                $groupedDetails = [];

                // Langkah 1: Kelompokkan BahanRusakDetails berdasarkan bahan_id dan unit_price
                foreach ($bahanRusakDetails as $rusakDetail) {
                    $key = $rusakDetail->bahan_id ?? $rusakDetail->produk_id ?? $rusakDetail->produk_jadis_id;
                    $unitPrice = $rusakDetail->unit_price;
                    $serialNumber = $rusakDetail->serial_number ?? null;

                    if (!isset($groupedDetails[$key][$unitPrice])) {
                        $groupedDetails[$key][$unitPrice] = [
                            'bahan_id' => $rusakDetail->bahan_id,
                            'produk_id' => $rusakDetail->produk_id,
                            'produk_jadis_id' => $rusakDetail->produk_jadis_id,
                            'qty' => 0,
                            'unit_price' => $unitPrice,
                            'serial_number' => $serialNumber,
                        ];
                    }
                    $groupedDetails[$key][$unitPrice]['qty'] += $rusakDetail->qty;
                }

                // Langkah 2: Kurangi qty di ProduksiDetails dan ProjekDetails sesuai groupedDetails
                foreach ($groupedDetails as $bahanId => $detailsByPrice) {
                    // Update untuk ProduksiDetails
                    $produksiDetail = ProduksiDetails::where('produksi_id', $bahanRusak->produksi_id)
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

                    // Update untuk ProduksiProdukJadiDetails
                    $produksiProdukJadiDetail = ProduksiProdukJadiDetails::where('produksi_produk_jadi_id', $bahanRusak->produksi_produk_jadi_id)
                        ->where(function ($query) use ($bahanId) {
                            $query->where('bahan_id', $bahanId)
                                    ->orWhere('produk_id', $bahanId);
                        })
                        ->first();

                    if ($produksiProdukJadiDetail) {
                        $currentDetails = json_decode($produksiProdukJadiDetail->details, true) ?? [];

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
                        $produksiProdukJadiDetail->qty -= $totalQtyReduction;
                        $produksiProdukJadiDetail->used_materials -= $totalQtyReduction;

                        // Pastikan qty dan used_materials tidak negatif
                        $produksiProdukJadiDetail->qty = max(0, $produksiProdukJadiDetail->qty);
                        $produksiProdukJadiDetail->used_materials = max(0, $produksiProdukJadiDetail->used_materials);

                        // Hitung ulang sub_total hanya jika masih ada entri di details
                        if (!empty($currentDetails)) {
                            $produksiProdukJadiDetail->sub_total = 0;
                            foreach ($currentDetails as $detail) {
                                $produksiProdukJadiDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                            }

                            // Simpan details yang sudah diperbarui
                            $produksiProdukJadiDetail->details = json_encode(array_values($currentDetails));
                            $produksiProdukJadiDetail->save();
                        } else {
                            // Jika tidak ada entry tersisa, hapus ProduksiProdukJadiDetails
                            $produksiProdukJadiDetail->delete();
                        }
                    }

                    // Update untuk ProjekDetails
                    $projekDetail = ProjekDetails::where('projek_id', $bahanRusak->projek_id)
                        ->where(function ($query) use ($bahanId) {
                            $query->where('bahan_id', $bahanId)
                                    ->orWhere('produk_id', $bahanId)
                                    ->orWhere('produk_jadis_id', $bahanId);
                        })
                        ->first();

                    if ($projekDetail) {
                        $currentDetails = json_decode($projekDetail->details, true) ?? [];

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
                        $projekDetail->qty -= $totalQtyReduction;
                        $projekDetail->used_materials -= $totalQtyReduction;

                        // Pastikan qty dan used_materials tidak negatif
                        $projekDetail->qty = max(0, $projekDetail->qty);
                        $projekDetail->used_materials = max(0, $projekDetail->used_materials);

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
                    $garansiProjekDetail = GaransiProjekDetails::where('garansi_projek_id', $bahanRusak->garansi_projek_id)
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
                                    if ($entry['qty'] <= 0) {
                                        unset($currentDetails[$key]);
                                    }
                                    break;
                                }
                            }
                        }

                        $totalQtyReduction = array_sum(array_column($detailsByPrice, 'qty'));
                        $garansiProjekDetail->qty -= $totalQtyReduction;
                        $garansiProjekDetail->used_materials -= $totalQtyReduction;

                        // Pastikan qty dan used_materials tidak negatif
                        $garansiProjekDetail->qty = max(0, $garansiProjekDetail->qty);
                        $garansiProjekDetail->used_materials = max(0, $garansiProjekDetail->used_materials);

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

                    // Update untuk ProjekRndDetails
                    $projekRndDetail = ProjekRndDetails::where('projek_rnd_id', $bahanRusak->projek_rnd_id)
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

                        // Pastikan qty dan used_materials tidak negatif
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

                    // // Update untuk PengajuanDetails
                    // $pengajuanDetail = PengajuanDetails::where('pengajuan_id', $bahanRusak->pengajuan_id)
                    //     ->where('bahan_id', $bahanId)
                    //     ->first();

                    // if ($pengajuanDetail) {
                    //     $currentDetails = json_decode($pengajuanDetail->details, true) ?? [];

                    //     foreach ($detailsByPrice as $unitPrice => $qtyData) {
                    //         foreach ($currentDetails as $key => &$entry) {
                    //             if ($entry['unit_price'] == $unitPrice) {
                    //                 $entry['qty'] -= $qtyData['qty'];
                    //                 if ($entry['qty'] <= 0) {
                    //                     unset($currentDetails[$key]);
                    //                 }
                    //                 break;
                    //             }
                    //         }
                    //     }

                    //     $totalQtyReduction = array_sum(array_column($detailsByPrice, 'qty'));
                    //     $pengajuanDetail->qty -= $totalQtyReduction;
                    //     $pengajuanDetail->used_materials -= $totalQtyReduction;

                    //     // Pastikan qty dan used_materials tidak negatif
                    //     $pengajuanDetail->qty = max(0, $pengajuanDetail->qty);
                    //     $pengajuanDetail->used_materials = max(0, $pengajuanDetail->used_materials);

                    //     // Hitung ulang sub_total hanya jika masih ada entri di details
                    //     if (!empty($currentDetails)) {
                    //         $pengajuanDetail->sub_total = 0;
                    //         foreach ($currentDetails as $detail) {
                    //             $pengajuanDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                    //         }

                    //         // Simpan details yang sudah diperbarui
                    //         $pengajuanDetail->details = json_encode(array_values($currentDetails));
                    //         $pengajuanDetail->save();
                    //     } else {
                    //         // Jika tidak ada entry tersisa, hapus pengajuanDetails
                    //         $pengajuanDetail->delete();
                    //     }
                    // }

                    // Update untuk PengambilanBahanDetails
                    $pengambilanBahanDetail = PengambilanBahanDetails::where('pengambilan_bahan_id', $bahanRusak->pengambilan_bahan_id)
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

                        // Pastikan qty dan used_materials tidak negatif
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

                    $produkSampleDetail = ProdukSampleDetails::where('produk_sample_id', $bahanRusak->produk_sample_id)
                        ->where(function ($query) use ($bahanId) {
                            $query->where('bahan_id', $bahanId)
                                    ->orWhere('produk_id', $bahanId)
                                    ->orWhere('produk_jadis_id', $bahanId);
                        })
                        ->first();

                    if ($produkSampleDetail) {
                        $currentDetails = json_decode($produkSampleDetail->details, true) ?? [];

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
                        $produkSampleDetail->qty -= $totalQtyReduction;
                        $produkSampleDetail->used_materials -= $totalQtyReduction;

                        // Pastikan qty dan used_materials tidak negatif
                        $produkSampleDetail->qty = max(0, $produkSampleDetail->qty);
                        $produkSampleDetail->used_materials = max(0, $produkSampleDetail->used_materials);

                        // Hitung ulang sub_total hanya jika masih ada entri di details
                        if (!empty($currentDetails)) {
                            $produkSampleDetail->sub_total = 0;
                            foreach ($currentDetails as $detail) {
                                $produkSampleDetail->sub_total += $detail['qty'] * $detail['unit_price'];
                            }

                            // Simpan details yang sudah diperbarui
                            $produkSampleDetail->details = json_encode(array_values($currentDetails));
                            $produkSampleDetail->save();
                        } else {
                            // Jika tidak ada entry tersisa, hapus ProjekDetails
                            $produkSampleDetail->delete();
                        }
                    }
                }
                foreach ($bahanRusakDetails as $rusakDetail) {
                    $rusakDetail->sisa = $rusakDetail->qty;
                    $rusakDetail->save();
                }
            }
            // Update status bahan rusak
            $bahanRusak->status = $validated['status'];
            $bahanRusak->tgl_diterima = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $bahanRusak->save();
            DB::commit();
            LogHelper::success('Berhasil Mengubah Status Bahan Rusak!');
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Pesan error: $errorMessage");
        }

        return redirect()->route('bahan-rusaks.index')->with('success', 'Status berhasil diubah.');
    }



    public function destroy(string $id)
    {
        try{
            $data = BahanRusak::find($id);
            if (!$data) {
                return redirect()->back()->with('gagal', 'Transaksi tidak ditemukan.');
            }
            $data->delete();
            LogHelper::success('Berhasil Menghapus Pengajuan Bahan Rusak!');
            return redirect()->route('bahan-rusaks.index')->with('success', 'Berhasil Menghapus Pengajuan Bahan Rusak!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

}
