<?php

namespace App\Livewire\Quality;

use App\Models\User;
use Livewire\Component;
use App\Models\Produksi;
use App\Helpers\LogHelper;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use App\Models\QcProdukSetengahJadiList;

#[Layout('layouts.quality', ['title' => 'Tambah QC'])]
class QcProdukSetengahJadiWizard extends Component
{
    use WithPagination, WithFileUploads;
    protected $queryString = ['search', 'sortBy', 'perPage'];

    public $step = 1;
    public $search = '';
    public $sortBy = 'asc';
    public $perPage = 12;

    public $selected_produksi_id = null;
    public $selected_petugas_id;
    public $produksiList;
    public $searchProduksi = '';

    public $selectedProdukList = [];


    public function mount()
    {
        $this->produksiList = Produksi::where('status', 'Selesai')->get();
    }

    public function getFilteredProduksiSetengahJadiListProperty()
    {
        return $this->produksiList->filter(fn ($item) =>
            str_contains(strtolower($item->kode_produksi), strtolower($this->searchProduksi))
        );
    }

    public function nextStep()
    {
        if ($this->step === 1) {
            $this->validate([
                'selected_produksi_id' => 'required',
                'selected_petugas_id' => 'required',
            ], [
                'selected_produksi_id.required' => 'Silakan pilih kode produksi.',
                'selected_petugas_id.required' => 'Silakan pilih tim produksi',
            ]);

            // Simpan input step 1 ke session
            session()->put('selected_produksi_id', $this->selected_produksi_id);
            session()->put('selected_petugas_id', $this->selected_petugas_id);
        }

        if ($this->step < 2) {
            $this->step++;

            if ($this->step === 2) {
                $produksi = Produksi::with(['produksiDetails', 'dataBahan'])->find($this->selected_produksi_id);

                $existingKodeList = QcProdukSetengahJadiList::pluck('kode_list')->toArray();

                $this->selectedProdukList = [];
                // dd($produksi->toArray());

                if ($produksi && $produksi->dataBahan && $produksi->produksiDetails) {
                    // Hitung total biaya semua bahan produksi
                    $totalSubTotal = $produksi->produksiDetails->sum('sub_total');
                    // Harga per 1 produk (dibagi jumlah produksi)
                    $unitPrice = $produksi->jml_produksi > 0 ? $totalSubTotal / $produksi->jml_produksi : 0;
                    foreach (range(1, $produksi->jml_produksi) as $i) {
                        $kodeList = ($produksi->kode_produksi ?? '') . '-' . ($i . '/' . $produksi->jml_produksi);

                        $this->selectedProdukList[] = [
                            'bahan_id'      => $produksi->dataBahan->id,
                            'nama_bahan'    => $produksi->dataBahan->nama_bahan ?? '',
                            'nomor'         => $i . '/' . $produksi->jml_produksi,
                            'kode_produksi' => $produksi->kode_produksi ?? '',
                            'kode_list'       => $kodeList,
                            'mulai_produksi' => $produksi->mulai_produksi ?? '',
                            'qty'           => 1,
                            'unit_price'    => $unitPrice,
                            'sub_total'     => $unitPrice,
                            'is_selected'   => false,
                            'is_disabled'     => in_array($kodeList, $existingKodeList),
                        ];
                    }
                }
                // dd($this->selectedProdukList);
            }
        }
    }

    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }

        // Simpan juga agar jika user mundur ke step 1, datanya masih ada
        session()->put('selected_produksi_id', $this->selected_produksi_id);
        session()->put('selected_petugas_id', $this->selected_petugas_id);
    }

    public function goToStep($step)
    {
        if ($step >= 1 && $step <= 3) {
            $this->step = $step;
        }
    }

    // public function simpanQcProduk()
    // {
    //     // Filter hanya yang dipilih
    //     $produkDipilih = collect($this->selectedProdukList)->where('is_selected', true);

    //     if ($produkDipilih->isEmpty()) {
    //         $this->dispatch('notify', ['type' => 'error', 'message' => 'Tidak ada produk yang dipilih!']);
    //         return;
    //     }

    //     foreach ($produkDipilih as $produk) {
    //         QcProdukSetengahJadiList::create([
    //             'kode_produksi' => $produk['kode_produksi'],
    //             'bahan_id'      => $produk['bahan_id'],
    //             'qty'           => $produk['qty'],
    //             'unit_price'    => $produk['unit_price'],
    //             'sub_total'     => $produk['sub_total'],
    //             'mulai_produksi'=> $produk['mulai_produksi'],
    //             'selesai_produksi'=> now('Asia/Jakarta'),
    //             'petugas_produksi'    => $this->selected_petugas_id ?? session('selected_petugas_id'),
    //         ]);
    //     }

    //     $this->dispatch('notify', ['type' => 'success', 'message' => 'QC Produk berhasil disimpan!']);

    //     // Reset session dan data list
    //     $this->selectedProdukList = [];
    //     session()->forget('selected_produk_list');
    // }

    public function simpanQcProduk()
    {
        DB::beginTransaction();
        try {
            $produksi = Produksi::find($this->selected_produksi_id);

            if (!$produksi) {
                LogHelper::error('Data produksi tidak ditemukan!');
                session()->flash('error', 'Data produksi tidak ditemukan!');
                return;
            }
            // Filter hanya yang dipilih
            $produkDipilih = collect($this->selectedProdukList)->where('is_selected', true);
            // dd($produkDipilih->toArray());
            if ($produkDipilih->isEmpty()) {
                LogHelper::error('Data produksi tidak ditemukan!');
                session()->flash('error', 'Tidak ada produk yang dipilih!');
                return;
            }

            foreach ($produkDipilih as $produk) {
                //  dd($produk);
                QcProdukSetengahJadiList::create([
                    'produksi_id' => $produksi->id,
                    'kode_list'      => $produk['kode_produksi'] . '-' . $produk['nomor'],
                    'bahan_id'      => $produk['bahan_id'],
                    'qty'           => $produk['qty'],
                    'unit_price'    => $produk['unit_price'],
                    'sub_total'     => $produk['sub_total'],
                    'mulai_produksi'=> $produk['mulai_produksi'],
                    'selesai_produksi'=> now('Asia/Jakarta'),
                    'petugas_produksi'    => $this->selected_petugas_id ?? session('selected_petugas_id'),
                ]);
            }

            DB::commit();
            LogHelper::success('Produk Setengah Jadi berhasil disimpan!');
            session()->flash('success', 'Produk Setengah Jadi berhasil disimpan!');
            return redirect()->route('quality-page.qc-produk-setengah-jadi.index');

        } catch (\Throwable $th) {
            // dd($th->getMessage());
            DB::rollBack();
            LogHelper::error('Gagal menyimpan data: ' . $th->getMessage());
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'Gagal menyimpan data',
            ]);
        }
    }


    public function render()
    {
        return view('livewire.quality.qc-produk-setengah-jadi-wizard');
    }
}
