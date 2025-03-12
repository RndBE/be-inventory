<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Bahan;
use App\Models\Projek;
use Livewire\Component;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;
use App\Models\GaransiProjek;
use App\Models\LaporanProyek;
use App\Models\LaporanGaransiProyek;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Support\Facades\Validator;

class CreateLaporanGaransiProyek extends Component
{
    public $garansi_proyek;
    public $garansiProjekDetails = [];
    public $dataBahanRusak = [];
    public $dataBahan = [];
    public $dataProduk = [];
    public $produksiStatus;
    public $itemsAset = [];
    public $proyek_id;

    public $savedItemsAset = [];
    public $deletedItemsAset = [];
    public $editingIndex = null;
    public $originalData = [];
    public $canEditBiayaTambahan, $canDeleteBiayaTambahan, $canTambahBaris, $canSimpanLaporan, $canEditAnggaran;
    public $anggaran;
    public $editingAnggaran = false;




    public function mount(Request $request)
    {
        $this->canEditBiayaTambahan = Gate::allows('edit-biaya-tambahan-garansi');
        $this->canDeleteBiayaTambahan = Gate::allows('hapus-biaya-tambahan-garansi');
        $this->canTambahBaris = Gate::allows('tambah-baris-garansi');
        $this->canSimpanLaporan = Gate::allows('simpan-laporan-garansi');
        $this->canEditAnggaran = Gate::allows('edit-anggaran-garansi');

        $proyek_id = $request->query('garansi_proyek_id');
        // dd($proyek_id);
        $this->garansi_proyek = GaransiProjek::with([
            'garansiProjekDetails',
            'bahanKeluar',
            'dataKontrak',
            'dataBahanRusak.bahanRusakDetails.dataBahan',
            'dataBahanRusak.bahanRusakDetails.dataProduk'
        ])->find($proyek_id);

        $this->anggaran = $this->garansi_proyek->anggaran ?? null;

        // Ambil data laporan garansi proyek yang sudah tersimpan
        $this->savedItemsAset = LaporanGaransiProyek::where('garansi_projek_id', $proyek_id)->get()->toArray();

        // Pastikan garansiProjekDetails ada dan dalam bentuk array
        if ($this->garansi_proyek && $this->garansi_proyek->garansiProjekDetails) {
            $this->garansiProjekDetails = $this->garansi_proyek->garansiProjekDetails->toArray();
            $this->dataBahanRusak = $this->garansi_proyek->dataBahanRusak->toArray();
        }

        $this->dataBahan = Bahan::pluck('nama_bahan', 'id')->toArray();
        $this->dataProduk = BahanSetengahjadiDetails::pluck('nama_bahan', 'id')->toArray();

        foreach ($this->savedItemsAset as &$item) {
            $item['tanggal'] = Carbon::parse($item['tanggal'])->format('Y-m-d');
        }

    }

    public function editAnggaran()
    {
        $this->editingAnggaran = true;
    }

    public function saveAnggaran()
    {
        // $validator = Validator::make([
        //     'anggaran' => 'nullable|numeric|min:0',
        // ],[
        //     'anggaran.numeric' => 'Anggaran harus berupa angka.',
        // ]);

        $validator = Validator::make(
            ['anggaran' => $this->anggaran],
            ['anggaran' => 'nullable|numeric|min:0'],
            [
                'anggaran.numeric' => 'Anggaran harus berupa angka.',
                'anggaran.min' => 'Anggaran tidak boleh kurang dari 0.',
            ]
        );

        if ($validator->fails()) {
            $this->dispatch('show-toast', [
                'message' => 'Validasi gagal: <br>' . implode('<br>', $validator->errors()->all()),
                'type' => 'error'
            ]);
            return;
        }

        $this->garansi_proyek->update(['anggaran' => $this->anggaran]);

        $this->editingAnggaran = false;
        $this->dispatch('show-toast', ['message' => 'Anggaran berhasil diperbarui.']);
    }

    public function cancelEditAnggaran()
    {
        $this->anggaran = $this->garansi_proyek->anggaran;
        $this->editingAnggaran = false;
    }

    public function addRow()
    {
        $this->itemsAset[] = [
            'tanggal' => '',
            'nama_biaya_tambahan' => '',
            'qty' => '',
            'satuan' => '',
            'unit_price' => '',
            'total_biaya' => '',
            'keterangan' => '',
        ];
    }

    public function saveRow($index)
    {
        $this->itemsAset[$index]['editing'] = false;
    }

    public function removeRow($index)
    {
        unset($this->itemsAset[$index]);
        $this->itemsAset = array_values($this->itemsAset);
    }

    public function deleteSavedRow($id)
    {
        // Tandai data untuk dihapus saat tombol Simpan ditekan
        $this->deletedItemsAset[] = $id;

        // Hapus secara langsung dari tampilan (tanpa reload)
        $this->savedItemsAset = array_filter($this->savedItemsAset, function ($item) use ($id) {
            return $item['id'] != $id;
        });
    }

    public function editRow($index)
    {
        $this->originalData[$index] = $this->savedItemsAset[$index];
        $this->editingIndex = $index;
    }

    // Fungsi untuk menyimpan perubahan
    public function updateRow($index)
    {
        $data = $this->savedItemsAset[$index];

        // Validasi data yang diupdate
        $validator = Validator::make($data, [
            'tanggal' => 'required|date',
            'nama_biaya_tambahan' => 'required|string|max:255',
            'qty' => 'required|numeric',
            'satuan' => 'nullable|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'total_biaya' => 'nullable',
            'keterangan' => 'nullable|string|max:500',
        ],[
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Format tanggal tidak valid.',
            'nama_biaya_tambahan.required' => 'Nama biaya tambahan wajib diisi.',
            'nama_biaya_tambahan.string' => 'Nama biaya tambahan harus berupa teks.',
            'nama_biaya_tambahan.max' => 'Nama biaya tambahan maksimal 255 karakter.',
            'qty.required' => 'Jumlah (Qty) wajib diisi.',
            'qty.numeric' => 'Jumlah (Qty) harus berupa angka.',
            'satuan.string' => 'Satuan harus berupa teks.',
            'satuan.max' => 'Satuan maksimal 50 karakter.',
            'unit_price.required' => 'Unit price wajib diisi.',
            'unit_price.numeric' => 'Unit price harus berupa angka.',
            'unit_price.min' => 'Unit price tidak boleh negatif.',
            'keterangan.string' => 'Keterangan harus berupa teks.',
            'keterangan.max' => 'Keterangan maksimal 500 karakter.',
        ]);

        if ($validator->fails()) {
            $this->dispatch('show-toast', [
                'message' => 'Validasi gagal: <br>' . implode('<br>', $validator->errors()->all()),
                'type' => 'error'
            ]);
            return;
        }
        $tanggalFormatted = Carbon::parse($data['tanggal'])->format('Y-m-d H:i:s');
        $total_biaya = $data['qty'] * $data['unit_price'];

        LaporanGaransiProyek::where('id', $data['id'])->update([
            'tanggal' => $tanggalFormatted,
            'nama_biaya_tambahan' => $data['nama_biaya_tambahan'],
            'qty' => $data['qty'],
            'satuan' => $data['satuan'],
            'unit_price' => $data['unit_price'],
            'total_biaya' => $total_biaya,
            'keterangan' => $data['keterangan'],
        ]);

        $this->savedItemsAset[$index]['total_biaya'] = $total_biaya;

        $this->editingIndex = null;
        LogHelper::success('Laporan garansi proyek berhasil diperbarui.');
        $this->dispatch('show-toast', ['message' => 'Laporan garansi proyek berhasil diperbarui.']);
    }

    // Fungsi untuk membatalkan mode edit
    public function cancelEdit()
    {
        if ($this->editingIndex !== null) {
            // Kembalikan data asli
            $this->savedItemsAset[$this->editingIndex] = $this->originalData[$this->editingIndex];

            // Hapus index yang sedang diedit
            $this->editingIndex = null;

        }
    }


    public function saveToLaporanProyek()
    {
        // Validasi setiap item di $this->itemsAset
        foreach ($this->itemsAset as $item) {
            $validator = Validator::make($item, [
                'tanggal' => 'required|date',
                'nama_biaya_tambahan' => 'required|string|max:255',
                'qty' => 'required|numeric',
                'satuan' => 'nullable|string|max:50',
                'unit_price' => 'required|numeric|min:0',
                'total_biaya' => 'nullable',
                'keterangan' => 'nullable|string|max:500',
            ],[
                'tanggal.required' => 'Tanggal wajib diisi.',
                'tanggal.date' => 'Format tanggal tidak valid.',
                'nama_biaya_tambahan.required' => 'Nama biaya tambahan wajib diisi.',
                'nama_biaya_tambahan.string' => 'Nama biaya tambahan harus berupa teks.',
                'nama_biaya_tambahan.max' => 'Nama biaya tambahan maksimal 255 karakter.',
                'qty.required' => 'Jumlah (Qty) wajib diisi.',
                'qty.numeric' => 'Jumlah (Qty) harus berupa angka.',
                'satuan.string' => 'Satuan harus berupa teks.',
                'satuan.max' => 'Satuan maksimal 50 karakter.',
                'unit_price.required' => 'Unit price wajib diisi.',
                'unit_price.numeric' => 'Unit price harus berupa angka.',
                'unit_price.min' => 'Unit price tidak boleh negatif.',
                'keterangan.string' => 'Keterangan harus berupa teks.',
                'keterangan.max' => 'Keterangan maksimal 500 karakter.',
            ]);

            if ($validator->fails()) {
                $this->dispatch('show-toast', [
                    'message' => 'Validasi gagal: <br>' . implode('<br>', $validator->errors()->all()),
                    'type' => 'error'
                ]);
                return;
            }
        }
        // Hapus data yang telah ditandai untuk dihapus
        if (!empty($this->deletedItemsAset)) {
            LaporanGaransiProyek::whereIn('id', $this->deletedItemsAset)->delete();
            $this->deletedItemsAset = []; // Kosongkan daftar ID yang dihapus
        }

        $user = Auth::user();
        // Simpan data baru
        foreach ($this->itemsAset as $item) {
            $total_biaya = $item['qty'] * $item['unit_price'];

            $laporan = LaporanGaransiProyek::create([
                'garansi_projek_id' => $this->garansi_proyek->id,
                'pembuat_laporan' => $user->name,
                'tanggal' => $item['tanggal'],
                'nama_biaya_tambahan' => $item['nama_biaya_tambahan'],
                'qty' => $item['qty'],
                'satuan' => $item['satuan'],
                'unit_price' => $item['unit_price'],
                'total_biaya' => $total_biaya,
                'keterangan' => $item['keterangan'],
            ]);

            // Tambahkan data baru ke tampilan
            $this->savedItemsAset[] = $laporan->toArray();
        }

        // Simpan anggaran ke dalam tabel `GaransiProjek`
        // GaransiProjek::where('id', $this->garansi_proyek->id)->update([
        //     'anggaran' => $this->anggaran,
        // ]);
        // Kosongkan form setelah disimpan
        $this->itemsAset = [];

        LogHelper::success('Laporan garansi proyek berhasil diperbarui.');
        $this->dispatch('show-toast', ['message' => 'Laporan garansi proyek berhasil diperbarui.']);
    }

    public function render()
    {
        // Hitung total produksi dari projek details
        $produksiTotal = array_sum(array_column($this->garansiProjekDetails, 'sub_total'));

        // Hitung total biaya tambahan dari laporan garansi proyek
        $totalHargaBiayaTambahan = array_sum(array_column($this->savedItemsAset, 'total_biaya'));

        // Hitung total bahan rusak
        // $totalHargaBahanRusak = array_sum(array_column($this->dataBahanRusak, 'total_biaya'));
        $totalHargaBahanRusak = array_sum(array_map(function ($bahanRusak) {
            return array_sum(array_column($bahanRusak['bahan_rusak_details'], 'sub_total'));
        }, $this->dataBahanRusak));

        // Hitung total keseluruhan
        $totalKeseluruhan = $produksiTotal + $totalHargaBiayaTambahan + $totalHargaBahanRusak;

        $sisaAnggaran = floatval($this->anggaran) - $totalKeseluruhan;
        return view('livewire.create-laporan-garansi-proyek', [
            'proyek' => $this->garansi_proyek,
            'garansiProjekDetails' => $this->garansiProjekDetails,
            'produksiTotal' => $produksiTotal,
            'totalHargaBiayaTambahan' => $totalHargaBiayaTambahan,
            'totalHargaBahanRusak' => $totalHargaBahanRusak,
            'totalKeseluruhan' => $totalKeseluruhan,
            'sisaAnggaran' => $sisaAnggaran,
            'dataBahanRusak' => $this->dataBahanRusak,
        ]);
    }
}
