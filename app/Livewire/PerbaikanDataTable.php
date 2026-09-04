<?php

namespace App\Livewire;

use App\Models\Projek;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\LaporanProyek;
use App\Models\PerbaikanData;
use Illuminate\Support\Facades\Auth;

class PerbaikanDataTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 25;
    public $id_perbaikan_data;
    public $isDeleteModalOpen = false;
    public $isApproveModalOpen = false;
    public $currentPage, $status, $catatan;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function editApproval(int $id, $page)
    {
        $Data = PerbaikanData::findOrFail($id);
        $this->id_perbaikan_data = $id;
        $this->status = $Data->status;
        $this->catatan = $Data->catatan;
        $this->currentPage = $page;
        $this->isApproveModalOpen = true;
    }

    public function deletePerbaikanData(int $id)
    {
        $this->id_perbaikan_data = $id;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
        $this->isApproveModalOpen = false;
    }

    public function render()
    {
        // $perbaikanDatas = PerbaikanData::orderBy('id', 'desc')
        //     ->paginate($this->perPage);

        // return view('livewire.perbaikan-data-table', [
        //     'perbaikanDatas' => $perbaikanDatas,
        // ]);

        $user = Auth::user();
        $userName = $user->name ?? null;
        $dapatLihatSemua = $user->hasRole('superadmin')
            || $user->hasRole('software')
            || $user->can('lihat-semua-perbaikan-data');

        // `penunjukan` ikut dimuat karena tiap baris memeriksa apakah suratnya
        // sudah terbit untuk memilih tombol yang ditampilkan. Tanpa eager load,
        // itu satu query per baris.
        $perbaikanDatas = PerbaikanData::with(['approvalKendalas', 'penunjukan'])
            ->when(! $dapatLihatSemua, function ($query) use ($userName) {
                $query->where('pengaju', $userName);
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.perbaikan-data-table', [
            'perbaikanDatas' => $perbaikanDatas,
        ]);
    }
}
