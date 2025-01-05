<?php

namespace App\Livewire;

use App\Models\BahanRetur;
use App\Models\StockOpname;
use Livewire\Component;
use Livewire\WithPagination;

class BahanStockOpnameTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_bahan_returs, $status,
    $kode_transaksi, $tgl_diterima, $tgl_pengajuan, $divisi, $bahanReturDetails, $kode_produksi, $kode_projek;
    public $filter = 'semua';
    public $totalHarga;
    public $isDeleteModalOpen = false;
    public $isEditModalOpen = false;
    public $isShowModalOpen = false;

    public function mount()
    {
        $this->calculateTotalHarga();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function setFilter($value)
    {
        if ($value === 'semua') {
            $this->filter = null;
        } else {
            $this->filter = $value;
        }
    }

    public function showBahanRetur(int $id)
    {
        $Data = BahanRetur::with('bahanReturDetails')->findOrFail($id);
        $this->id_bahan_returs = $id;
        $this->tgl_pengajuan = $Data->tgl_pengajuan;
        $this->tgl_diterima = $Data->tgl_diterima;
        $this->kode_produksi = $Data->produksiS ? $Data->produksiS->kode_produksi : null;
        $this->kode_projek = $Data->projek ? $Data->projek->kode_projek : null;
        $this->kode_transaksi = $Data->kode_transaksi;
        $this->divisi = $Data->divisi;
        $this->status = $Data->status;
        $this->bahanReturDetails  = $Data->bahanReturDetails;
        $this->isShowModalOpen = true;
    }

    public function calculateTotalHarga()
    {
        $this->totalHarga = BahanRetur::where('status', 'Disetujui')->with('bahanReturDetails')
        ->get()
            ->sum(function ($bahanRetur) {
                return $bahanRetur->bahanReturDetails->sum('sub_total');
            });
    }

    public function editBahanRetur(int $id)
    {
        $Data = BahanRetur::findOrFail($id);
        $this->id_bahan_returs = $id;
        $this->status = $Data->status;
        $this->isEditModalOpen = true;
    }

    public function deleteBahanReturs(int $id)
    {
        $this->id_bahan_returs = $id;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
        $this->isEditModalOpen = false;
        $this->isShowModalOpen = false;
    }

    public function render()
    {
        $stock_opnames = StockOpname::with('stockOpnameDetails')->orderBy('id', 'desc')
        ->where(function ($query) {
            $query->where('tgl_pengajuan', 'like', '%' . $this->search . '%')
            ->orWhere('tgl_diterima', 'like', '%' . $this->search . '%')
                ->orWhere('status_finance', 'like', '%' . $this->search . '%')
                ->orWhere('status_direktur', 'like', '%' . $this->search . '%')
                ->orWhere('nomor_referensi', 'like', '%' . $this->search . '%')
                ->orWhere('keterangan', 'like', '%' . $this->search . '%')
                ->orWhereHas('stockOpnameDetails.dataBahan', function ($query) {
                    $query->where('nama_bahan', 'like', '%' . $this->search . '%');
                });
        })
            ->when($this->filter === 'Ditolak', function ($query) {
                return $query->where('status_finance', 'Ditolak');
            return $query->where('status_direktur', 'Ditolak');
            })
            ->when($this->filter === 'Disetujui', function ($query) {
                return $query->where('status_finance', 'Disetujui');
                return $query->where('status_direktur', 'Disetujui');
            })
            ->when($this->filter === 'Belum disetujui', function ($query) {
                return $query->where('status_finance', 'Belum disetujui');
                return $query->where('status_direktur', 'Belum disetujui');
            })
            ->paginate($this->perPage);

        return view('livewire.bahan-stock-opname-table', [
            'stock_opnames' => $stock_opnames,
        ]);
    }
}
