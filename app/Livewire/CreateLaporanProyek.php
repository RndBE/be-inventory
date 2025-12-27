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
use Illuminate\Support\Facades\DB;
use App\Models\ProdukJadiDetails;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateLaporanProyek extends Component
{
    public $proyek;
    public $projekDetails = [];
    public $dataBahanRusak = [];
    public $dataBahan = [];
    public $dataProduk = [];
    public $dataProdukJadi = [];
    public $produksiStatus;

    public $itemsAset = [];
    public $proyek_id;
    public $items = [];

    public $editingId = null;
    public $editForm = [];


    public $savedItemsAset = [];
    public $deletedItemsAset = [];
    public $editingIndex = null;
    public $originalData = [];
    public $canEditBiayaTambahan, $canDeleteBiayaTambahan, $canTambahBaris, $canSimpanLaporan;

    public function mergeAndSortItems()
    {
        $merged = [];

        // Data dari DB
        foreach ($this->savedItemsAset as $row) {
            $row['uuid'] = $row['uuid'] ?? null;
            $row['source'] = 'db';
            $row['tanggal'] = $row['tanggal'] ?? null;
            $merged[] = $row;
        }

        // Data baru
        foreach ($this->itemsAset as $row) {
            $row['source'] = 'new';
            $row['id'] = $row['id'] ?? null;
            $row['tanggal'] = $row['tanggal'] ?? null;
            $merged[] = $row;
        }

        // buang baris kosong
        $merged = array_filter(
            $merged,
            fn($r) =>
            !empty($r['tanggal']) || !empty($r['nama_biaya_tambahan'])
        );

        // sembunyikan data yang dihapus
        if (!empty($this->deletedItemsAset)) {
            $merged = array_filter(
                $merged,
                fn($r) =>
                empty($r['id']) || !in_array($r['id'], $this->deletedItemsAset)
            );
        }

        // sort tanggal ASC
        usort(
            $merged,
            fn($a, $b) =>
            strtotime($a['tanggal'] ?? '1970-01-01')
                <=>
                strtotime($b['tanggal'] ?? '1970-01-01')
        );

        $this->items = $merged;
    }
    // public function mergeAndSortItems()
    // {
    //     // $merged = array_merge(
    //     //     $this->savedItemsAset ?? [],
    //     //     $this->itemsAset ?? []
    //     // );

    //     // gabungkan TANPA reindex
    //     $merged = ($this->savedItemsAset ?? []) + ($this->itemsAset ?? []);

    //     $merged = array_filter($merged, function ($row) {
    //         return !empty($row['tanggal']) || !empty($row['nama_biaya_tambahan']);
    //     });

    //     // if (!empty($this->deletedItemsAset)) {
    //     //     $merged = array_filter($merged, function ($row) {
    //     //         return empty($row['id']) || !in_array($row['id'], $this->deletedItemsAset);
    //     //     });
    //     // }
    //     if (!empty($this->deletedItemsAset)) {
    //         $merged = array_filter($merged, function ($row) {
    //             $id = $row['id'] ?? null;
    //             return !$id || !in_array($id, $this->deletedItemsAset);
    //         });
    //     }

    //     usort($merged, function ($a, $b) {
    //         $ta = is_array($a) ? ($a['tanggal'] ?? null) : ($a->tanggal ?? null);
    //         $tb = is_array($b) ? ($b['tanggal'] ?? null) : ($b->tanggal ?? null);

    //         return strtotime($ta ?? '1970-01-01') <=> strtotime($tb ?? '1970-01-01');
    //     });

    //     $this->items = $merged;
    // }


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
            'dataBahanRusak.bahanRusakDetails.dataProduk',
            'dataBahanRusak.bahanRusakDetails.dataProdukJadi'
        ])->find($proyek_id);


        // Ambil data laporan proyek yang sudah tersimpan
        $this->savedItemsAset = LaporanProyek::where('projek_id', $proyek_id)
            ->orderBy('tanggal', 'asc')
            ->get()
            ->keyBy('id')
            ->toArray();

        // Pastikan projekDetails ada dan dalam bentuk array
        if ($this->proyek && $this->proyek->projekDetails) {
            $this->projekDetails = $this->proyek->projekDetails->toArray();
            $this->dataBahanRusak = $this->proyek->dataBahanRusak->toArray();
        }

        $this->dataBahan = Bahan::pluck('nama_bahan', 'id')->toArray();
        $this->dataProduk = BahanSetengahjadiDetails::pluck('nama_bahan', 'id')->toArray();
        $this->dataProdukJadi = ProdukJadiDetails::pluck('nama_produk', 'id')->toArray();

        foreach ($this->savedItemsAset as &$item) {
            $item['tanggal'] = Carbon::parse($item['tanggal'])->format('Y-m-d');
        }

        $this->mergeAndSortItems();
    }

    public function addRow()
    {
        $uuid = (string) Str::uuid();

        $this->itemsAset[$uuid] = [
        // $this->savedItemsAset[$uuid] = [
            'uuid' => $uuid,
            'id' => null,
            'tanggal' => null,
            'nama_biaya_tambahan' => '',
            'qty' => '',
            'satuan' => '',
            'unit_price' => '',
            'total_biaya' => '',
            'keterangan' => '',
        ];

        $this->mergeAndSortItems();
    }

    // public function addRow()
    // {
    //     // $this->itemsAset[] = [
    //     //     // 'tanggal' => '',
    //     //     'tanggal' => null,
    //     //     'nama_biaya_tambahan' => '',
    //     //     'qty' => '',
    //     //     'satuan' => '',
    //     //     'unit_price' => '',
    //     //     'total_biaya' => '',
    //     //     'keterangan' => '',
    //     // ];

    //     $uuid = (string) Str::uuid();
    //     $this->itemsAset[$uuid] = [
    //         'uuid' => $uuid,
    //         // 'tanggal' => '',
    //         'tanggal' => null,
    //         'nama_biaya_tambahan' => '',
    //         'qty' => '',
    //         'satuan' => '',
    //         'unit_price' => '',
    //         'total_biaya' => '',
    //         'keterangan' => '',
    //     ];

    //     $this->mergeAndSortItems();
    // }

    public function saveRow($uuid)
    {
        $this->itemsAset[$uuid]['editing'] = false;
    }


    public function removeRow($uuid)
    {
        unset($this->itemsAset[$uuid]);

        $this->mergeAndSortItems();
    }
    // public function removeRow($uuid)
    // {
    //     unset($this->itemsAset[$uuid]);
    //     // $this->itemsAset = array_values($this->itemsAset);

    //     $this->mergeAndSortItems();
    // }

    public function deleteSavedRow($id)
    {
        $this->deletedItemsAset[] = $id;

        $this->savedItemsAset = collect($this->savedItemsAset)
            ->reject(fn($row) => $row['id'] == $id)
            ->all();

        $this->mergeAndSortItems();
    }

    // public function deleteSavedRow($id)
    // {
    //     // Tandai data untuk dihapus saat tombol Simpan ditekan
    //     $this->deletedItemsAset[] = $id;

    //     // Hapus secara langsung dari tampilan (tanpa reload)
    //     // $this->savedItemsAset = array_filter($this->savedItemsAset, function ($item) use ($id) {
    //     //     return $item['id'] != $id;
    //     // });

    //     // $this->savedItemsAset = collect($this->savedItemsAset)
    //     //     ->reject(fn($row) => isset($row['id']) && $row['id'] == $id)
    //     //     // ->values()
    //     //     ->all();
    //     $this->savedItemsAset = collect($this->savedItemsAset)
    //         ->reject(function ($row) use ($id) {
    //             return isset($row['id']) && $row['id'] == $id;
    //         })
    //         ->all();
    //     // if ($this->editingId === $id) {
    //     //     $this->editingId = null;
    //     //     $this->editForm = [];
    //     // }

    //     $this->mergeAndSortItems();
    // }

    public function editRow($key)
    {
        $this->editingIndex = $key;
    }

    // public function editRow($index)
    // {
    //     if (!isset($this->items[$index])) {
    //         return;
    //     }

    //     $this->originalData[$index] = $this->savedItemsAset[$index];
    //     $this->editingIndex = $index;
    // }

    // Fungsi untuk menyimpan perubahan
    public function updateRow($key)
    {
        // $data = $this->savedItemsAset[$id];

        if (isset($this->savedItemsAset[$key])) {
            $data = $this->savedItemsAset[$key];   // data lama dari DB
        } else {
            $data = $this->itemsAset[$key];       // data baru (uuid)
        }

        // Validasi data yang diupdate
        $validator = Validator::make($data, [
            'tanggal' => 'required|date',
            'nama_biaya_tambahan' => 'required|string|max:255',
            'qty' => 'required|numeric',
            'satuan' => 'nullable|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'total_biaya' => 'nullable',
            'keterangan' => 'nullable|string|max:500',
        ], [
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

        if (!empty($data['id'])) {
            LaporanProyek::where('id', $data['id'])->update([
                'tanggal' => $tanggalFormatted,
                'nama_biaya_tambahan' => $data['nama_biaya_tambahan'],
                'qty' => $data['qty'],
                'satuan' => $data['satuan'],
                'unit_price' => $data['unit_price'],
                'total_biaya' => $total_biaya,
                'keterangan' => $data['keterangan'],
            ]);
            $this->savedItemsAset[$key]['total_biaya'] = $total_biaya;
        } else {
            $this->itemsAset[$key]['total_biaya'] = $total_biaya;
        }

        $this->editingIndex = null;

        $this->mergeAndSortItems();
        LogHelper::success('Laporan proyek berhasil diperbarui.');
        $this->dispatch('show-toast', ['message' => 'Laporan proyek berhasil diperbarui.']);
    }

    // Fungsi untuk membatalkan mode edit
    public function cancelEdit()
    {
        // if ($this->editingIndex !== null) {
        //     // Kembalikan data asli
        //     $this->savedItemsAset[$this->editingIndex] = $this->originalData[$this->editingIndex];

        //     // Hapus index yang sedang diedit
        //     $this->editingIndex = null;
        // }

        $this->editingIndex = null;

        // if ($this->editingIndex !== null) {
        //     $id = $this->editingIndex;
        //     $this->savedItemsAset[$id] = $this->originalData[$id];
        //     $this->editingIndex = null;
        // }
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
            ], [
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
            LaporanProyek::whereIn('id', $this->deletedItemsAset)->delete();
            $this->deletedItemsAset = []; // Kosongkan daftar ID yang dihapus
        }

        $user = Auth::user();
        // Simpan data baru
        foreach ($this->itemsAset as $item) {
            $total_biaya = $item['qty'] * $item['unit_price'];

            $laporan = LaporanProyek::create([
                'projek_id' => $this->proyek->id,
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
            $this->savedItemsAset[$laporan->id] = $laporan->toArray();

            // $this->savedItemsAset[] = $laporan->toArray();
        }

        // Kosongkan form setelah disimpan
        $this->itemsAset = [];
        $this->mergeAndSortItems();

        LogHelper::success('Laporan proyek berhasil diperbarui.');
        $this->dispatch('show-toast', ['message' => 'Laporan proyek berhasil diperbarui.']);

        // $this->dispatch('reload-tab2');
    }

    public function render()
    {
        // Hitung total produksi dari projek details
        $produksiTotal = array_sum(array_column($this->projekDetails, 'sub_total'));
        // Hitung total biaya tambahan dari laporan proyek
        $totalHargaBiayaTambahan = array_sum(array_column($this->savedItemsAset, 'total_biaya'));
        // Hitung total bahan rusak
        $totalHargaBahanRusak = array_sum(array_map(function ($bahanRusak) {
            return array_sum(array_column($bahanRusak['bahan_rusak_details'], 'sub_total'));
        }, $this->dataBahanRusak));

        // Hitung total keseluruhan
        $totalKeseluruhan = $produksiTotal + $totalHargaBiayaTambahan + $totalHargaBahanRusak;
        // dd($totalHargaBahanRusak);
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
