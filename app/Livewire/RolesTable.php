<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class RolesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $id_role, $name;

    public function render()
    {
        $roles = Role::where('name', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.roles-table', [
            'roles' => $roles
        ]);
    }

    public function deleteRole(int $id)
    {
        $this->id_role = $id;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
