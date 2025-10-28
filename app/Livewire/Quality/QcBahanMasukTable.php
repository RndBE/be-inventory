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
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

#[Layout('layouts.quality', ['title' => 'QC Bahan Masuk'])]
class QcBahanMasukTable extends Component
{
    use WithPagination;

    public $search = '';
    public $showDeleteModal = false;
    public $deleteId;
    public $pdfData;
    public $qc;
    public $canAddGudangQCBahanMasuk, $canHapusQCBahanMasuk;
    public $selectedTab = 'SudahMasukGudang';

    protected $paginationTheme = 'tailwind';

    public function mount(Request $request)
    {
        $this->canAddGudangQCBahanMasuk = Gate::allows('addgudang-qc-bahan-masuk');
        $this->canHapusQCBahanMasuk = Gate::allows('hapus-qc-bahan-masuk');
    }

    public function setTab($tab)
    {
        $this->selectedTab = $tab;
    }

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
//             ->when($this->selectedTab === 'SudahMasukGudang', function ($query) {
//     // ✅ Data yang sudah masuk gudang = tanggal_masuk_gudang valid
//     $query->whereNotNull('tanggal_masuk_gudang')
//           ->whereDate('tanggal_masuk_gudang', '>', '2000-01-01');
// })
// ->when($this->selectedTab === 'BelumMasukGudang', function ($query) {
//     // ✅ Data yang belum masuk gudang = tanggal_masuk_gudang null atau <= 2000
//     $query->where(function ($q) {
//         $q->whereNull('tanggal_masuk_gudang')
//           ->orWhereDate('tanggal_masuk_gudang', '<=', '2000-01-01');
//     });
// })

            ->orderBy('tanggal_qc', 'desc')
            ->paginate(10);

        return view('livewire.qc-bahan-masuk-table', [
            'qcList' => $data,
        ]);
    }

    public function prosesKeGudang($id_qc_bahan_masuk)
    {
        try {
            $qc = QcBahanMasuk::with('details')->findOrFail($id_qc_bahan_masuk);

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

    public function confirmDelete($id_qc_bahan_masuk)
    {
        $this->deleteId = $id_qc_bahan_masuk;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed()
    {
        DB::beginTransaction();

        try {
            $qc = QcBahanMasuk::with(['details.dokumentasi', 'purchase'])->findOrFail($this->deleteId);

            // Cek apakah sudah ada transaksi
            if ($qc->purchase) {
                LogHelper::error('QC Bahan Masuk ini tidak dapat dihapus karena sudah memiliki transaksi di gudang.');
                session()->flash('error', 'QC Bahan Masuk ini tidak dapat dihapus karena sudah memiliki transaksi di gudang.');
                return redirect()->route('quality-page.qc-bahan-masuk.index');
            }

            // Hapus dokumentasi (foto) terkait detail
            foreach ($qc->details as $detail) {
                foreach ($detail->dokumentasi as $doc) {
                    if ($doc->gambar && Storage::disk('public')->exists($doc->gambar)) {
                        Storage::disk('public')->delete($doc->gambar);
                    }
                    $doc->delete();
                }
                $detail->delete();
            }

            // Hapus record QC utama
            $qc->delete();

            DB::commit();
            LogHelper::success('Data QC Bahan Masuk beserta detail dan dokumentasinya berhasil dihapus.');
            session()->flash('success', 'Data QC Bahan Masuk beserta detail dan dokumentasinya berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            LogHelper::error('Gagal menghapus data: ' . $e->getMessage());
            session()->flash('error', 'Gagal menghapus data');
        }

        $this->showDeleteModal = false;
        return redirect()->route('quality-page.qc-bahan-masuk.index');
    }
}

