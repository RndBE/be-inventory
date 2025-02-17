<?php

namespace App\Livewire;

use App\Models\Unit;
use Livewire\Component;
use App\Models\BarangAset;
use App\Models\JenisBahan;
use App\Models\JobPosition;
use App\Models\RekapAset;
use App\Models\User;
use Livewire\WithPagination;

class RekapAsetTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 25;
    public $id_barang, $nama_barang, $jenis_bahan_id, $unit_id, $kode_barang, $link_gambar, $id_rekap, $nomor_aset;
    public $selectedIds = [];
    public $isDeleteModalOpen = false;
    public $isEditModalOpen = false;
    public $isShowGambarModalOpen = false;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showGambar(int $id)
    {
        $Data = RekapAset::findOrFail($id);
        $this->id_rekap= $id;
        $this->link_gambar = $Data->link_gambar;
        $this->isShowGambarModalOpen = true;
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
        $barang = RekapAset::findOrFail($id);
        $this->id_barang = $id;
        $this->nama_barang = $barang->barangAset->nama_barang;
        $this->nomor_aset = $barang->nomor_aset;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
        $this->isEditModalOpen = false;
        $this->isShowGambarModalOpen = false;
    }

    public function render()
    {
        $units = Unit::all();
        $jenisBahan = JenisBahan::all();
        $dataUser = User::all();
        $dataDivisi = JobPosition::all();
        $barangAset = BarangAset::all();
        $rekap_asets = RekapAset::with('jenisBahan', 'dataUnit','dataUser.dataJobPosition', 'barangAset','dataDivisi' )
            ->where(function ($query) {
                $query->where('nomor_aset', 'like', '%' . $this->search . '%')
                ->orWhere('tgl_perolehan', 'like', '%' . $this->search . '%')
                ->orWhere('kondisi', 'like', '%' . $this->search . '%')
                    ->orWhereHas('dataUser', function ($query) {
                        $query->where('name', 'like', '%' . $this->search . '%');
                    })->orWhereHas('dataUser.dataJobPosition', function ($query) {
                        $query->where('nama', 'like', '%' . $this->search . '%');
                    })->orWhereHas('barangAset', function ($query) {
                        $query->where('nama_barang', 'like', '%' . $this->search . '%');
                    });
            })
            ->paginate($this->perPage);


        return view('livewire.rekap-aset-table', [
            'rekap_asets' => $rekap_asets,
            'jenisBahan' => $jenisBahan,
            'units' => $units,
            'dataUser' => $dataUser,
            'dataDivisi' => $dataDivisi,
            'barangAset' => $barangAset,
        ]);
    }
}
