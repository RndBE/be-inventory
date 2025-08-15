<?php

// app/Livewire/QcBahanMasuk.php

namespace App\Livewire\Quality;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Purchase;
use App\Helpers\LogHelper;
use App\Models\QcBahanMasuk;
use Livewire\WithPagination;
use App\Models\PurchaseDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Layout;
use Illuminate\Contracts\View\View;

#[Layout('layouts.quality', ['title' => 'QC Bahan Masuk'])]
class QcBahanMasukTable extends Component
{
    use WithPagination;

    public $search = '';
    public $showDeleteModal = false;
    public $deleteId;
    public $pdfData;
    public $qc;

    protected $paginationTheme = 'tailwind';

    public function render(): View
    {
        $data = QcBahanMasuk::with(['petugasQc', 'petugasInputQc', 'pembelianBahan'])
            ->where(function ($query) {
                $query->where('kode_qc', 'like', '%' . $this->search . '%')
                    ->orWhere('tanggal_qc', 'like', '%' . $this->search . '%')
                    ->orWhere('keterangan_qc', 'like', '%' . $this->search . '%')
                    ->orWhereHas('petugasQc', fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
                    ->orWhereHas('petugasInputQc', fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
                    ->orWhereHas('pembelianBahan', fn($q) => $q->where('kode_transaksi', 'like', '%' . $this->search . '%'));
            })
            ->orderBy('tanggal_qc', 'desc')
            ->paginate(10);

        return view('livewire.qc-bahan-masuk-table', [
            'qcList' => $data,
        ]);
    }

    public function prosesKeGudang($id)
    {
        try {
            $qc = QcBahanMasuk::with('details')->findOrFail($id);

            // Ambil hanya bahan yang fisik_baik > 0
            $validDetails = $qc->details->filter(function ($detail) {
                return $detail->fisik_baik > 0;
            });

            // Jika tidak ada bahan valid, hentikan proses
            if ($validDetails->isEmpty()) {
                session()->flash('error', 'Tidak ada bahan dengan fisik baik yang bisa diproses ke gudang.');
                return redirect()->route('quality-page.qc-bahan-masuk.index');
            }

            // Update tanggal masuk gudang
            $qc->tanggal_masuk_gudang = Carbon::now('Asia/Jakarta');
            $qc->save();

            // Buat transaksi purchase
            $kode_transaksi = 'KBM-' . strtoupper(uniqid());
            $purchase = Purchase::create([
                'kode_transaksi'        => $kode_transaksi,
                'tgl_masuk'             => Carbon::now('Asia/Jakarta'),
                'id_qc_bahan_masuk'     => $qc->id_qc_bahan_masuk,
            ]);

            // Simpan detail pembelian dari QC details yang valid
            foreach ($validDetails as $detail) {
                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'bahan_id'    => $detail->bahan_id,
                    'qty'         => $detail->fisik_baik,
                    'sisa'        => $detail->fisik_baik,
                    'unit_price'  => $detail->unit_price,
                    'sub_total'   => $detail->sub_total,
                ]);
            }

            LogHelper::success('Berhasil Menambahkan List Bahan Di QC Bahan Masuk Ke Transaksi Bahan Masuk!');
            session()->flash('success', 'Barang berhasil dimasukkan ke gudang.');
        } catch (\Exception $e) {
            LogHelper::error('Gagal memproses ke gudang: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat memproses ke gudang: ' . $e->getMessage());
        }

        return redirect()->route('quality-page.qc-bahan-masuk.index');
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed()
    {
        QcBahanMasuk::destroy($this->deleteId);
        $this->showDeleteModal = false;
        session()->flash('message', 'Data berhasil dihapus.');
    }
}

