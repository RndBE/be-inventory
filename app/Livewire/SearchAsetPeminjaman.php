<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\RekapAset;
use Livewire\WithPagination;

class SearchAsetPeminjaman extends Component
{
    use WithPagination;

    public $query = '';
    public $hanyaTersedia = false;
    public $perPage = 6;

    /**
     * Id aset yang sudah masuk keranjang, dikirim oleh AsetPeminjamanCart.
     */
    public $terpilih = [];

    protected $listeners = ['asetTerpilihBerubah' => 'perbaruiTerpilih'];

    public function perbaruiTerpilih($ids = [])
    {
        $this->terpilih = $ids;
    }

    public function updatedQuery()
    {
        $this->resetPage();
    }

    public function updatedHanyaTersedia()
    {
        $this->resetPage();
    }

    public function pilihAset(int $id)
    {
        $this->dispatch('asetSelected', asetId: $id);
    }

    public function render()
    {
        $asetList = RekapAset::with('barangAset', 'dataRuangan', 'dataPic', 'peminjamanAktif.peminjamanAset.dataUser')
            ->when($this->query !== '', function ($builder) {
                $builder->where(function ($sub) {
                    $sub->where('nomor_aset', 'like', '%' . $this->query . '%')
                        ->orWhere('serial_number', 'like', '%' . $this->query . '%')
                        ->orWhereHas('barangAset', function ($barang) {
                            $barang->where('nama_barang', 'like', '%' . $this->query . '%');
                        })
                        ->orWhereHas('dataRuangan', function ($ruangan) {
                            $ruangan->where('nama_ruangan', 'like', '%' . $this->query . '%');
                        });
                });
            })
            ->when($this->hanyaTersedia, function ($builder) {
                // Definisi "tersedia" wajib sama dengan relasi peminjamanAktif dan
                // pengecekan bentrok di controller: aset baru terhitung keluar setelah
                // GA menyetujui DAN HRD mengetahui. Kalau di sini hanya mengecek approval
                // GA, aset yang tertahan di HRD ikut tersembunyi padahal badge-nya "Tersedia".
                $builder->whereDoesntHave('peminjamanDetails', function ($detail) {
                    $detail->where('status_pengembalian', 'Belum dikembalikan')
                        ->whereHas('peminjamanAset', function ($header) {
                            $header->bolehDikeluarkan();
                        });
                });
            })
            ->orderBy('nomor_aset')
            ->paginate($this->perPage);

        return view('livewire.search-aset-peminjaman', [
            'asetList' => $asetList,
        ]);
    }
}
