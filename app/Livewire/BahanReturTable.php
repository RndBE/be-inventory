<?php

namespace App\Livewire;

use App\Models\BahanRetur;
use Livewire\Component;
use Livewire\WithPagination;

class BahanReturTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_bahan_returs, $status,
    $kode_transaksi, $tgl_diterima, $tgl_pengajuan, $divisi, $bahanReturDetails, $kode_produksi, $kode_projek;
    public $filter = 'semua';
    public $totalHarga;
    public $isModalOpen = false;

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
        $this->resetPage();
        $this->isModalOpen = true;
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
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function calculateTotalHarga()
    {
        $this->totalHarga = BahanRetur::where('status', 'Disetujui')->with('bahanReturDetails')
        ->get()
            ->sum(function ($bahanRetur) {
                return $bahanRetur->bahanReturDetails->sum('sub_total');
            });
    }

    public function render()
    {
        $bahan_returs = BahanRetur::with('bahanReturDetails')->orderBy('id', 'desc')
        ->where(function ($query) {
            $query->where('tgl_pengajuan', 'like', '%' . $this->search . '%')
                ->orWhere('tgl_diterima', 'like', '%' . $this->search . '%')
                ->orWhere('kode_transaksi', 'like', '%' . $this->search . '%')
                ->orWhere('status', 'like', '%' . $this->search . '%')
                ->orWhere('tujuan', 'like', '%' . $this->search . '%')
                ->orWhere('divisi', 'like', '%' . $this->search . '%')
                ->orWhereHas('bahanReturDetails.dataBahan', function ($query) {
                    $query->where('nama_bahan', 'like', '%' . $this->search . '%');
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
            })
            ->paginate($this->perPage);

        return view('livewire.bahan-retur-table', [
            'bahan_returs' => $bahan_returs,
        ]);
    }

    public function editBahanRetur(int $id)
    {
        $Data = BahanRetur::findOrFail($id);
        $this->id_bahan_returs = $id;
        $this->status = $Data->status;
        $this->isModalOpen = true;
    }

    public function deleteBahanReturs(int $id)
    {
        $this->id_bahan_returs = $id;
        $this->isModalOpen = true;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
