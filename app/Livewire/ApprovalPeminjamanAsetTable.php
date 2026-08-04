<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\PeminjamanAset;

/**
 * Layar kerja approver. Semua tombol approve dan pencatatan pengembalian ada di sini,
 * terpisah dari layar pemohon (PeminjamanAsetTable) yang hanya memantau status.
 */
class ApprovalPeminjamanAsetTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 25;
    public $selectedTab = 'semua';
    public $filterDivisi = '';

    public $id_peminjaman, $kode_peminjaman;
    public $isDetailModalOpen = false;
    public $isApproveModalOpen = false;
    public $isPengembalianModalOpen = false;

    public $tahapApprove;
    public $kendalaTahap;
    public $detailPeminjaman;
    public $peminjamanPengembalian;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterDivisi()
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
        $query = PeminjamanAset::with([
            'dataUser.dataJobPosition',
            'dataRuangan',
            'peminjamanAsetDetails.dataAset.barangAset',
            'peminjamanAsetDetails.dataAset.dataRuangan',
            'peminjamanAsetDetails.buktiFoto',
            'approvalKendalas.dataUser',
        ]);

        // Pembatasan harus ikut berlaku di sini. Tanpa ini, approver bisa memanggil
        // showDetail() dengan id sembarang dari sisi klien dan membaca pengajuan
        // divisi lain yang barisnya sengaja disembunyikan dari tabel.
        $this->detailPeminjaman = $query->terlihatOleh(Auth::user())->findOrFail($id);

        $this->isDetailModalOpen = true;
    }

    public function openApprove(int $id, string $tahap)
    {
        $peminjaman = PeminjamanAset::with('approvalKendalas')->findOrFail($id);

        $this->id_peminjaman = $id;
        $this->tahapApprove = $tahap;
        $this->kode_peminjaman = $peminjaman->kode_peminjaman;

        // Tampilkan kendala yang sudah ada supaya approver bisa memperbarui,
        // bukan menulis ulang dari nol.
        $label = [
            'leader' => 'Leader',
            'manager' => 'Manager',
            'ga' => 'General Affair',
            'hrd' => 'HRD',
        ][$tahap] ?? $tahap;
        $this->kendalaTahap = $peminjaman->kendalaApproval($label);

        $this->isApproveModalOpen = true;
    }

    /**
     * Membuka modal pencatatan pengembalian untuk satu pengajuan sekaligus.
     * Aset mana saja yang dicatat dipilih di dalam modal, bukan lewat menu,
     * supaya dropdown tidak membengkak mengikuti jumlah aset.
     */
    public function openPengembalian(int $peminjamanId)
    {
        $this->peminjamanPengembalian = PeminjamanAset::with('peminjamanAsetDetails.dataAset.barangAset')
            ->terlihatOleh(Auth::user())
            ->findOrFail($peminjamanId);

        $this->isPengembalianModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDetailModalOpen = false;
        $this->isApproveModalOpen = false;
        $this->isPengembalianModalOpen = false;
        $this->detailPeminjaman = null;
        $this->peminjamanPengembalian = null;
        $this->tahapApprove = null;
        $this->kendalaTahap = null;
    }

    public function render()
    {
        $user = Auth::user();

        $query = PeminjamanAset::with([
            'dataUser',
            'dataRuangan',
            'peminjamanAsetDetails.dataAset.barangAset',
            // Dipakai penanda "ditugaskan tetap" di daftar-aset-status. Keduanya
            // wajib di-eager load: accessor ditugaskan_tetap membaca dataPic dan
            // peminjamanAktif, dan tanpa ini tiap baris aset memicu query sendiri.
            'peminjamanAsetDetails.dataAset.dataPic',
            'peminjamanAsetDetails.dataAset.peminjamanAktif',
            'approvalKendalas',
        ]);

        // Aturan tunggal untuk kedua layar, didefinisikan di PeminjamanAset::scopeTerlihatOleh().
        $query->terlihatOleh($user);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('kode_peminjaman', 'like', '%' . $this->search . '%')
                    ->orWhere('divisi', 'like', '%' . $this->search . '%')
                    ->orWhere('keperluan', 'like', '%' . $this->search . '%')
                    ->orWhereHas('dataUser', function ($sub) {
                        $sub->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('dataRuangan', function ($sub) {
                        $sub->where('nama_ruangan', 'like', '%' . $this->search . '%')
                            ->orWhere('kode_ruangan', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('peminjamanAsetDetails.dataAset', function ($sub) {
                        $sub->where('nomor_aset', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('peminjamanAsetDetails.dataAset.barangAset', function ($sub) {
                        $sub->where('nama_barang', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if ($this->filterDivisi !== '') {
            $query->where('divisi', $this->filterDivisi);
        }

        match ($this->selectedTab) {
            // 'semua' tidak menyaring apa pun — pembatasan divisi di atas tetap berlaku.
            'pengajuan' => $query->where('status', '!=', 'Ditolak')
                ->where('status_hrd', '!=', 'Ditolak')
                ->where(function ($q) {
                    $q->where('status', '!=', 'Disetujui')->orWhere('status_hrd', '!=', 'Disetujui');
                }),
            'diproses' => $query->bolehDikeluarkan()->where('status_pengembalian', '!=', 'Selesai'),
            'selesai' => $query->bolehDikeluarkan()->where('status_pengembalian', 'Selesai'),
            'ditolak' => $query->ditolak(),
            default => null,
        };

        $peminjamans = $query->orderByDesc('id')->paginate($this->perPage);

        return view('livewire.approval-peminjaman-aset-table', [
            'peminjamans' => $peminjamans,
        ]);
    }
}
