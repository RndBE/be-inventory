<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;

class PermissionsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 5;
    public $id_permission, $name;

    public function render()
    {
        $permissions = Permission::where('name', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.permissions-table', [
            'permissions' => $permissions
        ]);
    }

    public function deletePermission(int $id)
    {
        $this->id_permission = $id;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
