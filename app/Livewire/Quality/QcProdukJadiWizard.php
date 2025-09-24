<?php

namespace App\Livewire\Quality;

use Livewire\Component;
use App\Helpers\LogHelper;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\QcProdukJadiList;
use App\Models\ProduksiProdukJadi;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.quality', ['title' => 'Tambah QC'])]
class QcProdukJadiWizard extends Component
{
    use WithPagination, WithFileUploads;
    protected $queryString = ['search', 'sortBy', 'perPage'];

    public $step = 1;
    public $search = '';
    public $sortBy = 'asc';
    public $perPage = 12;

    public $selected_produksi_produk_jadi_id = null;
    // public $selected_petugas_id;
    public $produksiProdukjadiList;
    public $searchProduksi = '';

    public $selectedProdukJadiList = [];


    public function mount()
    {
        $this->produksiProdukjadiList = ProduksiProdukJadi::where('status', 'Selesai')->get();
    }

    public function getFilteredProduksiJadiListProperty()
    {
        return $this->produksiProdukjadiList->filter(fn ($item) =>
            str_contains(strtolower($item->kode_produksi), strtolower($this->searchProduksi))
        );
    }

    public function nextStep()
    {
        if ($this->step === 1) {
            $this->validate([
                'selected_produksi_produk_jadi_id' => 'required',
                // 'selected_petugas_id' => 'required',
            ], [
                'selected_produksi_produk_jadi_id.required' => 'Silakan pilih kode produksi.',
                // 'selected_petugas_id.required' => 'Silakan pilih tim produksi',
            ]);

            // Simpan input step 1 ke session
            session()->put('selected_produksi_produk_jadi_id', $this->selected_produksi_produk_jadi_id);
            // session()->put('selected_petugas_id', $this->selected_petugas_id);
        }

        if ($this->step < 2) {
            $this->step++;

            if ($this->step === 2) {
                $produksi = ProduksiProdukJadi::with(['produksiProdukJadiDetails', 'dataProdukJadi'])->find($this->selected_produksi_produk_jadi_id);

                $existingKodeList = QcProdukJadiList::pluck('kode_list')->toArray();

                $this->selectedProdukJadiList = [];
                // dd($produksi->toArray());

                if ($produksi && $produksi->dataProdukJadi && $produksi->produksiProdukJadiDetails) {
                    // Hitung total biaya semua bahan produksi
                    $totalSubTotal = $produksi->produksiProdukJadiDetails->sum('sub_total');
                    // Harga per 1 produk (dibagi jumlah produksi)
                    $unitPrice = $produksi->jml_produksi > 0 ? $totalSubTotal / $produksi->jml_produksi : 0;
                    foreach (range(1, $produksi->jml_produksi) as $i) {
                        $kodeList = ($produksi->kode_produksi ?? '') . '-' . ($i . '/' . $produksi->jml_produksi);

                        $this->selectedProdukJadiList[] = [
                            'produk_jadi_id'      => $produksi->dataProdukJadi->id,
                            'nama_produk'    => $produksi->dataProdukJadi->nama_produk ?? '',
                            'nomor'         => $i . '/' . $produksi->jml_produksi,
                            'kode_produksi' => $produksi->kode_produksi ?? '',
                            'kode_list'       => $kodeList,
                            'mulai_produksi' => $produksi->mulai_produksi ?? '',
                            'qty'           => 1,
                            'unit_price'    => $unitPrice,
                            'sub_total'     => $unitPrice,
                            'is_selected'   => false,
                            'is_disabled'     => in_array($kodeList, $existingKodeList),

                            'id_logger'        => null,
                        ];
                    }
                }
            }
        }
    }

    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }

        // Simpan juga agar jika user mundur ke step 1, datanya masih ada
        session()->put('selected_produksi_produk_jadi_id', $this->selected_produksi_produk_jadi_id);
        // session()->put('selected_petugas_id', $this->selected_petugas_id);
    }

    public function goToStep($step)
    {
        if ($step >= 1 && $step <= 3) {
            $this->step = $step;
        }
    }

    public function simpanQcProduk()
    {
        DB::beginTransaction();
        try {
            $produksi = ProduksiProdukJadi::find($this->selected_produksi_produk_jadi_id);

            if (!$produksi) {
                LogHelper::error('Data produksi produk jadi tidak ditemukan!');
                session()->flash('error', 'Data produksi produk jadi tidak ditemukan!');
                return;
            }
            // Filter hanya yang dipilih
            $produkDipilih = collect($this->selectedProdukJadiList)->where('is_selected', true);
            // dd($produkDipilih->toArray());
            if ($produkDipilih->isEmpty()) {
                LogHelper::error('Data produksi produk jadi tidak ditemukan!');
                session()->flash('error', 'Tidak ada produk yang dipilih!');
                return;
            }

            foreach ($produkDipilih as $index => $produk) {
                if (empty($produk['id_logger'])) {
                    DB::rollBack();
                    $this->addError("selectedProdukJadiList.$index.id_logger", "Kode List tidak boleh kosong.");
                    $this->dispatch('swal:error', [
                        'title' => 'Error',
                        'text'  => "Kode List pada produk {$produk['nama_bahan']} belum diisi!",
                    ]);
                    return;
                }
                //  dd($produk);
                QcProdukJadiList::create([
                    'produksi_produk_jadi_id' => $produksi->id,
                    'kode_list'      => $produk['kode_produksi'] . '-' . $produk['nomor'],
                    'produk_jadi_id'      => $produk['produk_jadi_id'],
                    'qty'           => $produk['qty'],
                    'unit_price'    => $produk['unit_price'],
                    'sub_total'     => $produk['sub_total'],
                    'mulai_produksi'=> $produk['mulai_produksi'],
                    'selesai_produksi'=> now('Asia/Jakarta'),
                    // 'petugas_produksi'    => $this->selected_petugas_id ?? session('selected_petugas_id'),

                    'id_logger'=> $produk['id_logger'],
                ]);
            }

            DB::commit();
            LogHelper::success('Produk Jadi berhasil disimpan!');
            session()->flash('success', 'Produk Jadi berhasil disimpan!');
            return redirect()->route('quality-page.qc-produk-jadi.index');

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
        return view('livewire.quality.qc-produk-jadi-wizard');
    }
}
