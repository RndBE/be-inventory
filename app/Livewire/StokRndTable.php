<?php

namespace App\Livewire;

use App\Models\StokRnd;
use Livewire\Component;

class StokRndTable extends Component
{
    public $search = '';
    public $perPage = 5;

    public function render()
    {
        $stokRnds = StokRnd::with('dataBahan.dataUnit')
            ->whereHas('dataBahan', function ($query) {
                $query->where('nama_bahan', 'like', '%' . $this->search . '%')
                    ->orWhere('kode_bahan', 'like', '%' . $this->search . '%');
            })->paginate($this->perPage);

            return view('livewire.stok-rnd-table', [
                'stokRnds' => $stokRnds
        ]);
    }
}
