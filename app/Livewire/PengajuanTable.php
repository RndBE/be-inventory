<?php

namespace App\Livewire;

use App\Models\Pengajuan;
use Livewire\Component;
use App\Models\Produksi;
use App\Models\Projek;
use Livewire\WithPagination;

class PengajuanTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_pengajuans;
    public function render()
    {
        $pengajuans = Pengajuan::with(['pengajuanDetails', 'bahanKeluar'])->orderBy('id', 'desc')
        ->where('mulai_pengajuan', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        return view('livewire.pengajuan-table', [
            'pengajuans' => $pengajuans,
        ]);
    }

    public function deletePengajuans(int $id)
    {
        $this->id_pengajuans = $id;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
