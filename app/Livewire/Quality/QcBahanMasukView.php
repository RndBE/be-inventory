<?php

namespace App\Livewire\Quality;

use Livewire\Component;
use App\Models\QcBahanMasuk;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Layout;

#[Layout('layouts.quality', ['title' => 'Detail QC Bahan Masuk'])]
class QcBahanMasukView extends Component
{
    public $id_qc_bahan_masuk;
    public $qc;
    public $pdfData;
    public $selectedBahanList = [];
    public $selectedBahanListFisikBaik = [];
    public $gambarPerBahan = [];
    public $is_verified;
    public $keterangan_qc;
    public $petugas_qc_nama;
    public $petugas_qc_ttd;
    public $petugas_input_qc_nama;
    public $petugas_input_qc_ttd;


    public function mount($id_qc_bahan_masuk)
    {
        $this->id = $id_qc_bahan_masuk;
        $this->qc = QcBahanMasuk::with(['petugasQc', 'petugasInputQc', 'pembelianBahan', 'details.bahan', 'details.dokumentasi'])
            ->findOrFail($id_qc_bahan_masuk);

        // Mapping data: bahan_id => array dokumentasi
        $this->gambarPerBahan = [];
        foreach ($this->qc->details as $detail) {
            $this->gambarPerBahan[$detail->bahan_id] = $detail->dokumentasi;
        }

        // Simpan list bahan untuk filter
        $this->selectedBahanList = $this->qc->details->map(function ($detail) {
            return [
                'bahan_id'   => $detail->bahan_id,
                'nama_bahan' => $detail->bahan->nama_bahan,
            ];
        })->toArray();
        $this->is_verified = $this->qc->is_verified;
        $this->keterangan_qc = $this->qc->keterangan_qc;

        // Data petugas QC
        $this->petugas_qc_nama       = $this->qc->petugasQc->name ?? null;
        $this->petugas_qc_ttd        = $this->qc->petugasQc->tanda_tangan ?? null; // misal kolom tanda_tangan

        // Data petugas Input QC
        $this->petugas_input_qc_nama = $this->qc->petugasInputQc->name ?? null;
        $this->petugas_input_qc_ttd  = $this->qc->petugasInputQc->tanda_tangan ?? null; // misal kolom tanda_tangan


        // Simpan list bahan untuk filter: hanya yang jumlah fisik baik > 0
        $this->selectedBahanListFisikBaik = $this->qc->details
            ->filter(function ($detail) {
                return $detail->fisik_baik > 0;
            })
            ->map(function ($detail) {
                return [
                    'bahan_id'   => $detail->bahan_id,
                    'nama_bahan' => $detail->bahan->nama_bahan,
                    'fisik_baik' => $detail->fisik_baik,
                    'unit_price' => $detail->unit_price,
                    'sub_total' => $detail->sub_total,
                ];
            })
            ->values() // reset index array
            ->toArray();

    }

    public function streamPdf()
    {
        $pdf = Pdf::loadView('livewire.quality.qc-bahan-masuk-pdf', [
            'qc' => $this->qc
        ]);

        // Simpan stream PDF dalam base64 agar bisa di-embed
        $this->pdfData = 'data:application/pdf;base64,' . base64_encode($pdf->output());
    }

    public function render()
    {
        return view('livewire.quality.qc-bahan-masuk-view');
    }
}
