<?php

namespace App\Livewire;

use App\Livewire\Concerns\MengelolaLinkGambarProduk;
use App\Models\BahanSetengahjadi;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\WithPagination;

class BahanSetengahjadiTabel extends Component
{
    use MengelolaLinkGambarProduk;
    use WithPagination;
    public $search = "";
    public $perPage = 15;
    public $id_bahanSetengahjadis;
    public function render()
    {
        $bahanSetengahjadis = BahanSetengahjadi::with(['bahanSetengahjadiDetails', 'qcProdukSetengaJadi'])->orderBy('id', 'desc')
        ->where(function ($query) {
            $query->where('tgl_masuk', 'like', '%' . $this->search . '%')
                ->orWhere('kode_transaksi', 'like', '%' . $this->search . '%')
                ->orWhereHas('bahanSetengahjadiDetails', function ($query) {
                    $query->where('nama_bahan', 'like', '%' . $this->search . '%')
                    ->orWhere('serial_number', 'like', '%' . $this->search . '%');
                });
        })
            ->paginate($this->perPage);

        return view('livewire.bahan-setengahjadi-tabel', [
            'bahanSetengahjadis' => $bahanSetengahjadis,
        ]);
    }

    public function deleteBahanSetengahjadis(int $id)
    {
        $this->id_bahanSetengahjadis = $id;
    }

    protected function cariUntukLinkGambar(int $id): ?Model
    {
        return BahanSetengahjadi::find($id);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
