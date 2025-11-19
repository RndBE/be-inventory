<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Produksi;
use App\Models\Projek;
use Livewire\WithPagination;

class ProjekTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_projeks;
    public $isDeleteModalOpen = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deleteProjeks(int $id)
    {
        $this->id_projeks = $id;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
    }

    public function render()
    {
        $projeks = Projek::with(['projekDetails', 'bahanKeluar'])->orderBy('id', 'desc')
        ->where(function ($query) {
            $query->where('mulai_projek', 'like', '%' . $this->search . '%')
                ->orWhere('selesai_projek', 'like', '%' . $this->search . '%')
                ->orWhere('nama_projek', 'like', '%' . $this->search . '%')
                ->orWhere('status', 'like', '%' . $this->search . '%')
                ->orWhere('keterangan', 'like', '%' . $this->search . '%')
                ->orWhere('kode_projek', 'like', '%' . $this->search . '%');
        })
            ->paginate($this->perPage);

        return view('livewire.projek-table', [
            'projeks' => $projeks,
        ]);
    }
}
