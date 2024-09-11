<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use Livewire\WithPagination;

class BahanTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 5;
    public $id_bahan, $nama_bahan, $jenis_bahan_id, $stok_awal, $total_stok, $penempatan, $unit_id, $kondisi, $gambar, $kode_bahan;

    public function render()
    {
        $bahans = Bahan::with('jenisBahan', 'dataUnit')
        ->where('nama_bahan', 'like', '%' . $this->search . '%')
        ->orWhere('kode_bahan', 'like', '%' . $this->search . '%')
        ->paginate($this->perPage);

        return view('livewire.bahan-table', [
            'bahans' => $bahans
        ]);
    }

    public function showBahan(int $id)
    {
        $Data = Bahan::findOrFail($id);
        $this->id_bahan = $id;
        $this->kode_bahan = $Data->kode_bahan;
        $this->nama_bahan = $Data->nama_bahan;
        $this->jenis_bahan_id = $Data->jenisBahan->nama ?? 'N/A';
        $this->stok_awal = $Data->stok_awal;
        $this->total_stok = $Data->total_stok;
        $this->penempatan = $Data->penempatan;
        $this->kondisi = $Data->kondisi;
        $this->unit_id = $Data->dataUnit->nama ?? 'N/A';
        $this->gambar = $Data->gambar;
    }

    public function deleteBahan(int $id)
    {
        $this->id_bahan = $id;
    }

    public function updatingSearch(){
        $this->resetPage();
    }
}
