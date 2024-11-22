<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UsersTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 25;
    public $id_user, $name;

    public function render()
    {
        $users = User::query()
            ->where('name', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->orWhereHas('roles', function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->paginate($this->perPage);

        return view('livewire.users-table', [
            'users' => $users
        ]);
    }



    public function deleteUser(int $id)
    {
        $this->id_user = $id;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
