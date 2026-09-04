<?php

namespace App\Livewire;

use App\Models\PenunjukanPerbaikanData;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tab Penunjukan pada halaman Perbaikan Data.
 *
 * Dipisah dari PerbaikanDataTable, bukan dijadikan mode kedua di sana, karena
 * kolomnya berbeda seluruhnya: yang satu bercerita tentang pengajuan, yang ini
 * tentang siapa yang ditunjuk dan sudah dikerjakan atau belum. Menggabungkannya
 * berarti satu komponen dengan dua query, dua set kolom, dan dua aturan
 * penyaringan yang dipilih oleh satu properti.
 */
class PenunjukanPerbaikanDataTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 25;

    /** Id surat yang sedang dikonfirmasi penghapusannya. */
    public $id_penunjukan;
    public $isDeleteModalOpen = false;

    /**
     * Nomor surat yang ditampilkan di modal konfirmasi.
     *
     * Disimpan terpisah, tidak dibaca ulang dari model di dalam blade modalnya:
     * modal yang harus mengambil barisnya sendiri berarti satu query tambahan
     * setiap kali komponennya dirender, termasuk saat modalnya tertutup.
     */
    public $nomor_penunjukan;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function konfirmasiHapus(int $id)
    {
        $penunjukan = PenunjukanPerbaikanData::findOrFail($id);

        $this->id_penunjukan = $id;
        $this->nomor_penunjukan = $penunjukan->nomorSuratCetak();
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
    }

    public function render()
    {
        $user = Auth::user();

        // Penyaringan yang sama seperti tab Pengajuan: yang tidak boleh melihat
        // semua hanya melihat yang menyangkut dirinya. Untuk tab ini "dirinya"
        // berarti dua peran — pengaju yang menunggu pekerjaannya, dan pelaksana
        // yang ditugaskan. Menyembunyikan surat dari pelaksananya sendiri akan
        // membuat penunjukan yang tidak bisa dikerjakan.
        $dapatLihatSemua = $user->hasRole('superadmin')
            || $user->hasRole('software')
            || $user->can('lihat-semua-perbaikan-data');

        $penunjukan = PenunjukanPerbaikanData::with(['perbaikanData', 'pelaksana', 'penunjuk'])
            ->when(! $dapatLihatSemua, function ($query) use ($user) {
                $query->where(function ($cabang) use ($user) {
                    $cabang->where('ditunjuk_user_id', $user->id)
                        ->orWhereHas('perbaikanData', function ($pengajuan) use ($user) {
                            $pengajuan->where('user_id', $user->id)
                                ->orWhere('pengaju', $user->name);
                        });
                });
            })
            ->when($this->search !== '', function ($query) {
                $kata = '%' . $this->search . '%';

                $query->where(function ($cabang) use ($kata) {
                    $cabang->where('kode_penunjukan', 'like', $kata)
                        ->orWhere('nomor_surat', 'like', $kata)
                        ->orWhere('nama_petugas', 'like', $kata)
                        ->orWhere('status', 'like', $kata)
                        ->orWhereHas(
                            'perbaikanData',
                            fn ($pengajuan) => $pengajuan->where('kode_pengajuan', 'like', $kata)
                        )
                        ->orWhereHas('pelaksana', fn ($orang) => $orang->where('name', 'like', $kata));
                });
            })
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.penunjukan-perbaikan-data-table', [
            'daftarPenunjukan' => $penunjukan,
        ]);
    }
}
