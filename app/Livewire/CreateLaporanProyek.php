<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Bahan;
use App\Models\Projek;
use Livewire\Component;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;
use App\Models\LaporanProyek;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Support\Facades\Validator;

class CreateLaporanProyek extends Component
{
    public $proyek;
    public $projekDetails = [];
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
    public $canEditBiayaTambahan, $canDeleteBiayaTambahan, $canTambahBaris, $canSimpanLaporan;



    public function mount(Request $request)
    {
        $this->canEditBiayaTambahan = Gate::allows('edit-biaya-tambahan');
        $this->canDeleteBiayaTambahan = Gate::allows('hapus-biaya-tambahan');
        $this->canTambahBaris = Gate::allows('tambah-baris');
        $this->canSimpanLaporan = Gate::allows('simpan-laporan');

        $proyek_id = $request->query('proyek_id');
        $this->proyek = Projek::with([
            'projekDetails',
            'bahanKeluar',
            'dataKontrak',
            'dataBahanRusak.bahanRusakDetails.dataBahan',
            'dataBahanRusak.bahanRusakDetails.dataProduk'
        ])->find($proyek_id);

        // Ambil data laporan proyek yang sudah tersimpan
        $this->savedItemsAset = LaporanProyek::where('projek_id', $proyek_id)->get()->toArray();

        // Pastikan projekDetails ada dan dalam bentuk array
        if ($this->proyek && $this->proyek->projekDetails) {
            $this->projekDetails = $this->proyek->projekDetails->toArray();
            $this->dataBahanRusak = $this->proyek->dataBahanRusak->toArray();
        }

        $this->dataBahan = Bahan::pluck('nama_bahan', 'id')->toArray();
        $this->dataProduk = BahanSetengahjadiDetails::pluck('nama_bahan', 'id')->toArray();

        foreach ($this->savedItemsAset as &$item) {
            $item['tanggal'] = Carbon::parse($item['tanggal'])->format('Y-m-d');
        }

    }

    public function addRow()
    {
        $this->itemsAset[] = [
            'tanggal' => '',
            'nama_biaya_tambahan' => '',
            'qty' => '',
            'satuan' => '',
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
            'total_biaya' => 'required|numeric|min:0',
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
            'total_biaya.required' => 'Total biaya wajib diisi.',
            'total_biaya.numeric' => 'Total biaya harus berupa angka.',
            'total_biaya.min' => 'Total biaya tidak boleh negatif.',
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

        LaporanProyek::where('id', $data['id'])->update([
            'tanggal' => $data['tanggal'],
            'nama_biaya_tambahan' => $data['nama_biaya_tambahan'],
            'qty' => $data['qty'],
            'satuan' => $data['satuan'],
            'total_biaya' => $data['total_biaya'],
            'keterangan' => $data['keterangan'],
        ]);

        $this->editingIndex = null;
        LogHelper::success('Laporan proyek berhasil diperbarui.');
        $this->dispatch('show-toast', ['message' => 'Laporan proyek berhasil diperbarui.']);
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
                'total_biaya' => 'required|numeric|min:0',
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
                'total_biaya.required' => 'Total biaya wajib diisi.',
                'total_biaya.numeric' => 'Total biaya harus berupa angka.',
                'total_biaya.min' => 'Total biaya tidak boleh negatif.',
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
            LaporanProyek::whereIn('id', $this->deletedItemsAset)->delete();
            $this->deletedItemsAset = []; // Kosongkan daftar ID yang dihapus
        }

        $user = Auth::user();
        // Simpan data baru
        foreach ($this->itemsAset as $item) {
            $laporan = LaporanProyek::create([
                'projek_id' => $this->proyek->id,
                'pembuat_laporan' => $user->name,
                'tanggal' => $item['tanggal'],
                'nama_biaya_tambahan' => $item['nama_biaya_tambahan'],
                'qty' => $item['qty'],
                'satuan' => $item['satuan'],
                'total_biaya' => $item['total_biaya'],
                'keterangan' => $item['keterangan'],
            ]);

            // Tambahkan data baru ke tampilan
            $this->savedItemsAset[] = $laporan->toArray();
        }

        // Kosongkan form setelah disimpan
        $this->itemsAset = [];

        LogHelper::success('Laporan proyek berhasil diperbarui.');
        $this->dispatch('show-toast', ['message' => 'Laporan proyek berhasil diperbarui.']);
    }

    public function render()
    {
        // Hitung total produksi dari projek details
        $produksiTotal = array_sum(array_column($this->projekDetails, 'sub_total'));

        // Hitung total biaya tambahan dari laporan proyek
        $totalHargaBiayaTambahan = array_sum(array_column($this->savedItemsAset, 'total_biaya'));

        // Hitung total bahan rusak
        $totalHargaBahanRusak = array_sum(array_column($this->dataBahanRusak, 'total_biaya'));

        // Hitung total keseluruhan
        $totalKeseluruhan = $produksiTotal + $totalHargaBiayaTambahan + $totalHargaBahanRusak;
        return view('livewire.create-laporan-proyek', [
            'proyek' => $this->proyek,
            'projekDetails' => $this->projekDetails,
            'produksiTotal' => $produksiTotal,
            'totalHargaBiayaTambahan' => $totalHargaBiayaTambahan,
            'totalHargaBahanRusak' => $totalHargaBahanRusak,
            'totalKeseluruhan' => $totalKeseluruhan,
            'dataBahanRusak' => $this->dataBahanRusak,
        ]);
    }
}
