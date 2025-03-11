<?php

namespace App\Livewire;

use App\Models\Projek;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\GaransiProjek;
use App\Models\LaporanProyek;

class LaporanGaransiProyekTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 25;
    public $id_laporan_proyek;
    public $isDeleteModalOpen = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deleteLaporanProyek(int $id)
    {
        $this->id_laporan_proyek = $id;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
    }

    public function render()
    {
        $proyeks = GaransiProjek::with(['dataKontrak'])
            ->whereHas('dataKontrak', function ($query) {
                $query->where('nama_kontrak', 'like', '%' . $this->search . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.laporan-garansi-proyek-table', [
            'proyeks' => $proyeks,
        ]);
    }
}
