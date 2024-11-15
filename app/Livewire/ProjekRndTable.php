<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Produksi;
use App\Models\Projek;
use App\Models\ProjekRnd;
use Livewire\WithPagination;

class ProjekRndTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_projek_rnd;
    public function render()
    {
        $projek_rnds = ProjekRnd::with(['projekRndDetails', 'bahanKeluar'])->orderBy('id', 'desc')
        ->where(function ($query) {
            $query->where('mulai_projek_rnd', 'like', '%' . $this->search . '%')
                ->orWhere('selesai_projek_rnd', 'like', '%' . $this->search . '%')
                ->orWhere('nama_projek_rnd', 'like', '%' . $this->search . '%')
                ->orWhere('status', 'like', '%' . $this->search . '%')
                ->orWhere('kode_projek_rnd', 'like', '%' . $this->search . '%');
        })
            ->paginate($this->perPage);

        return view('livewire.projek-rnd-table', [
            'projek_rnds' => $projek_rnds,
        ]);
    }

    public function deleteProjekRnd(int $id)
    {
        $this->id_projek_rnd = $id;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
