<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use App\Models\Pengajuan;
use App\Models\BahanKeluar;
use Livewire\WithPagination;
use App\Models\PembelianBahan;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PembelianBahanTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 25;
    public $id_pembelian_bahan, $status, $gambar, $nama_bahan, $kode_bahan, $jenis_bahan_id, $stok_awal,  $unit_id, $total_stok,  $penempatan, $supplier,
        $kode_transaksi, $tgl_keluar, $divisi, $link, $pembelianBahanDetails, $status_pengambilan, $status_leader, $status_purchasing, $status_manager, $status_finance, $status_admin_manager, $ongkir, $asuransi, $layanan, $jasa_aplikasi, $shipping_cost, $full_amount_fee, $value_today_fee, $jenis_pengajuan, $new_shipping_cost, $new_full_amount_fee, $ppn, $new_value_today_fee, $status_general_manager, $catatan, $dokumen;

    public $filter = 'semua';
    public $totalHarga;
    public $isShowModalOpen = false;
    public $isDeleteModalOpen = false;
    public $isApproveLeaderModalOpen = false;
    public $isApproveGMModalOpen = false;
    public $isApproveManagerModalOpen = false;
    public $isApprovePurchasingModalOpen = false;
    public $isApproveAdminManagerModalOpen = false;
    public $isApproveFinanceModalOpen = false;
    public $isApproveDirekturModalOpen = false;
    public $isShowInvoiceModalOpen = false;
    public $isUploadInvoiceModalOpen = false;
    public $isUploadDokumenModalOpen = false;
    public $pembelian_bahan;
    public $selectedStatus = [];
    public $selectedTab = 'semua';
    public $isDetailOpen = false;
    public $detailTransaksi;
    public $statusList = [];
    public $dateList = [];
    public $timeDiffs = [];
    public $currentPage;

    public function mount()
    {
        $this->resetModalState();
        $this->calculateTotalHarga();
        foreach (PembelianBahan::all() as $bahan) {
            $this->selectedStatus[$bahan->id] = $bahan->status_pembelian;
        }
    }

    public function showPembelianBahanDetail($id)
    {
        $Data = PembelianBahan::with('pembelianBahanDetails')->findOrFail($id);
        $this->id_pembelian_bahan = $id;
        $this->tgl_keluar = $Data->tgl_keluar;
        $this->kode_transaksi = $Data->kode_transaksi;
        $this->divisi = $Data->divisi;
        $this->status = $Data->status;
        $this->jenis_pengajuan = $Data->jenis_pengajuan;
        $this->pembelianBahanDetails  = $Data->pembelianBahanDetails;
        $this->ongkir = $Data->ongkir;
        $this->asuransi = $Data->asuransi;
        $this->layanan = $Data->layanan;
        $this->jasa_aplikasi = $Data->jasa_aplikasi;
        $this->ppn = $Data->ppn;
        $this->shipping_cost = $Data->shipping_cost;
        $this->full_amount_fee = $Data->full_amount_fee;
        $this->value_today_fee = $Data->value_today_fee;

        $this->new_shipping_cost = $Data->new_shipping_cost;
        $this->new_full_amount_fee = $Data->new_full_amount_fee;
        $this->new_value_today_fee = $Data->new_value_today_fee;

        // $this->isDetailOpen = true;
        // Ambil jenis pengajuan
        $jenis = $Data->jenis_pengajuan;

        // Daftar status
        if ($jenis === 'Pembelian Aset' || $jenis === 'Pembelian Aset Lokal' || $jenis === 'Pembelian Aset Impor') {
            $this->statusList = [
                'Leader' => $Data->status_leader ?? 'Belum disetujui',
                'General Affair' => $Data->status_general_manager ?? 'Belum disetujui',
                'Purchasing' => $Data->status_purchasing ?? 'Belum disetujui',
                'Manager' => $Data->status_manager ?? 'Belum disetujui',
                'Finance' => $Data->status_finance ?? 'Belum disetujui',
                'Manager Admin' => $Data->status_admin_manager ?? 'Belum disetujui',
                'Direktur' => $Data->status ?? 'Belum disetujui',
            ];

            $this->dateList = [
                'Pengajuan' => $Data->tgl_pengajuan,
                'Leader' => $Data->tgl_approve_leader,
                'General Affair' => $Data->tgl_approve_general_manager,
                'Purchasing' => $Data->tgl_approve_purchasing,
                'Manager' => $Data->tgl_approve_manager,
                'Finance' => $Data->tgl_approve_finance,
                'Manager Admin' => $Data->tgl_approve_admin_manager,
                'Direktur' => $Data->tgl_approve_direktur,
            ];
        } else {
            $this->statusList = [
                'Leader' => $Data->status_leader ?? 'Belum disetujui',
                'Purchasing' => $Data->status_purchasing ?? 'Belum disetujui',
                'Manager' => $Data->status_manager ?? 'Belum disetujui',
                'Finance' => $Data->status_finance ?? 'Belum disetujui',
                'Manager Admin' => $Data->status_admin_manager ?? 'Belum disetujui',
                'Direktur' => $Data->status ?? 'Belum disetujui',
            ];

            $this->dateList = [
                'Pengajuan' => $Data->tgl_pengajuan,
                'Leader' => $Data->tgl_approve_leader,
                'Purchasing' => $Data->tgl_approve_purchasing,
                'Manager' => $Data->tgl_approve_manager,
                'Finance' => $Data->tgl_approve_finance,
                'Manager Admin' => $Data->tgl_approve_admin_manager,
                'Direktur' => $Data->tgl_approve_direktur,
            ];
        }

        // Hitung perbedaan waktu antar approval
        $previousDate = null;
        $this->timeDiffs = [];

        foreach ($this->dateList as $key => $date) {
            if ($previousDate && $date) {
                $this->timeDiffs[$key] = Carbon::parse($date)->diffForHumans(Carbon::parse($previousDate), ['parts' => 2, 'short' => true]);
            } else {
                $this->timeDiffs[$key] = null;
            }
            $previousDate = $date;
        }

        $this->isDetailOpen = true;
    }


    public function resetModalState()
    {
        $this->isDeleteModalOpen = false;
        $this->isShowModalOpen = false;
        $this->isApproveLeaderModalOpen = false;
        $this->isApproveManagerModalOpen = false;
        $this->isApprovePurchasingModalOpen = false;
        $this->isApproveFinanceModalOpen = false;
        $this->isApproveAdminManagerModalOpen = false;
        $this->isApproveDirekturModalOpen = false;
        $this->isApproveGMModalOpen = false;
        $this->isShowInvoiceModalOpen = false;
        $this->isUploadInvoiceModalOpen = false;
        $this->isUploadDokumenModalOpen = false;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function setTab($tab)
    {
        $this->selectedTab = $tab;
    }

    public function updateStatus($id)
    {
        $bahan = PembelianBahan::find($id);
        if ($bahan) {
            $bahan->status_pembelian = $this->selectedStatus[$id];
            $bahan->save();

            // Update status di tabel pengajuan
            if ($bahan->pengajuan_id) {
                $pengajuan = Pengajuan::find($bahan->pengajuan_id);
                if ($pengajuan) {
                    $pengajuan->status_pembelian = $bahan->status_pembelian;
                    $pengajuan->save();
                }
            }
        }
    }

    public function setFilter($value)
    {
        if ($value === 'semua') {
            $this->filter = null;
        } else {
            $this->filter = $value;
        }
    }

    public function showPembelianBahan(int $id)
    {
        $Data = PembelianBahan::with('pembelianBahanDetails')->findOrFail($id);
        $this->id_pembelian_bahan = $id;
        $this->tgl_keluar = $Data->tgl_keluar;
        $this->kode_transaksi = $Data->kode_transaksi;
        $this->divisi = $Data->divisi;
        $this->status = $Data->status;
        $this->jenis_pengajuan = $Data->jenis_pengajuan;
        $this->pembelianBahanDetails  = $Data->pembelianBahanDetails;
        $this->ongkir = $Data->ongkir;
        $this->asuransi = $Data->asuransi;
        $this->layanan = $Data->layanan;
        $this->jasa_aplikasi = $Data->jasa_aplikasi;
        $this->ppn = $Data->ppn;
        $this->shipping_cost = $Data->shipping_cost;
        $this->full_amount_fee = $Data->full_amount_fee;
        $this->value_today_fee = $Data->value_today_fee;

        $this->new_shipping_cost = $Data->new_shipping_cost;
        $this->new_full_amount_fee = $Data->new_full_amount_fee;
        $this->new_value_today_fee = $Data->new_value_today_fee;
        $this->isShowModalOpen = true;
    }

    public function calculateTotalHarga()
    {
        $this->totalHarga = PembelianBahan::where('status', 'Disetujui')->with('pembelianBahanDetails')
            ->get()
            ->sum(function ($pemebelianBahan) {
                return $pemebelianBahan->pembelianBahanDetails->sum('sub_total');
            });
    }

    public function editPembelianBahan(int $id, $page)
    {
        $Data = PembelianBahan::findOrFail($id);
        $this->id_pembelian_bahan = $id;
        $this->status = $Data->status;
        $this->catatan = $Data->catatan;
        $this->currentPage = $page;
        $this->isApproveDirekturModalOpen = true;
    }

    public function editLeaderPembelianBahan(int $id, $page)
    {
        $Data = PembelianBahan::findOrFail($id);
        $this->id_pembelian_bahan = $id;
        $this->status_leader = $Data->status_leader;
        $this->catatan = $Data->catatan;
        $this->currentPage = $page;
        $this->isApproveLeaderModalOpen = true;
    }

    public function editGMPembelianBahan(int $id, $page)
    {
        $Data = PembelianBahan::findOrFail($id);
        $this->id_pembelian_bahan = $id;
        $this->status_general_manager = $Data->status_general_manager;
        $this->catatan = $Data->catatan;
        $this->currentPage = $page;
        $this->isApproveGMModalOpen = true;
    }

    public function editPurchasingPembelianBahan(int $id, $page)
    {
        $Data = PembelianBahan::findOrFail($id);
        $this->id_pembelian_bahan = $id;
        $this->status_purchasing = $Data->status_purchasing;
        $this->catatan = $Data->catatan;
        $this->currentPage = $page;
        $this->isApprovePurchasingModalOpen = true;
    }

    public function editManagerPembelianBahan(int $id, $page)
    {
        $Data = PembelianBahan::findOrFail($id);
        $this->id_pembelian_bahan = $id;
        $this->status_manager = $Data->status_manager;
        $this->catatan = $Data->catatan;
        $this->currentPage = $page;
        $this->isApproveManagerModalOpen = true;
    }

    public function editFinancePembelianBahan(int $id, $page)
    {
        $Data = PembelianBahan::findOrFail($id);
        $this->id_pembelian_bahan = $id;
        $this->status_finance = $Data->status_finance;
        $this->catatan = $Data->catatan;
        $this->currentPage = $page;
        $this->isApproveFinanceModalOpen = true;
    }

    public function editAdminManagerPembelianBahan(int $id, $page)
    {
        $Data = PembelianBahan::findOrFail($id);
        $this->id_pembelian_bahan = $id;
        $this->status_admin_manager = $Data->status_admin_manager;
        $this->catatan = $Data->catatan;
        $this->currentPage = $page;
        $this->isApproveAdminManagerModalOpen = true;
    }

    public function showInvoice(int $id)
    {
        $Data = PembelianBahan::findOrFail($id);
        $this->id_pembelian_bahan = $id;
        $this->link = $Data->link;
        $this->isShowInvoiceModalOpen = true;
    }


    public function editPengambilanPembelianBahan(int $id)
    {
        $Data = PembelianBahan::findOrFail($id);
        $this->id_pembelian_bahan = $id;
        $this->status_pengambilan = $Data->status_pengambilan;
    }

    public function uploadInvoice(int $id, $page)
    {
        $Data = PembelianBahan::findOrFail($id);
        $this->id_pembelian_bahan = $id;
        $this->link = $Data->link;
        $this->currentPage = $page;
        $this->isUploadInvoiceModalOpen = true;
    }

    public function uploadDokumen(int $id, $page)
    {
        $Data = PembelianBahan::findOrFail($id);
        $this->id_pembelian_bahan = $id;
        $this->dokumen = $Data->dokumen;
        $this->currentPage = $page;
        $this->isUploadDokumenModalOpen = true;
    }

    public function deletePembelianBahan(int $id, $page)
    {
        $this->id_pembelian_bahan = $id;
        $this->currentPage = $page;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
        $this->isShowModalOpen = false;
        $this->isApproveLeaderModalOpen = false;
        $this->isApproveManagerModalOpen = false;
        $this->isApprovePurchasingModalOpen = false;
        $this->isApproveFinanceModalOpen = false;
        $this->isApproveAdminManagerModalOpen = false;
        $this->isApproveDirekturModalOpen = false;
        $this->isApproveGMModalOpen = false;
        $this->isShowInvoiceModalOpen = false;
        $this->isUploadInvoiceModalOpen = false;
        $this->isUploadDokumenModalOpen = false;
    }

    public function render()
    {
        $user = Auth::user();

        // Default: Urutkan berdasarkan tanggal pengajuan DESC
        $pembelian_bahan = PembelianBahan::with('dataUser', 'pembelianBahanDetails');

        if ($user->hasRole(['superadmin'])) {
            $pembelian_bahan->where(function ($query) {
                $query->whereIn('jenis_pengajuan', ['Pembelian Bahan/Barang/Alat Lokal', 'Pembelian Bahan/Barang/Alat Impor', 'Pembelian Aset',
                'Pembelian Aset Lokal', 'Pembelian Aset Impor', 'Purchase Order']);
            })->orderBy('tgl_pengajuan', 'desc');
        } elseif ($user->hasRole(['hardware manager'])) {
            $pembelian_bahan
                ->whereIn('divisi', ['RnD', 'Helper', 'Teknisi', 'OP', 'Produksi'])
                ->whereIn('jenis_pengajuan', [
                    'Pembelian Bahan/Barang/Alat Lokal',
                    'Pembelian Bahan/Barang/Alat Impor',
                    'Pembelian Aset', 'Pembelian Aset Lokal', 'Pembelian Aset Impor',
                    'Purchase Order'
                ])
                ->where(function ($query) {

                    $query->where('status_leader', 'Belum disetujui')

                        ->orWhere(function ($q) {
                            $q->where('jenis_pengajuan', 'Purchase Order')
                                ->where('status_leader', 'Disetujui');
                        })

                        ->orWhere(function ($q) {
                            $q->whereIn('jenis_pengajuan', [
                                'Pembelian Bahan/Barang/Alat Lokal',
                                'Pembelian Bahan/Barang/Alat Impor',
                                'Pembelian Aset','Pembelian Aset Lokal', 'Pembelian Aset Impor',
                            ])->whereHas('dataUser', function ($u) {
                                $u->whereIn('job_level', [3, 4])
                                    ->where('status_purchasing', 'Disetujui');
                            });
                        });
                })

                ->orderByRaw("
            CASE
                WHEN status_leader = 'Belum disetujui' THEN 0
                ELSE 1
            END
        ")
                ->orderBy('tgl_pengajuan', 'desc');
            // $pembelian_bahan->whereIn('divisi', ['RnD', 'Helper', 'Teknisi', 'OP', 'Produksi'])
            //     ->whereIn('jenis_pengajuan', [
            //         'Pembelian Bahan/Barang/Alat Lokal',
            //         'Pembelian Bahan/Barang/Alat Impor',
            //         'Pembelian Aset',
            //         'Purchase Order'
            //     ])
            //     ->where(function ($query) {
            //         // Jika jenis pengajuan adalah Purchase Order → hanya tampil jika status_leader = 'Disetujui'
            //         $query->where(function ($q) {
            //             $q->where('jenis_pengajuan', 'Purchase Order')
            //                 ->where('status_leader', 'Disetujui');
            //         })
            //             // Untuk jenis pengajuan lain → tetap tampil berdasarkan job_level & status_purchasing
            //             ->orWhere(function ($q) {
            //                 $q->whereIn('jenis_pengajuan', [
            //                     'Pembelian Bahan/Barang/Alat Lokal',
            //                     'Pembelian Bahan/Barang/Alat Impor',
            //                     'Pembelian Aset'
            //                 ])
            //                     ->where(function ($sub) {
            //                         $sub->whereHas('dataUser', function ($u) {
            //                             $u->where('job_level', 3)
            //                                 ->where('status_purchasing', 'Disetujui');
            //                         })
            //                             ->orWhereHas('dataUser', function ($u) {
            //                                 $u->where('job_level', 4)
            //                                     ->where('status_purchasing', 'Disetujui');
            //                             });
            //                     });
            //             });
            //     })
            //     ->orderBy('tgl_pengajuan', 'desc');
        } elseif ($user->hasRole(['marketing manager'])) {
            $pembelian_bahan->whereIn('divisi', ['Marketing'])
                ->whereIn('jenis_pengajuan', [
                    'Pembelian Bahan/Barang/Alat Lokal',
                    'Pembelian Bahan/Barang/Alat Impor',
                    'Pembelian Aset','Pembelian Aset Lokal', 'Pembelian Aset Impor',
                    'Purchase Order'
                ])
                ->where(function ($query) {
                    // Kondisi berdasarkan job_level pengaju
                    $query
                        ->whereHas('dataUser', function ($q) {
                            $q->where('job_level', 3)
                                ->where('status_purchasing', 'Disetujui');
                        })
                        ->orWhereHas('dataUser', function ($q) {
                            $q->where('job_level', 4)
                                ->where('status_purchasing', 'Disetujui');
                        });
                })
                ->orderBy('tgl_pengajuan', 'desc');
        } elseif ($user->hasRole(['software manager', 'software', 'publikasi'])) {
            $pembelian_bahan->whereIn('divisi', ['Software', 'Publikasi']);
            $pembelian_bahan->orderByRaw("CASE WHEN status_manager = 'Belum disetujui' THEN 0 ELSE 1 END");
        } elseif ($user->hasRole(['marketing level 3'])) {
            $pembelian_bahan->whereIn('divisi', ['Marketing']);
            $pembelian_bahan->where(function ($query) {
                $query->whereIn('jenis_pengajuan', ['Pembelian Bahan/Barang/Alat Lokal', 'Pembelian Bahan/Barang/Alat Impor', 'Pembelian Aset',
                'Pembelian Aset Lokal', 'Pembelian Aset Impor', 'Purchase Order']);
            });
            $pembelian_bahan->orderByRaw("CASE WHEN status_leader = 'Belum disetujui' THEN 0 ELSE 1 END");
        } elseif ($user->hasRole(['rnd level 3'])) {
            $pembelian_bahan->whereIn('divisi', ['RnD']);
            $pembelian_bahan->where(function ($query) {
                $query->whereIn('jenis_pengajuan', ['Pembelian Bahan/Barang/Alat Lokal', 'Pembelian Bahan/Barang/Alat Impor', 'Pembelian Aset',
                'Pembelian Aset Lokal', 'Pembelian Aset Impor', 'Purchase Order']);
            });
            $pembelian_bahan->orderByRaw("CASE WHEN status_leader = 'Belum disetujui' THEN 0 ELSE 1 END");
        } elseif ($user->hasRole(['teknisi level 3'])) {
            $pembelian_bahan->whereIn('divisi', ['Helper', 'Teknisi']);
            $pembelian_bahan->where(function ($query) {
                $query->whereIn('jenis_pengajuan', ['Pembelian Bahan/Barang/Alat Lokal', 'Pembelian Bahan/Barang/Alat Impor', 'Pembelian Aset',
                'Pembelian Aset Lokal', 'Pembelian Aset Impor', 'Purchase Order']);
            });
            $pembelian_bahan->orderByRaw("CASE WHEN status_leader = 'Belum disetujui' THEN 0 ELSE 1 END");
        } elseif ($user->hasRole(['produksi level 3'])) {
            $pembelian_bahan->whereIn('divisi', ['OP', 'Produksi']);
            $pembelian_bahan->where(function ($query) {
                $query->whereIn('jenis_pengajuan', ['Pembelian Bahan/Barang/Alat Lokal', 'Pembelian Bahan/Barang/Alat Impor', 'Pembelian Aset',
                'Pembelian Aset Lokal', 'Pembelian Aset Impor', 'Purchase Order']);
            });
            $pembelian_bahan->orderByRaw("CASE WHEN status_leader = 'Belum disetujui' THEN 0 ELSE 1 END");
        } elseif ($user->hasAnyRole(['hrd level 3', 'general_affair'])) {
            $pembelian_bahan->where(function ($query) use ($user) {
                if ($user->hasRole('hrd level 3')) {
                    $query->orWhere(function ($q) {
                        $q->whereIn('divisi', ['HSE', 'HRD', 'Helper', 'General Affair'])
                            ->whereIn('jenis_pengajuan', [
                                'Pembelian Bahan/Barang/Alat Lokal',
                                'Pembelian Bahan/Barang/Alat Impor',
                                'Pembelian Aset',
                                'Pembelian Aset Lokal', 'Pembelian Aset Impor',
                                'Purchase Order'
                            ]);
                    });
                }elseif ($user->hasRole('general_affair')) {
                    $query->orWhere(function ($q) {
                        $q->where('status_leader', 'Disetujui')
                            ->where('status_general_manager', 'Belum disetujui');
                        $q->where(function ($sub) {
                            $sub->orWhere('jenis_pengajuan', [
                                'Pembelian Aset',
                                'Pembelian Aset Lokal',
                                'Pembelian Aset Impor'
                            ]);
                            $sub->orWhere(function ($x) {
                                $x->whereIn('jenis_pengajuan', [
                                    'Pembelian Bahan/Barang/Alat Lokal',
                                    'Pembelian Bahan/Barang/Alat Impor'
                                ])->whereIn('divisi', [
                                    'HSE',
                                    'HRD',
                                    'Helper',
                                    'General Affair'
                                ]);
                            });
                        });
                    });
                }
            });

            // Order prioritas
            $pembelian_bahan->orderByRaw("
            CASE
                WHEN status_general_manager = 'Belum disetujui' THEN 0
                WHEN status_leader = 'Belum disetujui' THEN 1
                ELSE 2
            END");
        } elseif ($user->hasRole(['purchasing level 3'])) {
            // $pembelian_bahan->whereIn('divisi', ['Purchasing']);
            $pembelian_bahan->where(function ($query) {
                $query->whereIn('jenis_pengajuan', [
                    'Pembelian Bahan/Barang/Alat Lokal',
                    'Pembelian Bahan/Barang/Alat Impor',
                    'Pembelian Aset',
                    'Pembelian Aset Lokal', 'Pembelian Aset Impor',
                    'Purchase Order'
                ]);

                // Untuk Aset, hanya tampil kalau sudah disetujui GA
                // $query->orWhere(function ($q) {
                //     $q->where('jenis_pengajuan', 'Pembelian Aset')
                //     ->where('status_general_manager', 'Disetujui');
                // });
            });

            $pembelian_bahan->orderByRaw("CASE WHEN status_leader = 'Belum disetujui' THEN 0 ELSE 1 END")->orderBy('tgl_pengajuan', 'desc');
        } elseif ($user->hasRole(['purchasing'])) {
            $pembelian_bahan->where(function ($query) {
                $query->whereIn('jenis_pengajuan', [
                    'Pembelian Bahan/Barang/Alat Lokal',
                    'Pembelian Bahan/Barang/Alat Impor',
                    'Pembelian Aset',
                    'Pembelian Aset Lokal', 'Pembelian Aset Impor',
                    'Purchase Order'
                ]);
            })->orderBy('tgl_pengajuan', 'desc');
        } elseif ($user->hasRole(['administrasi'])) {
            $pembelian_bahan->whereIn('jenis_pengajuan', [
                'Pembelian Bahan/Barang/Alat Lokal',
                'Pembelian Bahan/Barang/Alat Impor',
                'Pembelian Aset',
                'Pembelian Aset Lokal', 'Pembelian Aset Impor',
                'Purchase Order'
            ])
                ->orderBy('tgl_pengajuan', 'desc'); // Hanya tampilkan yang sudah disetujui oleh manager

            // Urutkan agar status_finance yang belum disetujui muncul lebih dulu
            $pembelian_bahan->orderByRaw("CASE WHEN status_finance = 'Belum disetujui' THEN 0 ELSE 1 END");
        } elseif ($user->hasRole(['administration manager'])) {
            $pembelian_bahan->where(function ($query) {
                $query->whereIn('jenis_pengajuan', ['Pembelian Bahan/Barang/Alat Lokal', 'Pembelian Bahan/Barang/Alat Impor', 'Pembelian Aset',
                'Pembelian Aset Lokal', 'Pembelian Aset Impor', 'Purchase Order']);
            })->orderBy('tgl_pengajuan', 'desc');
            // $pembelian_bahan->orderByRaw("CASE WHEN status_finance = 'Belum disetujui' THEN 0 ELSE 1 END");
        }

        // Pencarian dan filter tambahan
        $pembelian_bahan->where(function ($query) {
            $query->where('tgl_keluar', 'like', '%' . $this->search . '%')
                ->orWhere('tgl_pengajuan', 'like', '%' . $this->search . '%')
                ->orWhere('keterangan', 'like', '%' . $this->search . '%')
                ->orWhere('tujuan', 'like', '%' . $this->search . '%')
                ->orWhere('divisi', 'like', '%' . $this->search . '%')
                ->orWhere('status', 'like', '%' . $this->search . '%')
                ->orWhere('jenis_pengajuan', 'like', '%' . $this->search . '%')
                ->orWhereHas('dataUser', function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('dataPengajuan', function ($query) {
                    $query->where('kode_pengajuan', 'like', '%' . $this->search . '%');
                })
                ->orWhere('kode_transaksi', 'like', '%' . $this->search . '%');
        })
            ->when($this->selectedTab  === 'pengajuan', function ($query) {
                return $query->where('status_pembelian', 'Pengajuan');
            })
            ->when($this->selectedTab  === 'diproses', function ($query) {
                return $query->where('status_pembelian', 'Diproses');
            })
            ->when($this->selectedTab  === 'selesai', function ($query) {
                return $query->where('status_pembelian', 'Selesai');
            })
            ->when($this->filter === 'Ditolak', function ($query) {
                return $query->where('status', 'Ditolak');
            })
            ->when($this->filter === 'Disetujui', function ($query) {
                return $query->where('status', 'Disetujui');
            })
            ->when($this->filter === 'Belum disetujui', function ($query) {
                return $query->where('status', 'Belum disetujui');
            });

        // Paginate hasil query
        $pembelian_bahans = $pembelian_bahan->paginate($this->perPage);


        // Return ke view
        return view('livewire.pembelian-bahan-table', [
            'pembelian_bahans' => $pembelian_bahans,
        ]);
    }
}
