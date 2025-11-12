<?php

namespace App\Livewire\Quality;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Produksi;
use App\Helpers\LogHelper;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\ProduksiDetails;
use Livewire\Attributes\Layout;
use App\Models\BahanSetengahjadi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Qc1ProdukSetengahJadi;
use App\Models\Qc2ProdukSetengahJadi;
use Illuminate\Support\Facades\Storage;
use App\Models\BahanSetengahjadiDetails;
use App\Models\QcProdukSetengahJadiList;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use App\Models\QcDokumentasiProdukSetengahJadi;

#[Layout('layouts.quality', ['title' => 'QC Produk Setengah Jadi'])]
class QcProdukSetengahJadiTable extends Component
{
    use WithPagination, WithFileUploads;

    public $grade;
    public $search = '';
    public $laporan_qc;
    public $catatan;
    public $dokumentasi = [];
    public $hapus_dokumentasi = [];

    public $laporan_qc_old, $dokumentasi_lama = [];
    public $serial_number;
    public $deleteId = null;
    public $qc1ProdukSetengahjadi, $qc2ProdukSetengahjadi,$canAddGudangQCProdukSetengahjadi, $canHapusQCProdukSetengahjadi, $deleteKodeList;

    public function mount(Request $request)
    {
        $this->qc1ProdukSetengahjadi = Gate::allows('qc1-produk-setengahjadi');
        $this->qc2ProdukSetengahjadi = Gate::allows('qc2-produk-setengahjadi');
        $this->canAddGudangQCProdukSetengahjadi = Gate::allows('addgudang-qc-produk-setengahjadi');
        $this->canHapusQCProdukSetengahjadi = Gate::allows('hapus-qc-produk-setengahjadi');
    }

    public function removeDokumentasi($index)
    {
        if (isset($this->dokumentasi[$index])) {
            unset($this->dokumentasi[$index]);
            $this->dokumentasi = array_values($this->dokumentasi);
        }
    }

    public function resetForm()
    {
        $this->reset(['grade', 'laporan_qc', 'catatan', 'dokumentasi', 'dokumentasi_lama', 'laporan_qc_old']);
        $this->resetValidation();
    }

    public function loadQcData($id, $qc)
    {
        $this->resetForm();

        $model = $qc == 1
            ? Qc1ProdukSetengahJadi::with('dokumentasi')->find($id)
            : Qc2ProdukSetengahJadi::with('dokumentasi')->find($id);

        if (!$model) return;

        $this->grade = $model->grade;
        $this->catatan = $model->catatan;
        $this->laporan_qc_old = $model->laporan_qc;
        $this->dokumentasi_lama = $model->dokumentasi;

        //         dd([
        //     'qc' => $qc,
        //     'model_id' => $model->id,
        //     'data' => $model->dokumentasi,
        // ]);

    }

    public function updateQc($id, $qc)
    {
        try {

            $model = $qc == 1
                ? Qc1ProdukSetengahJadi::find($id)
                : Qc2ProdukSetengahJadi::find($id);

            $jenisQc = $qc == 1 ? 'QC 1' : 'QC 2';
            $kodeQc  = $model->kode_qc ?? '-';

            if (!$model) {
                $this->dispatch('swal:error', [
                    'title' => 'Error',
                    'text'  => 'Data QC tidak ditemukan'
                ]);
                return;
            }

            $this->validate([
                'grade'         => 'required|in:A,B',
                'laporan_qc'    => 'nullable|file|mimes:pdf|max:2048',
                'catatan'       => 'nullable|string',
                'dokumentasi.*' => 'file|mimes:jpg,jpeg,png,webp|max:4096',
            ]);

            // Upload laporan QC baru (jika ada)
            if ($this->laporan_qc) {
                // Hapus file lama kalau ada
                if ($model->laporan_qc) {
                    Storage::disk('public')->delete($model->laporan_qc);
                }

                $originalName = $this->laporan_qc->getClientOriginalName();
                $fileName = $jenisQc . '-' . $kodeQc . '-' . $originalName;
                $path = $this->laporan_qc->storeAs('laporan-qc', $fileName, 'public');
                $model->laporan_qc = $path;
            }

            $model->grade   = $this->grade;
            $model->catatan = $this->catatan;
            $model->save();

            // Upload dokumentasi baru
            if (!empty($this->dokumentasi)) {
                $foreignKey = $qc == 1 ? 'qc1_id' : 'qc2_id';
                foreach ($this->dokumentasi as $file) {
                    $originalName = $file->getClientOriginalName();
                    $fileName = $jenisQc . '-' . $kodeQc . '-' . $originalName;
                    $path = $file->storeAs('dokumentasi-qc', $fileName, 'public');

                    // $path = $file->store('dokumentasi-qc', 'public');
                    QcDokumentasiProdukSetengahJadi::create([
                        $foreignKey => $model->id,
                        'file_path' => $path
                    ]);
                }
            }

            // Hapus dokumentasi lama yang ditandai
            if (!empty($this->hapus_dokumentasi)) {
                foreach ($this->hapus_dokumentasi as $docId) {
                    $doc = QcDokumentasiProdukSetengahJadi::find($docId);
                    if ($doc) {
                        Storage::disk('public')->delete($doc->file_path);
                        $doc->delete();
                    }
                }
            }

            $this->resetForm();
            $this->hapus_dokumentasi = [];

            LogHelper::success("{$jenisQc} [{$kodeQc}] berhasil diperbarui");
            $this->dispatch('swal:success', [
                'title' => 'Berhasil',
                'text'  => "{$jenisQc} [{$kodeQc}] berhasil diperbarui"
            ]);
        } catch (\Throwable $e) {
            LogHelper::error('Gagal update data: ' . $e->getMessage());
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'Terjadi kesalahan saat update QC'
            ]);
        }
    }

    public function hapusDokumentasi($id)
    {
        // Tambahkan ke list hapus
        $this->hapus_dokumentasi[] = $id;

        // Hapus dari array tampilan sementara (supaya hilang di UI)
        $this->dokumentasi_lama = collect($this->dokumentasi_lama)
        ->reject(fn($doc) => $doc->id == $id)
        ->values();

        // dd($this->dokumentasi_lama);

    }

    public function simpanQc($id, $qc)
    {
        try {
            $this->validate([
                'grade'       => 'required|in:A,B',
                'laporan_qc'  => 'nullable|file|mimes:pdf|max:2048',
                'catatan'     => 'nullable|string',
                'dokumentasi' => 'nullable|array',
                'dokumentasi.*' => 'file|mimes:jpg,jpeg,png,webp|max:4096',
            ]);

            $kodeQc = 'QC-' . now('Asia/Jakarta')->format('YmdHis') . '-PRD';
            $jenisQc = $qc == 1 ? 'QC 1' : 'QC 2';

            // Simpan laporan QC (PDF) dengan nama custom
            $laporanPath = null;
            if ($this->laporan_qc) {
                $originalName = $this->laporan_qc->getClientOriginalName();
                $fileName = $jenisQc . '-' . $kodeQc . '-' . $originalName;
                $laporanPath = $this->laporan_qc->storeAs('laporan-qc', $fileName, 'public');
            }

            // Tentukan model QC
            $model = $qc === 1
                ? new Qc1ProdukSetengahJadi()
                : new Qc2ProdukSetengahJadi();



            $model->id_produk_setengah_jadi_list = $id;
            $model->kode_qc        = $kodeQc;
            $model->tgl_qc        = now('Asia/Jakarta');
            $model->petugas_qc        = Auth::user()->name;
            $model->grade        = $this->grade;
            $model->laporan_qc   = $laporanPath;
            $model->catatan      = $this->catatan;
            $model->save();

            // Simpan dokumentasi di tabel terpisah
            if ($this->dokumentasi && count($this->dokumentasi) > 0) {
                foreach ($this->dokumentasi as $file) {
                    $originalName = $file->getClientOriginalName();
                    $fileName = $jenisQc . '-' . $kodeQc . '-' . $originalName;
                    $path = $file->storeAs('dokumentasi-qc', $fileName, 'public');

                    $data = [
                        'file_path' => $path,
                    ];
                    if ($qc === 1) {
                        $data['qc1_id'] = $model->id;
                    } else {
                        $data['qc2_id'] = $model->id;
                    }

                    QcDokumentasiProdukSetengahJadi::create($data);
                }
            }

            // Reset form
            $this->resetForm();

            LogHelper::success("{$jenisQc} [{$kodeQc}] Produk Setengah Jadi berhasil disimpan!");
            $this->dispatch('swal:success', [
                'title' => 'Berhasil',
                'text'  => "{$jenisQc} [{$kodeQc}] Produk Setengah Jadi berhasil disimpan!"
            ]);
            return redirect()->route('quality-page.qc-produk-setengah-jadi.index');
        } catch (\Throwable $th) {
            // dd($th->getMessage());
            LogHelper::error('Gagal menyimpan data: ' . $th->getMessage());
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'Gagal menyimpan data',
            ]);
        }
    }

    private function generateSerialNumber($qc)
    {
        // Ambil tanggal selesai produksi (fallback ke now kalau null)
        $tanggalSelesai = $qc->selesai_produksi ?: Carbon::now('Asia/Jakarta');

        $jenisSn = strtolower($qc->jenis_sn);

        // Jika vendor → tidak generate
        if ($jenisSn === 'vendor') {
            return null;
        }

        // Hitung urutan tahunan (reset tiap tahun, dan per bahan_id)
        $lastTahunan = QcProdukSetengahJadiList::whereYear('selesai_produksi', $tanggalSelesai->year)
            ->where('bahan_id', $qc->bahan_id) // hanya produk dengan bahan yang sama
            ->whereNotNull('serial_number')
            ->selectRaw("MAX(CAST(RIGHT(serial_number, 3) AS UNSIGNED)) as last_no")
            ->value('last_no');

        $urutanTahunan = ($lastTahunan ?? 0) + 1;


        // Jumlah unit batch produksi (UT)
        $jumlahUnitBatch = Produksi::find($qc->produksi_id)?->jml_produksi ?? 0;

        // Default bluetooth 000 jika kosong
        $idBluetooth = $qc->id_bluetooth ?: '000';

        // Format tanggal
        $yy = $tanggalSelesai->format('y');
        $mm = $tanggalSelesai->format('m');
        $dd = $tanggalSelesai->format('d');

        // --- NON-WIRING ---
        if ($jenisSn === 'non-wiring') {
            return sprintf(
                "%s%s%s%02d%02s%03s%03d",
                $yy,                  // Tahun 2 digit
                $mm,                  // Bulan 2 digit
                $dd,                  // Hari 2 digit
                $jumlahUnitBatch,     // UT (2 digit)
                $qc->kode_jenis_unit, // PR (2 digit)
                str_pad($idBluetooth, 3, '0', STR_PAD_LEFT), // BLT (3 digit)
                $urutanTahunan        // SEQ (3 digit)
            );
        }

        // --- WIRING ---
        if ($jenisSn === 'wiring') {
            return sprintf(
                "%s%s%s%02d%02s%03s%03d",
                $yy,                       // Tahun 2 digit
                $mm,                       // Bulan 2 digit
                $dd,                       // Hari 2 digit
                $jumlahUnitBatch,          // UT (2 digit)
                $qc->kode_jenis_unit,      // PR (2 digit)
                str_pad($qc->kode_wiring_unit, 3, '0', STR_PAD_LEFT), // PRO (3 digit)
                $urutanTahunan             // SEQ (3 digit)
            );
        }

        return null;
    }


    public function generateSerialNumberLive($id)
    {
        $qc = QcProdukSetengahJadiList::findOrFail($id);
        return $this->generateSerialNumber($qc);
    }

    public function prosesKeGudang($id, $serial)
    {
        try {
            $qc = QcProdukSetengahJadiList::with('produksi')->findOrFail($id);

            if (!$serial) {
                $serial = $this->generateSerialNumber($qc);
            }

            DB::transaction(function () use ($qc, $serial) {
                // Buat transaksi purchase
                $bahan_setengahjadis = BahanSetengahjadi::create([
                    'kode_transaksi' => $qc->kode_list,
                    'tgl_masuk'      => Carbon::now('Asia/Jakarta'),
                    'id_qc_produk_setengahjadi' => $qc->id,
                ]);

                BahanSetengahjadiDetails::create([
                    'bahan_setengahjadi_id' => $bahan_setengahjadis->id,
                    'bahan_id'      => $qc->produksi->bahan_id,
                    'qty'           => $qc->qty,
                    'sisa'          => $qc->qty,
                    'unit_price'    => $qc->unit_price ?? 0,
                    'sub_total'     => $qc->unit_price ? $qc->unit_price * $qc->qty : 0,
                    'serial_number' => $serial,
                    'nama_bahan'    => $qc->produksi->dataBahan->nama_bahan,
                ]);

                // Update tanggal masuk gudang terakhir (jika semua sukses)
                $qc->update([
                    'tanggal_masuk_gudang' => Carbon::now('Asia/Jakarta'),
                    'serial_number' => $serial,
                ]);
            });

            LogHelper::success('Berhasil Menambahkan Produk Di QC Produk Setengah Jadi Ke Gudang!');
            session()->flash('success', 'Berhasil Menambahkan Produk Di QC Produk Setengah Jadi Ke Gudang!');
        } catch (\Exception $e) {
            LogHelper::error('Gagal memproses ke gudang: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat memproses ke gudang: ' . $e->getMessage());
        }

        return redirect()->route('quality-page.qc-produk-setengah-jadi.index');
    }

    public function confirmDelete($id)
    {
        $qc = QcProdukSetengahJadiList::find($id);
        $this->deleteId = $id;
        $this->deleteKodeList = $qc ? $qc->kode_list : '-';
        $this->dispatch('open-delete-modal', id: $id, kodeList: $this->deleteKodeList);
    }

    public function deleteItem($id)
    {
        try {
            $kodeList = null;

            DB::transaction(function () use ($id, &$kodeList) {
                $qc = QcProdukSetengahJadiList::find($id);

                if (!$qc) {
                    throw new \Exception("Data dengan ID $id tidak ditemukan");
                }

                $kodeList = $qc->kode_list;

                // Hapus QC1
                if ($qc->qc1) {
                    if ($qc->qc1->laporan_qc) {
                        Storage::disk('public')->delete($qc->qc1->laporan_qc);
                    }
                    foreach ($qc->qc1->dokumentasi as $doc) {
                        Storage::disk('public')->delete($doc->file_path);
                        $doc->delete();
                    }
                    $qc->qc1->delete();
                }

                // Hapus QC2
                if ($qc->qc2) {
                    if ($qc->qc2->laporan_qc) {
                        Storage::disk('public')->delete($qc->qc2->laporan_qc);
                    }
                    foreach ($qc->qc2->dokumentasi as $doc) {
                        Storage::disk('public')->delete($doc->file_path);
                        $doc->delete();
                    }
                    $qc->qc2->delete();
                }

                $qc->delete();
            });

            LogHelper::success("Data dengan kode list [$kodeList] berhasil dihapus beserta file-file QC");
            return redirect()->route('quality-page.qc-produk-setengah-jadi.index');
            $this->dispatch('swal:success', [
                'title' => 'Berhasil',
                'text'  => "Data dengan kode list [$kodeList] berhasil dihapus beserta file-file QC"
            ]);
        } catch (\Throwable $e) {
            LogHelper::error("Gagal hapus data [$kodeList]: " . $e->getMessage());
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'Gagal menghapus data: ' . $e->getMessage()
            ]);
        }
    }



    public function render()
    {
        $qcList = QcProdukSetengahJadiList::with(['produksi', 'produksi.dataBahan', 'qc1', 'qc2'])
            ->where(function ($query) {
                    $query->where('kode_list', 'like', '%' . $this->search . '%')
                        ->orWhere('mulai_produksi', 'like', '%' . $this->search . '%')
                        ->orWhere('selesai_produksi', 'like', '%' . $this->search . '%')
                        ->orWhere('serial_number', 'like', '%' . $this->search . '%')
                        ->orWhere('jenis_sn', 'like', '%' . $this->search . '%')
                        ->orWhere('id_bluetooth', 'like', '%' . $this->search . '%')
                        ->orWhere('kode_jenis_unit', 'like', '%' . $this->search . '%')
                        ->orWhere('kode_wiring_unit', 'like', '%' . $this->search . '%')
                        ->orWhere('tanggal_masuk_gudang', 'like', '%' . $this->search . '%')
                        ->orWhereHas('produksi.dataBahan', fn($q) => $q->where('nama_bahan', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('qc1', fn($q) => $q->where('kode_qc', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('qc2', fn($q) => $q->where('kode_qc', 'like', '%' . $this->search . '%'));
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10);

        return view('livewire.quality.qc-produk-setengah-jadi-table', [
            'qcList' => $qcList,
        ]);
    }
}
