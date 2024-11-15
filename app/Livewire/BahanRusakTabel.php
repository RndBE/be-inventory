<?php

namespace App\Livewire;

use App\Models\BahanRusak;
use Livewire\Component;
use Livewire\WithPagination;

class BahanRusakTabel extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_bahan_rusaks, $status,
    $kode_transaksi, $tgl_diterima, $tgl_pengajuan, $bahanRusakDetails;
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

    public function showBahanRusak(int $id)
    {
        $Data = BahanRusak::with('bahanRusakDetails')->findOrFail($id);
        $this->id_bahan_rusaks = $id;
        $this->tgl_pengajuan = $Data->tgl_pengajuan;
        $this->tgl_diterima = $Data->tgl_diterima;
        $this->kode_transaksi = $Data->kode_transaksi;
        $this->status = $Data->status;
        $this->bahanRusakDetails  = $Data->bahanRusakDetails;
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function calculateTotalHarga()
    {
        $this->totalHarga = BahanRusak::where('status', 'Disetujui')->with('bahanRusakDetails')
        ->get()
            ->sum(function ($bahanRusak) {
                return $bahanRusak->bahanRusakDetails->sum('sub_total');
            });
    }

    public function render()
    {
        $bahan_rusaks = BahanRusak::with('bahanRusakDetails')->orderBy('id', 'desc')
        ->where(function ($query) {
            $query->where('tgl_pengajuan', 'like', '%' . $this->search . '%')
                ->orWhere('tgl_diterima', 'like', '%' . $this->search . '%')
                ->orWhere('kode_transaksi', 'like', '%' . $this->search . '%')
                ->orWhere('status', 'like', '%' . $this->search . '%')
                ->orWhereHas('bahanRusakDetails.dataBahan', function ($query) {
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

        return view('livewire.bahan-rusak-tabel', [
            'bahan_rusaks' => $bahan_rusaks,
        ]);
    }

    public function editBahanRusak(int $id)
    {
        $Data = BahanRusak::findOrFail($id);
        $this->id_bahan_rusaks = $id;
        $this->status = $Data->status;
        $this->isModalOpen = true;
    }

    public function deleteBahanRusaks(int $id)
    {
        $this->id_bahan_rusaks = $id;
        $this->isModalOpen = true;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
