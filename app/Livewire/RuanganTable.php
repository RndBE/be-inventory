<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Ruangan;
use Livewire\WithPagination;

class RuanganTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 25;
    public $id_ruangan, $kode_ruangan, $nama_ruangan, $keterangan;
    public $isDeleteModalOpen = false;
    public $isEditModalOpen = false;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function editRuangan(int $id)
    {
        $ruangan = Ruangan::findOrFail($id);
        $this->id_ruangan = $id;
        $this->kode_ruangan = $ruangan->kode_ruangan;
        $this->nama_ruangan = $ruangan->nama_ruangan;
        $this->keterangan = $ruangan->keterangan;
        $this->isEditModalOpen = true;
    }

    public function deleteRuangan(int $id)
    {
        $ruangan = Ruangan::findOrFail($id);
        $this->id_ruangan = $id;
        $this->nama_ruangan = $ruangan->nama_ruangan;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
        $this->isEditModalOpen = false;
    }

    public function render()
    {
        $ruangans = Ruangan::withCount('rekapAsets')
            ->where(function ($query) {
                $query->where('kode_ruangan', 'like', '%' . $this->search . '%')
                    ->orWhere('nama_ruangan', 'like', '%' . $this->search . '%')
                    ->orWhere('keterangan', 'like', '%' . $this->search . '%');
            })
            ->paginate($this->perPage);

        return view('livewire.ruangan-table', [
            'ruangans' => $ruangans,
        ]);
    }
}
