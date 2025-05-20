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
    public $id_stock_opname, $status_finance, $status_direktur,
    $nomor_referensi, $tgl_diterima, $tgl_pengajuan, $stockOpnameDetails;
    public $filter = 'semua';
    public $totalHarga;
    public $isDeleteModalOpen = false;
    public $isEditModalOpen = false;
    public $isShowModalOpen = false;

    public function mount()
    {
        // $this->calculateTotalHarga();
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

    public function showStockOpname(int $id)
    {
        $Data = StockOpname::with('stockOpnameDetails')->findOrFail($id);
        $this->id_stock_opname = $id;
        $this->tgl_pengajuan = $Data->tgl_pengajuan;
        $this->tgl_diterima = $Data->tgl_diterima;
        $this->nomor_referensi = $Data->nomor_referensi;
        $this->status_finance = $Data->status_finance;
        $this->status_direktur = $Data->status_direktur;
        $this->stockOpnameDetails  = $Data->stockOpnameDetails;
        $this->isShowModalOpen = true;
    }


    public function editStockOpname(int $id)
    {
        $Data = StockOpname::findOrFail($id);
        $this->id_stock_opname = $id;
        $this->status_finance = $Data->status_finance;
        $this->status_direktur = $Data->status_direktur;
        $this->isEditModalOpen = true;
    }

    public function deleteStockOpname(int $id)
    {
        $this->id_stock_opname = $id;
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
        $stock_opnames = StockOpname::with('stockOpnameDetails')
            ->when(!auth()->user()->hasRole('superadmin','administrasi'), function ($query) {
                $query->where('pengaju', auth()->id());
            })
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
                return $query->where(function ($q) {
                    $q->where('status_finance', 'Ditolak')
                    ->orWhere('status_direktur', 'Ditolak');
                });
            })
            ->when($this->filter === 'Disetujui', function ($query) {
                return $query->where(function ($q) {
                    $q->where('status_finance', 'Disetujui')
                    ->orWhere('status_direktur', 'Disetujui');
                });
            })
            ->when($this->filter === 'Belum disetujui', function ($query) {
                return $query->where(function ($q) {
                    $q->where('status_finance', 'Belum disetujui')
                    ->orWhere('status_direktur', 'Belum disetujui');
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.bahan-stock-opname-table', [
            'stock_opnames' => $stock_opnames,
        ]);
    }

}
