<?php

namespace App\Livewire\Quality;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\QcProdukSetengahJadiList;

#[Layout('layouts.quality', ['title' => 'Detail QC Produk Setengah Jadi'])]
class QcProdukSetengahJadiView extends Component
{
    public $list;

    public function mount($id)
    {
        $this->list = QcProdukSetengahJadiList::with([
            'produksi',
            'qc1', 'qc1.dokumentasi',
            'qc2', 'qc2.dokumentasi'
        ])->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.quality.qc-produk-setengah-jadi-view');
    }
}
