<?php

namespace App\Livewire\Quality;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.quality', ['title' => 'QC Produk Setengah Jadi'])]
class QcProdukSetengahJadiTable extends Component
{
    public function render()
    {
        return view('livewire.quality.qc-produk-setengah-jadi-table');
    }
}
