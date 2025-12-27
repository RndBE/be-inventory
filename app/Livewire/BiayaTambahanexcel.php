<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\LaporanProyek;
use Livewire\WithPagination;

class BiayaTambahanExcel extends Component
{
    use WithFileUploads;

    // public $excelFile;
    public $excelFiles = [];
    public $previewItems = [];
    public $proyekId;
    public $savedItemsAset;
    public $items = [];


    public function loadSavedItems()
    {
        $this->savedItemsAset = DB::table('laporan_proyek')
            ->where('projek_id', $this->proyekId)
            ->get()
            ->map(function ($item) {
                return (array) $item;
            })
            ->toArray();
        // $this->savedItemsAset = DB::table('laporan_proyek')
        //     ->where('projek_id', $this->proyekId)
        //     ->orderBy('tanggal', 'asc')
        //     ->get()
        //     ->map(fn($i) => (array) $i)
        //     ->toArray();
    }

    public function mergeAndSortItems()
    {
        $merged = array_merge(
            $this->savedItemsAset ?? [],
            $this->previewItems ?? []
        );

        usort($merged, function ($a, $b) {
            return strtotime($a['tanggal']) <=> strtotime($b['tanggal']);
        });

        $this->items = $merged;
    }


    public function mount($proyekId)
    {
        $this->proyekId = $proyekId;
        $this->loadSavedItems();
    }

    // =========================================
    // A. UPLOAD & BACA FILE (PREVIEW SAJA)
    // =========================================
    public function readExcel()
    {
        $this->validate([
            'excelFiles' => 'required|array|min:1',
            'excelFiles.*' => 'mimes:xlsx,xls,csv|max:10240',
        ]);


        foreach ($this->excelFiles as $file) {
            $rows = Excel::toArray([], $file)[0] ?? [];
            array_shift($rows); // buang header

            foreach ($rows as $row) {
                if (empty($row[0])) continue;

                $rawDate = $row[0];

                if (is_numeric($rawDate)) {
                    $tanggal = Carbon::instance(
                        ExcelDate::excelToDateTimeObject($rawDate)
                    )->format('Y-m-d');
                } else {
                    $tanggal = Carbon::createFromFormat('m/d/Y', trim($rawDate))
                        ->format('Y-m-d');
                }

                $qty = (float) ($row[3] ?? 0);
                $price = (float) ($row[5] ?? 0);

                $items[] = [
                    'tanggal' => $tanggal,
                    'nama_biaya_tambahan' => $row[1] ?? null,
                    'keterangan' => $row[2] ?? null,
                    'qty' => $qty,
                    'satuan' => $row[4] ?? null,
                    'unit_price' => $price,
                    'total_biaya' => $qty * $price,
                ];
            }
        }

        usort(
            $items,
            fn($a, $b) =>
            strtotime($a['tanggal']) <=> strtotime($b['tanggal'])
        );

        $this->previewItems = $items;
        $this->mergeAndSortItems();
    }

    // =========================================
    // B. SIMPAN ISI FILE YANG SUDAH DIBACA
    // =========================================
    public function saveExcelResult()
    {
        if (empty($this->previewItems)) {
            return;
        }

        DB::transaction(function () {
            foreach ($this->previewItems as $item) {
                DB::table('laporan_proyek')->insert([
                    'projek_id' => $this->proyekId,
                    'tanggal' => $item['tanggal'],
                    'nama_biaya_tambahan' => $item['nama_biaya_tambahan'],
                    'keterangan' => $item['keterangan'],
                    'qty' => $item['qty'],
                    'satuan' => $item['satuan'],
                    'unit_price' => $item['unit_price'],
                    'total_biaya' => $item['total_biaya'],
                    // 'created_at' => now(),
                    // 'updated_at' => now(),
                ]);
            }
        });

        $this->reset(['excelFiles', 'previewItems']);
        $this->loadSavedItems();
        $this->mergeAndSortItems();

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => 'Biaya tambahan berhasil disimpan'
        ]);

        $this->dispatch('reload-tab2');
    }


    public function updatedUploadedFiles()
    {
        // $this->processUploadedFiles();
        // $this->mergeAndSortItems();
        $this->readExcel();
    }

    public function render()
    {
        $this->savedItemsAset = DB::table('laporan_proyek')
            ->where('projek_id', $this->proyekId)
            ->orderBy('tanggal', 'asc')
            ->get()
            ->map(fn($i) => (array) $i)
            ->toArray();
        return view('livewire.biaya-tambahan-excel');
    }
}
