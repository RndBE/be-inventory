<?php

namespace App\Livewire;

use App\Models\Kontrak;
use Livewire\Component;
use Livewire\WithPagination;

class KontrakTable extends Component
{
    use WithPagination;

    public $search = "";
    public $perPage = 15;
    public $id_kontrak, $nama_kontrak, $kode_kontrak, $mulai_kontrak, $selesai_kontrak, $garansi;
    public $isEditModalOpen = false;
    public $isDeleteModalOpen = false;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function editKontrak(int $id)
    {
        $data = Kontrak::findOrFail($id);
        $this->id_kontrak = $id;
        $this->kode_kontrak = $data->kode_kontrak;
        $this->nama_kontrak = $data->nama_kontrak;
        $this->mulai_kontrak = $data->mulai_kontrak;
        $this->selesai_kontrak = $data->selesai_kontrak;
        $this->garansi = $data->garansi;
        $this->isEditModalOpen = true;
    }

    public function deleteKontrak(int $id)
    {
        $this->id_kontrak = $id;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isEditModalOpen = false;
        $this->isDeleteModalOpen = false;
    }

    public function render()
{
    $data = Kontrak::where(function ($query) {
            $query->where('kode_kontrak', 'like', '%' . $this->search . '%')
                ->orWhere('nama_kontrak', 'like', '%' . $this->search . '%')
                ->orWhere('mulai_kontrak', 'like', '%' . $this->search . '%')
                ->orWhere('selesai_kontrak', 'like', '%' . $this->search . '%')
                ->orWhere('garansi', 'like', '%' . $this->search . '%');
        })
        ->orderByRaw("
            CAST(SUBSTRING_INDEX(kode_kontrak, '/', -1) AS UNSIGNED) ASC,
            CAST(SUBSTRING_INDEX(kode_kontrak, '/', 1) AS UNSIGNED) ASC
        ")
        ->paginate($this->perPage);

    return view('livewire.kontrak-table', [
        'kontraks' => $data,
    ]);
}

}
