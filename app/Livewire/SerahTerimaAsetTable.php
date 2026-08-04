<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\SerahTerimaAset;

class SerahTerimaAsetTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 25;
    public $selectedTab = 'semua';

    public $isDetailModalOpen = false;
    public $detailBast;

    /**
     * Konfirmasi "Tandai Selesai". Sebelumnya memakai confirm() bawaan peramban,
     * yang tampilannya tidak mengikuti tema dan isinya tidak bisa dirinci —
     * padahal tindakannya melepas aset dan menonaktifkan akun karyawan.
     */
    public $isSelesaiModalOpen = false;
    public $bastSelesai;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function setTab($tab)
    {
        $this->selectedTab = $tab;
        $this->resetPage();
    }

    public function showDetail(int $id)
    {
        $this->detailBast = SerahTerimaAset::with([
            'dataKaryawan.dataJobPosition',
            'dataAtasan',
            'dataPengaju',
            'dataPenyelesai',
            'serahTerimaAsetDetails.dataAset.barangAset',
            'serahTerimaAsetDetails.detailPeminjaman',
        ])->findOrFail($id);

        $this->isDetailModalOpen = true;
    }

    /**
     * Buka konfirmasi selesaikan BAST.
     *
     * Datanya dimuat ulang di sini, bukan diambil dari baris tabel: nama karyawan
     * dan jumlah aset yang akan dilepas ikut disebut di konfirmasinya, dan angka
     * itu harus yang terbaru — bukan sisa render sebelumnya.
     */
    public function openSelesai(int $id)
    {
        $bast = SerahTerimaAset::with('dataKaryawan', 'serahTerimaAsetDetails')->findOrFail($id);

        // Yang sudah selesai tidak perlu dikonfirmasi lagi. Penjagaan sebenarnya
        // ada di controller; ini hanya supaya modalnya tidak terbuka sia-sia.
        if ($bast->selesai) {
            return;
        }

        $this->bastSelesai = $bast;
        $this->isSelesaiModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDetailModalOpen = false;
        $this->detailBast = null;
        $this->isSelesaiModalOpen = false;
        $this->bastSelesai = null;
    }

    public function render()
    {
        $user = Auth::user();

        $query = SerahTerimaAset::with([
            'dataKaryawan',
            'dataAtasan',
            'serahTerimaAsetDetails',
        ]);

        // BAST menyangkut kepegawaian, jadi cakupannya lebih ketat daripada
        // peminjaman: hanya HRD, GA, superadmin, atasan yang tercatat, dan
        // karyawan yang bersangkutan sendiri yang boleh melihatnya.
        $bolehLihatSemua = $user->hasAnyRole(['superadmin', 'general_affair'])
            || $user->can('selesaikan-serah-terima-aset')
            || $user->can('tambah-serah-terima-aset');

        if (!$bolehLihatSemua) {
            $query->where(function ($q) use ($user) {
                $q->where('karyawan_id', $user->id)
                    ->orWhere('atasan_id', $user->id)
                    ->orWhere('pengaju', $user->id);
            });
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('kode_bast', 'like', '%' . $this->search . '%')
                    ->orWhere('alasan_keluar', 'like', '%' . $this->search . '%')
                    ->orWhereHas('dataKaryawan', function ($sub) {
                        $sub->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        match ($this->selectedTab) {
            'draft' => $query->draft(),
            'selesai' => $query->selesai(),
            default => null,
        };

        return view('livewire.serah-terima-aset-table', [
            'daftarBast' => $query->orderByDesc('id')->paginate($this->perPage),
        ]);
    }
}
