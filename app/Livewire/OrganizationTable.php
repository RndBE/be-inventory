<?php

namespace App\Livewire;

use App\Models\Organization;
use App\Models\Unit;
use Livewire\Component;
use Livewire\WithPagination;

class OrganizationTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 25;
    public $id_organization, $nama;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function editOrganization(int $id)
    {
        $Data = Organization::findOrFail($id);
        $this->id_organization = $id;
        $this->nama = $Data->nama;
    }

    public function deleteOrganization(int $id)
    {
        $this->id_organization = $id;
    }

    public function render()
    {
        $Data = Organization::orderBy('id', 'desc')
        ->where('nama', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.organization-table', [
            'organizations' => $Data,
        ]);
    }
}
