<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\JenisBahan;
use Livewire\WithPagination;

class JenisBahanTable extends Component
{
    use WithPagination;

    public $search = "";
    public $perPage = 15;
    public $id_jenisbahan, $nama;
    public $isEditModalOpen = false;
    public $isDeleteModalOpen = false;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function editJenisBahan(int $id)
    {
        $data = JenisBahan::findOrFail($id);
        $this->id_jenisbahan = $id;
        $this->nama = $data->nama;
        $this->isEditModalOpen = true;
    }

    public function deleteJenisBahan(int $id)
    {
        $this->id_jenisbahan = $id;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isEditModalOpen = false;
        $this->isDeleteModalOpen = false;
    }

    public function render()
    {
        $data = JenisBahan::orderBy('id', 'desc')
            ->where('nama', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.jenis-bahan-table', [
            'jenisbahans' => $data,
        ]);
    }
}

