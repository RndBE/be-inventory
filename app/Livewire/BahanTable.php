<?php

namespace App\Livewire;

use App\Models\Bahan;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\BahanSetengahjadiDetails;

class BahanTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 25;
    public $id_bahan, $nama_bahan, $jenis_bahan_id, $stok_awal, $total_stok, $penempatan, $supplier, $unit_id, $kondisi, $gambar, $kode_bahan;
    public $selectedIds = [];
    public $isDeleteModalOpen = false;
    public $isShowModalOpen = false;
    public $currentPage;

    public function mount()
    {
        $this->resetModalState();
    }

    public function resetModalState()
    {
        $this->isDeleteModalOpen = false;
        $this->isShowModalOpen = false;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function bulkEdit()
    {
        if (!empty($this->selectedIds)) {
            return redirect()->route('bahan.editmultiple', ['ids' => $this->selectedIds]);
        }
    }

    public function showBahan(int $id)
    {
        $Data = Bahan::with('purchaseDetails')->findOrFail($id);
        $this->id_bahan = $id;
        $this->kode_bahan = $Data->kode_bahan;
        $this->nama_bahan = $Data->nama_bahan;
        $this->jenis_bahan_id = $Data->jenisBahan->nama ?? 'N/A';
        $this->stok_awal = $Data->stok_awal;
        $this->total_stok = $Data->purchaseDetails->sum('sisa');
        $this->penempatan = $Data->penempatan;
        $this->supplier = $Data->dataSupplier->nama ?? 'N/A';
        $this->kondisi = $Data->kondisi;
        $this->unit_id = $Data->dataUnit->nama ?? 'N/A';
        $this->gambar = $Data->gambar;
        $this->isShowModalOpen = true;
    }


    public function deleteBahan(int $id, $page)
    {
        $this->id_bahan = $id;
        $this->currentPage = $page;
        $this->isDeleteModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
        $this->isShowModalOpen = false;
    }

    public function render()
    {
        $bahans = Bahan::with('jenisBahan', 'dataUnit', 'purchaseDetails')
            ->where(function ($query) {
                $query->where('nama_bahan', 'like', '%' . $this->search . '%')
                    ->orWhere('penempatan', 'like', '%' . $this->search . '%')
                    ->orWhere('kode_bahan', 'like', '%' . $this->search . '%')
                    ->orWhereHas('jenisBahan', function ($query) {
                        $query->where('nama', 'like', '%' . $this->search . '%');
                    })->orWhereHas('dataUnit', function ($query) {
                        $query->where('nama', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('dataSupplier', function ($query) {
                        $query->where('nama', 'like', '%' . $this->search . '%');
                    });
            })
            ->paginate($this->perPage);


        // Ambil daftar nama_bahan yang ada pada page hasil paginate saat ini
        $names = $bahans->pluck('nama_bahan')->filter()->unique()->values()->all();

        // Ambil aggregate sisa dari BahanSetengahjadiDetails berdasarkan nama_bahan
        $sisaSums = BahanSetengahjadiDetails::whereIn('nama_bahan', $names)
            ->where('sisa', '>', 0)
            ->selectRaw('nama_bahan, SUM(sisa) as total_sisa')
            ->groupBy('nama_bahan')
            ->pluck('total_sisa', 'nama_bahan'); // returns [ 'nama_bahan' => total_sisa, ... ]

        // Hitung total_stok tiap bahan: jika jenis PRODUKSI pakai sisa dari setengahjadi (berdasarkan nama),
        // selain itu pakai sisa dari purchaseDetails
        foreach ($bahans as $bahan) {
            if ($bahan->jenisBahan && strtoupper($bahan->jenisBahan->nama) === 'PRODUKSI') {
                $bahan->total_stok = isset($sisaSums[$bahan->nama_bahan]) ? (float) $sisaSums[$bahan->nama_bahan] : 0;
            } else {
                $bahan->total_stok = $bahan->purchaseDetails->sum('sisa');
            }
        }

        return view('livewire.bahan-table', [
            'bahans' => $bahans
        ]);
    }
}
