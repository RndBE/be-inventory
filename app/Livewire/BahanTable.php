<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use Livewire\WithPagination;

class BahanTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 25;
    public $id_bahan, $nama_bahan, $jenis_bahan_id, $stok_awal, $total_stok, $penempatan, $supplier, $unit_id, $kondisi, $gambar, $kode_bahan;
    public $selectedIds = [];
    public $isDeleteModalOpen = false;
    public $isShowModalOpen = false;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function bulkEdit()
    {
        if (!empty($this->selectedIds)) {
            return redirect()->route('bahan.editmultiple', ['ids' => $this->selectedIds]);
        }
    }

    public function showBahan(int $id)
    {
        $Data = Bahan::with('purchaseDetails')->findOrFail($id);
        $this->id_bahan = $id;
        $this->kode_bahan = $Data->kode_bahan;
        $this->nama_bahan = $Data->nama_bahan;
        $this->jenis_bahan_id = $Data->jenisBahan->nama ?? 'N/A';
        $this->stok_awal = $Data->stok_awal;
        $this->total_stok = $Data->purchaseDetails->sum('sisa');
        $this->penempatan = $Data->penempatan;
        $this->supplier = $Data->dataSupplier->nama ?? 'N/A';
        $this->kondisi = $Data->kondisi;
        $this->unit_id = $Data->dataUnit->nama ?? 'N/A';
        $this->gambar = $Data->gambar;
        $this->isShowModalOpen = true;
    }


    public function deleteBahan(int $id)
    {
        $this->id_bahan = $id;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
        $this->isShowModalOpen = false;
    }

    public function render()
    {
        $bahans = Bahan::with('jenisBahan', 'dataUnit', 'purchaseDetails')
            ->where(function ($query) {
                $query->where('nama_bahan', 'like', '%' . $this->search . '%')
                    ->orWhere('penempatan', 'like', '%' . $this->search . '%')
                    ->orWhere('kode_bahan', 'like', '%' . $this->search . '%')
                    ->orWhereHas('jenisBahan', function ($query) {
                        $query->where('nama', 'like', '%' . $this->search . '%');
                    })->orWhereHas('dataUnit', function ($query) {
                        $query->where('nama', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('dataSupplier', function ($query) {
                        $query->where('nama', 'like', '%' . $this->search . '%');
                    });
            })
            ->paginate($this->perPage);


        // Calculate total stock
        foreach ($bahans as $bahan) {
            $bahan->total_stok = $bahan->purchaseDetails->sum('sisa');
        }

        return view('livewire.bahan-table', [
            'bahans' => $bahans
        ]);
    }
}
