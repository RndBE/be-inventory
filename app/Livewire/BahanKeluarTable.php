<?php

namespace App\Livewire;

use App\Models\BahanKeluar;
use Livewire\Component;
use Livewire\WithPagination;

class BahanKeluarTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_bahan_keluars, $status,
    $kode_transaksi, $tgl_keluar, $divisi, $bahanKeluarDetails, $status_pengambilan;
    public $filter = 'semua';
    public $totalHarga;
    // public $isModalOpen = false;

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
        // $this->resetPage();
        // $this->isModalOpen = true;
    }

    public function showBahanKeluar(int $id)
    {
        $Data = BahanKeluar::with('bahanKeluarDetails')->findOrFail($id);
        $this->id_bahan_keluars = $id;
        $this->tgl_keluar = $Data->tgl_keluar;
        $this->kode_transaksi = $Data->kode_transaksi;
        $this->divisi = $Data->divisi;
        $this->status = $Data->status;
        $this->bahanKeluarDetails  = $Data->bahanKeluarDetails;
        // $this->isModalOpen = true;
    }

    // public function closeModal()
    // {
    //     $this->isModalOpen = false;
    // }

    public function calculateTotalHarga()
    {
        $this->totalHarga = BahanKeluar::where('status', 'Disetujui')->with('bahanKeluarDetails')
        ->get()
            ->sum(function ($bahanKeluar) {
                return $bahanKeluar->bahanKeluarDetails->sum('sub_total');
            });
    }

    public function render()
    {
        $bahan_keluars = BahanKeluar::with('bahanKeluarDetails')->orderBy('id', 'desc')
        ->where(function ($query) {
            $query->where('tgl_keluar', 'like', '%' . $this->search . '%')
                ->orWhere('tgl_pengajuan', 'like', '%' . $this->search . '%')
                ->orWhere('tujuan', 'like', '%' . $this->search . '%')
                ->orWhere('divisi', 'like', '%' . $this->search . '%')
                ->orWhere('status', 'like', '%' . $this->search . '%')
                ->orWhere('kode_transaksi', 'like', '%' . $this->search . '%');
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

        return view('livewire.bahan-keluar-table', [
            'bahan_keluars' => $bahan_keluars,
        ]);
    }

    public function editBahanKeluar(int $id)
    {
        $Data = BahanKeluar::findOrFail($id);
        $this->id_bahan_keluars = $id;
        $this->status = $Data->status;
        // $this->isModalOpen = true;
    }

    public function editPengambilanBahanKeluar(int $id)
    {
        $Data = BahanKeluar::findOrFail($id);
        $this->id_bahan_keluars = $id;
        $this->status_pengambilan = $Data->status_pengambilan;
    }

    public function deleteBahanKeluars(int $id)
    {
        $this->id_bahan_keluars = $id;
        // $this->isModalOpen = true;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
