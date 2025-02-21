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
    public $perPage = 25;
    public $id_pengajuans;
    public $isDeleteModalOpen = false;


    public $showModal = true;


    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deletePengajuans(int $id)
    {
        $this->id_pengajuans = $id;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
        $this->showModal = false;
    }

    public function render()
    {
        $user = Auth::user();

        $query = Pengajuan::with(['pengajuanDetails', 'bahanKeluar'])->orderBy('id', 'desc');

        if ($user->hasRole('superadmin') || $user->hasRole('purchasing')) {

        }elseif ($user->hasRole(['produksi', 'op', 'teknisi'])) {
            $query->whereIn('divisi', ['Produksi', 'OP', 'Teknisi']);
        }elseif ($user->hasRole('rnd')) {
            $query->where('divisi', 'RnD');
        }elseif ($user->hasRole(['publikasi', 'software'])) {
            $query->whereIn('divisi', ['Publikasi', 'Software']);
        }elseif ($user->hasRole('marketing')) {
            $query->where('divisi', 'Marketing');
        }elseif ($user->hasRole(['purchasing', 'helper'])) {
            $query->where('divisi', 'Purchasing');
        }elseif ($user->hasRole('hse')) {
            $query->where('divisi', 'HSE');
        }elseif ($user->hasRole('administrasi')) {
            $query->where('divisi', 'Administrasi');
        }elseif ($user->hasRole('sekretaris')) {
            $query->where('divisi', 'Sekretaris');
        }

        // Apply search filters
        $query->where(function ($query) {
            $query->where('mulai_pengajuan', 'like', '%' . $this->search . '%')
                ->orWhere('selesai_pengajuan', 'like', '%' . $this->search . '%')
                ->orWhere('divisi', 'like', '%' . $this->search . '%')
                ->orWhere('keterangan', 'like', '%' . $this->search . '%')
                ->orWhere('status_pembelian', 'like', '%' . $this->search . '%')
                ->orWhere('jenis_pengajuan', 'like', '%' . $this->search . '%')
                ->orWhere('kode_pengajuan', 'like', '%' . $this->search . '%');
        });

        // Paginate the results
        $pengajuans = $query->paginate($this->perPage);

        return view('livewire.pengajuan-table', [
            'pengajuans' => $pengajuans,
        ]);
    }
}
