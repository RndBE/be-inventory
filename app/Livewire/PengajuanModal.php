<?php

namespace App\Livewire;

use Livewire\Component;

class PengajuanModal extends Component
{
    public $showModal = true;

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.pengajuan-modal');
    }
}
