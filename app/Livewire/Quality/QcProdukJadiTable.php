<?php

namespace App\Livewire\Quality;

use Carbon\Carbon;
use Livewire\Component;
use App\Helpers\LogHelper;
use App\Models\ProdukJadis;
use Livewire\WithPagination;
use App\Models\Qc1ProdukJadi;
use App\Models\Qc2ProdukJadi;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\QcProdukJadiList;
use App\Models\ProdukJadiDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\QcDokumentasiProdukJadi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

#[Layout('layouts.quality', ['title' => 'QC Produk Jadi'])]
class QcProdukJadiTable extends Component
{
    use WithPagination, WithFileUploads;

    public $grade;
    public $laporan_qc;
    public $catatan;
    public $dokumentasi = [];
    public $hapus_dokumentasi = [];

    public $laporan_qc_old, $dokumentasi_lama = [];
    public $serial_number;
    public $deleteId = null;
    public $qc1ProdukJadi, $qc2ProdukJadi,$canAddGudangQCProdukJadi, $canHapusQCProdukJadi;

    public function mount(Request $request)
    {
        $this->qc1ProdukJadi = Gate::allows('qc1-produk-jadi');
        $this->qc2ProdukJadi = Gate::allows('qc2-produk-jadi');
        $this->canAddGudangQCProdukJadi = Gate::allows('addgudang-qc-produk-jadi');
        $this->canHapusQCProdukJadi = Gate::allows('hapus-qc-produk-jadi');
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
            ? Qc1ProdukJadi::with('dokumentasi')->find($id)
            : Qc2ProdukJadi::with('dokumentasi')->find($id);

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
                ? Qc1ProdukJadi::find($id)
                : Qc2ProdukJadi::find($id);

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
                    QcDokumentasiProdukJadi::create([
                        $foreignKey => $model->id,
                        'file_path' => $path
                    ]);
                }
            }

            // Hapus dokumentasi lama yang ditandai
            if (!empty($this->hapus_dokumentasi)) {
                foreach ($this->hapus_dokumentasi as $docId) {
                    $doc = QcDokumentasiProdukJadi::find($docId);
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
                'laporan_qc'  => 'required|file|mimes:pdf|max:2048',
                'catatan'     => 'nullable|string',
                'dokumentasi' => 'nullable|array',
                'dokumentasi.*' => 'file|mimes:jpg,jpeg,png,webp|max:4096',
            ]);

            $kodeQc = 'QC-' . now('Asia/Jakarta')->format('YmdHis') . '-PRDJD';
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
                ? new Qc1ProdukJadi()
                : new Qc2ProdukJadi();



            $model->id_produk_jadi_list = $id;
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

                    QcDokumentasiProdukJadi::create($data);
                }
            }

            // Reset form
            $this->resetForm();

            LogHelper::success("{$jenisQc} [{$kodeQc}] Produk Jadi berhasil disimpan!");
            $this->dispatch('swal:success', [
                'title' => 'Berhasil',
                'text'  => "{$jenisQc} [{$kodeQc}] Produk Jadi berhasil disimpan!"
            ]);
            return redirect()->route('quality-page.qc-produk-jadi.index');
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
        $tanggalSelesai = $qc->selesai_produksi
            ? Carbon::parse($qc->selesai_produksi)->timezone('Asia/Jakarta')
            : Carbon::now('Asia/Jakarta');

        // Hitung jumlah produksi harian yang SUDAH memiliki serial number
        $jumlahHarian = QcProdukJadiList::whereDate('selesai_produksi', $tanggalSelesai->toDateString())
            ->count();

        // Tentukan ID tim produksi
        $petugas = strtoupper(trim($qc->petugas_produksi));
        $idTim = match ($petugas) {
            'RASYID PRIYO NUGROHO' => '01',
            'ENDARTO NUGROHO'      => '02',
            default => '99', // fallback kalau ada nama lain
        };

        // Hitung urutan logger tahunan berdasarkan tanggal selesai_produksi
        $lastTahunan = QcProdukJadiList::whereYear('selesai_produksi', $tanggalSelesai->year)
            ->whereNotNull('serial_number')
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(serial_number, '-', -1) AS UNSIGNED)) as last_no")
            ->value('last_no');

        $urutanTahunan = ($lastTahunan ?? 0) + 1;

        // Format serial number (YYYY-MM-DD-Harian-IDTim-Tahunan)
        return sprintf(
            "%04d-%02d-%02d-%03d-%s-%05d",
            $tanggalSelesai->year,
            $tanggalSelesai->month,
            $tanggalSelesai->day,
            $jumlahHarian,
            $idTim,
            $urutanTahunan
        );
    }




    public function generateSerialNumberLive($id)
    {
        $qc = QcProdukJadiList::findOrFail($id);
        return $this->generateSerialNumber($qc);
    }

    public function prosesKeGudang($id, $serial)
    {
        try {
            $qc = QcProdukJadiList::with('produksiProdukJadi')->findOrFail($id);

            if (!$serial) {
                $serial = $this->generateSerialNumber($qc);
            }

            DB::transaction(function () use ($qc, $serial) {
                $produk_jadis = ProdukJadis::create([
                    'kode_transaksi' => $qc->kode_list,
                    'tgl_masuk'      => Carbon::now('Asia/Jakarta'),
                    'id_qc_produk_jadi' => $qc->id,
                ]);

                ProdukJadiDetails::create([
                    'produk_jadis_id' => $produk_jadis->id,
                    'produk_id'      => $qc->produksiProdukJadi->produk_id,
                    'qty'           => $qc->qty,
                    'sisa'          => $qc->qty,
                    'unit_price'    => $qc->unit_price ?? 0,
                    'sub_total'     => $qc->unit_price ? $qc->unit_price * $qc->qty : 0,
                    'serial_number' => $serial,
                    'nama_produk'    => $qc->produksiProdukJadi->dataProdukJadi->nama_produk,
                ]);

                $qc->update([
                    'tanggal_masuk_gudang' => Carbon::now('Asia/Jakarta'),
                    'serial_number' => $serial,
                ]);
            });

            LogHelper::success('Berhasil Menambahkan Produk Di QC Produk Jadi Ke Gudang!');
            session()->flash('success', 'Berhasil Menambahkan Produk Di QC Produk Jadi Ke Gudang!');
        } catch (\Exception $e) {
            LogHelper::error('Gagal memproses ke gudang: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat memproses ke gudang: ' . $e->getMessage());
        }

        return redirect()->route('quality-page.qc-produk-jadi.index');
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->dispatch('open-delete-modal', id: $id);
    }

    public function deleteItem($id)
    {
        try {
            $kodeList = null;
            DB::transaction(function () use ($id, &$kodeList) {
                $qc = QcProdukJadiList::findOrFail($id);
                $kodeList = $qc->kode_list;

                // Hapus QC1 dan file-file terkait
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

                // Hapus QC2 dan file-file terkait
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

                // Terakhir hapus list utamanya
                $qc->delete();
            });

            LogHelper::success("Data dengan kode list [$kodeList] berhasil dihapus beserta file-file QC");
            $this->dispatch('swal:success', [
                'title' => 'Berhasil',
                'text'  => "Data dengan kode list [$kodeList] berhasil dihapus beserta file-file QC"
            ]);
        } catch (\Throwable $e) {
            LogHelper::error("Gagal hapus data [$kodeList]: " . $e->getMessage());
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'Gagal menghapus data'
            ]);
        }
    }

    public function render()
    {
        $qcList = QcProdukJadiList::with('produksiProdukJadi')
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

        return view('livewire.quality.qc-produk-jadi-table', [
            'qcList' => $qcList,
        ]);
    }

    // public function render()
    // {
    //     return view('livewire.quality.qc-produk-jadi-table');
    // }
}
