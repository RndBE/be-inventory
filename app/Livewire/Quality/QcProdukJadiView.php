<?php

namespace App\Livewire\Quality;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\QcProdukJadiList;


#[Layout('layouts.quality', ['title' => 'Detail QC Produk Jadi'])]
class QcProdukJadiView extends Component
{
    public $list;

    public function mount($id)
    {
        $this->list = QcProdukJadiList::with([
            'produksiProdukJadi',
            'qc1', 'qc1.dokumentasi',
            'qc2', 'qc2.dokumentasi'
        ])->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.quality.qc-produk-jadi-view');
    }
}
