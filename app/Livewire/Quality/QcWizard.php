<?php

namespace App\Livewire\Quality;

use App\Models\User;
use App\Models\Bahan;
use Livewire\Component;
use App\Models\Supplier;
use App\Helpers\LogHelper;
use App\Models\QcBahanMasuk;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\PembelianBahan;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Layout;
use App\Models\HasilQcBahanMasuk;
use Illuminate\Support\Facades\DB;
use App\Models\QcBahanMasukDetails;
use Illuminate\Support\Facades\Auth;
use App\Models\DokumentasiQcBahanMasuk;

#[Layout('layouts.quality', ['title' => 'Tambah QC'])]

class QcWizard extends Component
{
    use WithPagination, WithFileUploads;
    protected $queryString = ['search', 'sortBy', 'perPage'];

    public $step = 1;
    public $search = '';
    public $sortBy = 'asc';
    public $perPage = 12;

    public $selected_pembelian_id;
    public $selected_petugas_id;
    public $selected_supplier_id;
    public $selected_bahan_id = null;
    public $selectedBahanList = [];

    public $keterangan;

    public $pembelianList;
    public $petugasList;
    public $supplierList;
    public $dokumentasiTemp = [];

    public $searchPembelian = '';
    public $searchPetugas = '';
    public $searchSupplier = '';

    public $gambarPerBahan = [];
    public $keterangan_qc;

    public $qc_notes;
    public $is_verified = false;
    public $selected = [];
    public $statusBahan = [];
    public $filteredBahanList = [];




    public function mount()
    {
        $this->selectedBahanList = session()->get('selected_bahan_list', []);
        $this->supplierList = Supplier::orderBy('nama', 'asc')->get();
        $this->pembelianList = PembelianBahan::whereIn('jenis_pengajuan', [
            'Pembelian Bahan/Barang/Alat Lokal',
            'Pembelian Bahan/Barang/Alat Impor',
        ])
        ->where('status_pembelian', 'Diproses')
        ->get();
        $this->petugasList = User::whereHas('dataOrganization', function ($query) {
            $query->where('nama', 'Hardware');
        })->get();
    }

    public function removeImage($bahanId, $index)
    {
        if (isset($this->gambarPerBahan[$bahanId][$index])) {
            array_splice($this->gambarPerBahan[$bahanId], $index, 1);
            // Optional: reset indeks agar rapi
            $this->gambarPerBahan[$bahanId] = array_values($this->gambarPerBahan[$bahanId]);
        }
    }

    public function getFilteredPembelianListProperty()
    {
        return $this->pembelianList->filter(fn ($item) =>
            str_contains(strtolower($item->kode_transaksi), strtolower($this->searchPembelian))
        );
    }

    public function getFilteredPetugasListProperty()
    {
        return $this->petugasList->filter(fn ($item) =>
            str_contains(strtolower($item->name), strtolower($this->searchPetugas))
        );
    }

    public function getFilteredSupplierListProperty()
    {
        return $this->supplierList->filter(fn ($item) =>
            str_contains(strtolower($item->nama), strtolower($this->searchSupplier))
        );
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSortBy()
    {
        $this->resetPage();
    }

    public function updatedSelectedPembelianId($id)
    {
        $pembelian = PembelianBahan::with('pembelianBahanDetails.dataBahan.purchaseDetails')->find($id);

        if ($pembelian) {
            $this->selectedBahanList = $pembelian->pembelianBahanDetails->map(function ($detail) {
                $stokLama = $detail->dataBahan->purchaseDetails->sum('sisa');
                return [
                    'bahan_id'          => $detail->dataBahan->id,
                    'nama_bahan'        => $detail->dataBahan->nama_bahan,
                    'stok_lama'         => $stokLama ?? 0,
                    'jumlah_pengajuan'  => $detail->jml_bahan ?? 0,
                    'no_invoice'        => '',
                    'jumlah_diterima'   => '',
                    'fisik_baik'        => '',
                    'fisik_rusak'       => '',
                    'fisik_retur'       => '',
                    'unit_price'        => '',
                    'statusQc'          => '',
                    'notes'             => '',
                    'supplier_id'       => '',
                    'is_selected'       => false,
                ];
            })->toArray();

            // Reset gambarPerBahan agar tidak ada key lama yang kosong
            $this->gambarPerBahan = [];

            // Inisialisasi keranjang gambar baru sesuai bahan yang baru dipilih
            foreach ($this->selectedBahanList as $row) {
                $bid = $row['bahan_id'];
                $this->gambarPerBahan[$bid] = [];
            }
        } else {
            $this->selectedBahanList = [];
            $this->gambarPerBahan = [];
        }
    }


    public function nextStep()
    {
        if ($this->step === 1) {
            $this->validate([
                'selected_pembelian_id' => 'required',
                'selected_petugas_id' => 'required',
            ], [
                'selected_pembelian_id.required' => 'Silakan pilih kode pembelian.',
                'selected_petugas_id.required' => 'Silakan pilih petugas QC.',
            ]);

            // Simpan input step 1 ke session
            session()->put('selected_pembelian_id', $this->selected_pembelian_id);
            session()->put('selected_petugas_id', $this->selected_petugas_id);
        }
        if ($this->step === 2) {
            // Filter hanya bahan yang dipilih (is_selected = true)
            $filteredBahan = collect($this->selectedBahanList)
                ->where('is_selected', true)
                ->values()
                ->toArray();

            // Jika semua OFF, tampilkan pesan error
            if (count($filteredBahan) === 0) {
                $this->addError('selectedBahanList', 'Minimal 1 bahan harus dipilih (ON).');
                return; // stop, jangan lanjut step
            }
            // Validasi hanya bahan yang ON
            foreach ($filteredBahan as $index => $bahan) {
                $this->validate([
                    "selectedBahanList.$index.no_invoice" => 'required|string',
                    "selectedBahanList.$index.supplier_id" => 'required|exists:supplier,id',
                    "selectedBahanList.$index.jumlah_diterima" => 'required|numeric|min:0',
                    "selectedBahanList.$index.fisik_baik" => 'required|numeric|min:0',
                    "selectedBahanList.$index.fisik_rusak" => 'required|numeric|min:0',
                    "selectedBahanList.$index.fisik_retur" => 'required|numeric|min:0',
                    "selectedBahanList.$index.unit_price" => 'required|numeric|min:0',
                    "selectedBahanList.$index.statusQc" => 'required|in:Belum Diterima,Diterima Semua,Diterima Sebagian,Ditolak',
                ], [
                    // Pesan error kustom opsional
                    "selectedBahanList.$index.no_invoice.required" => "No Invoice wajib diisi.",
                    "selectedBahanList.$index.supplier_id.required" => "Pilih supplier.",
                    "selectedBahanList.$index.jumlah_diterima" => 'Jumlah Diterima wajib diisi.',
                    "selectedBahanList.$index.fisik_baik" => 'Fisik Baik wajib diisi.',
                    "selectedBahanList.$index.fisik_rusak" => 'Fisik Rusak wajib diisi.',
                    "selectedBahanList.$index.fisik_retur" => 'Fisik Retur wajib diisi.',
                    "selectedBahanList.$index.unit_price" => 'Harga wajib diisi.',
                    "selectedBahanList.$index.statusQc.required" => "Status QC wajib dipilih.",
                ]);
            }
            // Simpan ke session hanya bahan ON
            session()->put('selected_bahan_list', $filteredBahan);

            // Jangan overwrite list asli, cukup buat property baru
            $this->filteredBahanList = $filteredBahan;
        }

        if ($this->step < 4) {
            $this->step++;
        }
    }


    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }

        // Simpan juga agar jika user mundur ke step 1, datanya masih ada
        session()->put('selected_pembelian_id', $this->selected_pembelian_id);
        session()->put('selected_petugas_id', $this->selected_petugas_id);
    }

    public function goToStep($step)
    {
        if ($step >= 1 && $step <= 4) {
            $this->step = $step;
        }
    }

    public function saveQCBahanMasuk()
    {
        $this->validate([
            'is_verified' => 'accepted',
            'selected_pembelian_id' => 'required|exists:pembelian_bahan,id',
            'selected_petugas_id' => 'required|exists:users,id',
        ], [
            'is_verified.accepted' => 'Anda harus mencentang pernyataan verifikasi.',
        ]);

        // Ambil hanya bahan yang ON
        $filteredBahan = collect($this->selectedBahanList)
            ->where('is_selected', true)
            ->values()
            ->toArray();

        // Validasi minimal 1 ON
        if (count($filteredBahan) === 0) {
            $this->addError('selectedBahanList', 'Minimal 1 bahan harus dipilih (ON).');
            return;
        }

        // Validasi detail hanya untuk bahan ON
        foreach ($filteredBahan as $index => $bahan) {
            $this->validate([
                "selectedBahanList.$index.no_invoice" => 'required|string',
                "selectedBahanList.$index.supplier_id" => 'required|exists:supplier,id',
                "selectedBahanList.$index.jumlah_diterima" => 'required|numeric|min:0',
                "selectedBahanList.$index.fisik_baik" => 'required|numeric|min:0',
                "selectedBahanList.$index.fisik_rusak" => 'required|numeric|min:0',
                "selectedBahanList.$index.fisik_retur" => 'required|numeric|min:0',
                "selectedBahanList.$index.unit_price" => 'required|numeric|min:0',
                "selectedBahanList.$index.statusQc" => 'required|in:Belum Diterima,Diterima Semua,Diterima Sebagian,Ditolak',
            ]);
        }

        DB::beginTransaction();
        try {
            // Simpan QC utama
            $qc = QcBahanMasuk::create([
                'id_pembelian_bahan'   => $this->selected_pembelian_id,
                'kode_qc'              => 'QC-' . now('Asia/Jakarta')->format('YmdHis') . '-BM',
                'tanggal_qc'           => now('Asia/Jakarta'),
                'keterangan_qc'        => $this->keterangan_qc ?? null,
                'id_petugas_qc'        => $this->selected_petugas_id,
                'id_petugas_input_qc' => Auth::user()->id,
                'is_verified'          => $this->is_verified ? 1 : 0,
            ]);

            // Simpan detail bahan hanya untuk yang ON
            foreach ($filteredBahan as $bahan) {
                $detail = QcBahanMasukDetails::create([
                    'id_qc_bahan_masuk' => $qc->id_qc_bahan_masuk,
                    'bahan_id'          => $bahan['bahan_id'],
                    'supplier_id'       => $bahan['supplier_id'],
                    'no_invoice'        => $bahan['no_invoice'],
                    'jumlah_pengajuan'  => $bahan['jumlah_pengajuan'],
                    'stok_lama'         => $bahan['stok_lama'],
                    'jumlah_diterima'   => $bahan['jumlah_diterima'],
                    'fisik_baik'        => $bahan['fisik_baik'],
                    'fisik_rusak'       => $bahan['fisik_rusak'],
                    'fisik_retur'       => $bahan['fisik_retur'],
                    'unit_price'        => $bahan['unit_price'],
                    'sub_total'         => $bahan['fisik_baik'] * $bahan['unit_price'],
                    'status'            => match($bahan['statusQc']) {
                        'Belum Diterima' => 'Belum Diterima',
                        'Diterima Semua' => 'Diterima Semua',
                        'Diterima Sebagian' => 'Diterima Sebagian',
                        'Ditolak' => 'Ditolak',
                        default => 'Belum Diterima'
                    },
                    'notes'             => $bahan['notes'] ?? null,
                ]);

                // Simpan foto hanya jika ada
                if (!empty($this->gambarPerBahan[$bahan['bahan_id']])) {
                    foreach ($this->gambarPerBahan[$bahan['bahan_id']] as $foto) {
                        $originalName = $foto->getClientOriginalName();
                        $filename = $qc->kode_qc . '-' . $originalName;

                        $path = $foto->storeAs('qc_bahan_masuk', $filename, 'public');

                        DokumentasiQcBahanMasuk::create([
                            'qc_bahan_masuk_detail_id' => $detail->id,
                            'bahan_id'                 => $bahan['bahan_id'],
                            'gambar'                   => $path,
                        ]);
                    }
                }
            }

            DB::commit();
            LogHelper::success('Data QC Bahan Masuk berhasil disimpan.');
            session()->flash('success', 'Data QC Bahan Masuk berhasil disimpan.');
            return redirect()->route('quality-page.qc-bahan-masuk.index');
        } catch (\Throwable $th) {
            DB::rollBack();
            LogHelper::error('Gagal menyimpan data: ' . $th->getMessage());
            session()->flash('error', 'Gagal menyimpan data: ' . $th->getMessage());
        }
    }



    public function render()
    {
        $bahanList = [];

        if ($this->step === 2) {
            $bahanList = Bahan::with(['jenisBahan', 'dataUnit', 'purchaseDetails'])
                ->where(function ($query) {
                    $query->where('nama_bahan', 'like', '%' . $this->search . '%')
                        ->orWhere('kode_bahan', 'like', '%' . $this->search . '%')
                        ->orWhere('penempatan', 'like', '%' . $this->search . '%')
                        ->orWhereHas('jenisBahan', function ($q) {
                            $q->where('nama', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('dataUnit', function ($q) {
                            $q->where('nama', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('dataSupplier', function ($q) {
                            $q->where('nama', 'like', '%' . $this->search . '%');
                        });
                })
                ->orderBy('nama_bahan', $this->sortBy)
                ->paginate($this->perPage);

            // Hitung total stok tersisa
            foreach ($bahanList as $bahan) {
                $bahan->total_stok = $bahan->purchaseDetails->sum('sisa');
            }
        }
        return view('livewire.quality.qc-wizard', [
            'bahanList' => $bahanList,
            'pembelianList' => $this->pembelianList,
            'petugasList' => $this->petugasList,
            'supplierList' => $this->supplierList,
        ]);
    }
}

