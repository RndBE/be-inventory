<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\BahanKeluar;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class BahanKeluarTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 25;
    public $id_bahan_keluars, $status,
    $kode_transaksi, $tgl_keluar, $divisi, $bahanKeluarDetails, $status_pengambilan, $status_leader, $status_purchasing, $status_manager, $status_finance, $status_admin_manager, $tujuan;
    public $filter = 'semua';
    public $totalHarga;
    public $isShowModalOpen = false;
    public $isDeleteModalOpen = false;
    public $isEditPengambilanModalOpen = false;
    public $isApproveLeaderModalOpen = false;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->calculateTotalHarga();
    }

    public function setFilter($value)
    {
        if ($value === 'semua') {
            $this->filter = null;
        } else {
            $this->filter = $value;
        }
    }

    public function showBahanKeluar(int $id)
    {
        $Data = BahanKeluar::with('bahanKeluarDetails')->findOrFail($id);
        $this->id_bahan_keluars = $id;
        $this->tgl_keluar = $Data->tgl_keluar;
        $this->kode_transaksi = $Data->kode_transaksi;
        $this->divisi = $Data->divisi;
        $this->status = $Data->status;
        $this->tujuan = $Data->tujuan;
        $this->bahanKeluarDetails  = $Data->bahanKeluarDetails;
        $this->isShowModalOpen = true;
    }

    public function calculateTotalHarga()
    {
        $this->totalHarga = BahanKeluar::where('status', 'Disetujui')->with('bahanKeluarDetails')
        ->get()
            ->sum(function ($bahanKeluar) {
                return $bahanKeluar->bahanKeluarDetails->sum('sub_total');
            });
    }

    public function editBahanKeluar(int $id)
    {
        $Data = BahanKeluar::findOrFail($id);
        $this->id_bahan_keluars = $id;
        $this->status = $Data->status; //status untuk direktur di akhir
        $this->status_leader = $Data->status_leader;
        $this->status_purchasing = $Data->status_purchasing;
        $this->status_manager = $Data->status_manager;
        $this->status_finance = $Data->status_finance;
        $this->status_admin_manager = $Data->status_admin_manager;
        $this->isApproveLeaderModalOpen = true;
    }

    public function editPengambilanBahanKeluar(int $id)
    {
        $Data = BahanKeluar::findOrFail($id);
        $this->id_bahan_keluars = $id;
        $this->status_pengambilan = $Data->status_pengambilan;
        $this->isEditPengambilanModalOpen = true;
    }

    public function deleteBahanKeluars(int $id)
    {
        $this->id_bahan_keluars = $id;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
        $this->isShowModalOpen = false;
        $this->isEditPengambilanModalOpen = false;
        $this->isApproveLeaderModalOpen = false;
    }

    public function render()
    {
        $user = Auth::user();

        $bahan_keluars = BahanKeluar::with('dataUser', 'bahanKeluarDetails', 'produksiS', 'produkJadiDetails')
            ->orderBy('id', 'desc');

        if ($user->hasRole(['superadmin','administrasi','purchasing'])) {

        }
        elseif ($user->hasRole(['hardware manager'])) {
            $bahan_keluars->whereIn('divisi', ['RnD', 'Helper','Teknisi','OP','Produksi','Engineer']);
        }elseif ($user->hasRole(['rnd level 3'])) {
            $bahan_keluars->whereIn('divisi', ['RnD']);
        }elseif ($user->hasRole(['purchasing level 3','helper'])) {
            $bahan_keluars->whereIn('divisi', ['Purchasing','Helper']);
        }elseif ($user->hasRole(['teknisi level 3','produksi level 3'])) {
            $bahan_keluars->whereIn('divisi', ['Teknisi','OP','Produksi','Engineer','Hardware']);
        }
        elseif ($user->hasRole(['marketing manager','marketing level 3'])) {
            $bahan_keluars->whereIn('divisi', ['Marketing', 'Admin Project']);
        }
        elseif ($user->hasRole(['software manager'])) {
            $bahan_keluars->whereIn('divisi', ['Software','Publikasi']);
        }
        elseif ($user->hasRole(['hrd level 3'])) {
            $bahan_keluars->where('divisi', ['HSE','Helper', 'HRD', 'General Affair']);
        }
        elseif ($user->hasRole(['sekretaris'])) {
            $bahan_keluars->where('divisi', 'Sekretaris', 'Secretary');
        }
        elseif ($user->hasRole('administrasi')) {
            $bahan_keluars->where('divisi', ['HSE','Sekretaris','Administrasi', 'Tax Officer', 'Accounting', 'Secretary']);
        }

        // Pencarian dan filter tambahan
        $bahan_keluars->where(function ($query) {

    // === Kolom langsung ===
    $query->where('tgl_keluar', 'like', '%' . $this->search . '%')
        ->orWhere('tgl_pengajuan', 'like', '%' . $this->search . '%')
        ->orWhere('tujuan', 'like', '%' . $this->search . '%')
        ->orWhere('keterangan', 'like', '%' . $this->search . '%')
        ->orWhere('divisi', 'like', '%' . $this->search . '%')
        ->orWhere('status', 'like', '%' . $this->search . '%')
        ->orWhere('status_leader', 'like', '%' . $this->search . '%')
        ->orWhere('status_pengambilan', 'like', '%' . $this->search . '%')
        ->orWhere('kode_transaksi', 'like', '%' . $this->search . '%');

    // === User ===
    $query->orWhereHas('dataUser', function ($q) {
        $q->where('name', 'like', '%' . $this->search . '%');
    });

    // === Bahan Keluar: Bahan ===
    $query->orWhereHas('bahanKeluarDetails', function ($q) {
        $q->whereHas('dataBahan', function ($b) {
            $b->where('nama_bahan', 'like', '%' . $this->search . '%');
        });
    });

    // === Bahan Keluar: Produk (produksi) ===
    $query->orWhereHas('bahanKeluarDetails', function ($q) {
        $q->whereHas('dataProduk', function ($p) {
            $p->whereHas('dataBahan', function ($b) {
                $b->where(function ($x) {
                    $x->where('nama_bahan', 'like', '%' . $this->search . '%')
                      ->orWhere('serial_number', 'like', '%' . $this->search . '%');
                });
            });
        });
    });

    // === Produk Jadi ===
    $query->orWhereHas('bahanKeluarDetails', function ($q) {
        $q->whereHas('dataProdukJadi', function ($pj) {
            $pj->where(function ($x) {
                $x->where('nama_produk', 'like', '%' . $this->search . '%')
                  ->orWhere('serial_number', 'like', '%' . $this->search . '%');
            });
        });
    });

})

            ->when($this->filter === 'Ditolak', function ($query) {
                return $query->where('status', 'Ditolak');
            })
            ->when($this->filter === 'Disetujui', function ($query) {
                return $query->where('status', 'Disetujui');
            })
            ->when($this->filter === 'Belum disetujui', function ($query) {
                return $query->where('status', 'Belum disetujui');
            })->when($this->filter === 'Belum Diambil', function ($query) {
                return $query->where('status_pengambilan', 'Belum Diambil');
            })->when($this->filter === 'Sudah Diambil', function ($query) {
                return $query->where('status_pengambilan', 'Sudah Diambil');
            });

        // Paginate hasil query
        $bahan_keluars = $bahan_keluars->paginate($this->perPage);

        // Return ke view
        return view('livewire.bahan-keluar-table', [
            'bahan_keluars' => $bahan_keluars,
        ]);
    }
}
