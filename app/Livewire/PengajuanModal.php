<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Session;

class PengajuanModal extends Component
{
    public $showModal = false;

    public function mount()
    {
        // Cek apakah session 'modal_shown' sudah ada
        if (!Session::has('modal_shown')) {
            $this->showModal = true;
            Session::put('modal_shown', true); // Tandai modal sudah pernah ditampilkan
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.pengajuan-modal');
    }
}
