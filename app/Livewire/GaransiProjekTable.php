<?php

namespace App\Livewire;

use App\Models\GaransiProjek;
use Livewire\Component;
use App\Models\Produksi;
use App\Models\Projek;
use Livewire\WithPagination;

class GaransiProjekTable extends Component
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
        $garansi_projeks = GaransiProjek::with(['garansiProjekDetails', 'bahanKeluar'])->orderBy('id', 'desc')
        ->where(function ($query) {
            $query->where('mulai_garansi', 'like', '%' . $this->search . '%')
                ->orWhere('selesai_garansi', 'like', '%' . $this->search . '%')
                ->orWhere('status', 'like', '%' . $this->search . '%')
                ->orWhere('pengaju', 'like', '%' . $this->search . '%')
                ->orWhere('kode_garansi', 'like', '%' . $this->search . '%');
        })
            ->paginate($this->perPage);

        return view('livewire.garansi-projek-table', [
            'garansi_projeks' => $garansi_projeks,
        ]);
    }
}
