<?php

namespace App\Livewire;

use App\Models\JobPosition;
use App\Models\Unit;
use Livewire\Component;
use Livewire\WithPagination;

class JobpositionTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 25;
    public $id_jobposition, $nama;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function editJobposition(int $id)
    {
        $Data = JobPosition::findOrFail($id);
        $this->id_jobposition = $id;
        $this->nama = $Data->nama;
    }

    public function deleteJobposition(int $id)
    {
        $this->id_jobposition = $id;
    }

    public function render()
    {
        $Data = JobPosition::orderBy('id', 'desc')
        ->where('nama', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.jobposition-table', [
            'jobpositions' => $Data,
        ]);
    }
}
