<?php

namespace App\Livewire;

use App\Models\Unit;
use Livewire\Component;
use App\Models\BarangAset;
use App\Models\JenisBahan;
use Livewire\WithPagination;

class BarangAsetTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 25;
    public $id_barang, $nama_barang, $jenis_bahan_id, $unit_id, $kode_barang;
    public $selectedIds = [];
    public $isDeleteModalOpen = false;
    public $isEditModalOpen = false;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function editBarang(int $id)
    {
        $barang = BarangAset::findOrFail($id);
        $this->id_barang = $id;
        $this->nama_barang = $barang->nama_barang;
        $this->kode_barang = $barang->kode_barang;
        $this->jenis_bahan_id = $barang->jenisBahan->id ?? 'N/A';
        $this->unit_id = $barang->dataUnit->id ?? 'N/A';
        $this->isEditModalOpen = true;
    }

    public function deleteBarang(int $id)
    {
        $barang = BarangAset::findOrFail($id);
        $this->id_barang = $id;
        $this->nama_barang = $barang->nama_barang;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
        $this->isEditModalOpen = false;
    }

    public function render()
    {
        $units = Unit::all();
        $jenisBahan = JenisBahan::all();
        $barang_asets = BarangAset::with('jenisBahan', 'dataUnit')
            ->where(function ($query) {
                $query->where('nama_barang', 'like', '%' . $this->search . '%')
                    ->orWhere('kode_barang', 'like', '%' . $this->search . '%')
                    ->orWhereHas('jenisBahan', function ($query) {
                        $query->where('nama', 'like', '%' . $this->search . '%');
                    })->orWhereHas('dataUnit', function ($query) {
                        $query->where('nama', 'like', '%' . $this->search . '%');
                    });
            })
            ->paginate($this->perPage);


        return view('livewire.barang-aset-table', [
            'barang_asets' => $barang_asets,
            'jenisBahan' => $jenisBahan,
            'units' => $units,
        ]);
    }
}
