<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\PeminjamanAset;

class PeminjamanAsetTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 25;
    public $selectedTab = 'semua';
    public $filterDivisi = '';

    public $isDetailModalOpen = false;

    public $detailPeminjaman;

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

        // Wajib memakai pembatasan yang sama dengan daftarnya. Tanpa ini, siapa pun
        // bisa memanggil showDetail() dengan id sembarang dari sisi klien dan membaca
        // pengajuan orang lain — menyembunyikan barisnya di tabel saja tidak cukup.
        $this->detailPeminjaman = $query->terlihatOleh(Auth::user())->findOrFail($id);

        $this->isDetailModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDetailModalOpen = false;
        $this->detailPeminjaman = null;
    }

    public function render()
    {
        $query = PeminjamanAset::with([
            'dataUser',
            'dataRuangan',
            'peminjamanAsetDetails.dataAset.barangAset',
            // Dipakai penanda "ditugaskan tetap" di daftar-aset-status.
            'peminjamanAsetDetails.dataAset.dataPic',
            'peminjamanAsetDetails.dataAset.peminjamanAktif',
            'approvalKendalas',
        ]);

        // Aturan tunggal untuk kedua layar, didefinisikan di PeminjamanAset::scopeTerlihatOleh().
        $query->terlihatOleh(Auth::user());

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
            // Masih berjalan di rantai approval, termasuk yang sudah lolos GA
            // tapi belum diketahui HRD sehingga asetnya belum boleh keluar.
            'pengajuan' => $query->where('status', '!=', 'Ditolak')
                ->where('status_hrd', '!=', 'Ditolak')
                ->where(function ($q) {
                    $q->where('status', '!=', 'Disetujui')->orWhere('status_hrd', '!=', 'Disetujui');
                }),
            // Aset sudah boleh keluar dan masih di tangan peminjam
            'diproses' => $query->bolehDikeluarkan()->where('status_pengembalian', '!=', 'Selesai'),
            'selesai' => $query->bolehDikeluarkan()->where('status_pengembalian', 'Selesai'),
            'ditolak' => $query->ditolak(),
            default => null,
        };

        $peminjamans = $query->orderByDesc('id')->paginate($this->perPage);

        return view('livewire.peminjaman-aset-table', [
            'peminjamans' => $peminjamans,
        ]);
    }
}
