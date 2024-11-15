<?php

namespace App\Livewire;

use App\Models\Projek;
use Livewire\Component;
use App\Models\Produksi;
use App\Models\Pengajuan;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class PengajuanTable extends Component
{
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_pengajuans;
    public function render()
    {
        $user = Auth::user();

        $query = Pengajuan::with(['pengajuanDetails', 'bahanKeluar'])->orderBy('id', 'desc');

        if ($user->hasRole('superadmin') || $user->hasRole('purchasing')) {

        } elseif ($user->hasRole('produksi')) {
            $query->where('divisi', 'Produksi');
        }

        // Apply search filters
        $query->where(function ($query) {
            $query->where('mulai_pengajuan', 'like', '%' . $this->search . '%')
                ->orWhere('selesai_pengajuan', 'like', '%' . $this->search . '%')
                ->orWhere('divisi', 'like', '%' . $this->search . '%')
                ->orWhere('keterangan', 'like', '%' . $this->search . '%')
                ->orWhere('status', 'like', '%' . $this->search . '%')
                ->orWhere('kode_pengajuan', 'like', '%' . $this->search . '%');
        });

        // Paginate the results
        $pengajuans = $query->paginate($this->perPage);

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
