<?php

namespace App\Exports;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Bahan;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchasesExport implements FromArray, WithHeadings, WithStyles
{
    protected $startDate;
    protected $endDate;
    protected $companyName;

    /** @var Carbon[] Setiap tanggal di rentang export, boleh lintas bulan. */
    protected $dates = [];

    public function __construct($startDate, $endDate, $companyName)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->companyName = $companyName;

        $period = CarbonPeriod::create(
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->startOfDay()
        );
        foreach ($period as $date) {
            $this->dates[] = $date->copy();
        }
    }

    public function array(): array
    {
        $data = [];

        $data[] = ["LAPORAN STOK BARANG " . $this->companyName];
        $data[] = ["Periode: " . $this->formattedPeriod()];

        $tglBlnHeaders = ['No', 'Kode Barang', 'Nama Barang', 'Seri Barang', 'Satuan', 'Stok Awal'];
        foreach ($this->dates as $date) {
            $tglBlnHeaders[] = $date->format('j/n');
        }
        $tglBlnHeaders[] = 'Stok Akhir';
        $tglBlnHeaders[] = 'Harga Terakhir';
        $data[] = $tglBlnHeaders;

        $dateNames = [];
        foreach ($this->dates as $date) {
            $dateNames[] = "Stok Masuk";
            $dateNames[] = "Harga Beli";
            $dateNames[] = "Stok Keluar";
        }
        $data[] = $dateNames;

        $bahan = Bahan::with(['dataUnit', 'jenisBahan'])
            ->whereHas('jenisBahan', function ($query) {
                $query->where('nama', '!=', 'Produksi')->where('nama', '!=', 'Projek RnD');
            })->orderBy('nama_bahan')
            ->get();

        // Semua angka diambil sekaligus untuk seluruh bahan, lalu dicocokkan di
        // PHP. Dulu setiap bahan menembak 3 query per hari ditambah 3 query lagi,
        // sehingga export sebulan untuk ribuan bahan menjadi puluhan ribu query.
        [$masuk, $hargaBeli] = $this->getMasukHarian();
        $keluar = $this->getKeluarHarian();
        $stok = $this->getStokAwalDanSisa();
        $hargaTerakhir = $this->getHargaTerakhir();

        foreach ($bahan as $index => $item) {
            $row = [
                $index + 1,
                $item->kode_bahan,
                $item->nama_bahan,
                $item->seri_bahan,
                $item->dataUnit->nama ?? null,
                $stok[$item->id]['stok_awal'] ?? 0,
            ];

            foreach ($this->dates as $date) {
                $key = $date->toDateString();
                $harga = $hargaBeli[$item->id][$key] ?? null;

                $row[] = $masuk[$item->id][$key] ?? 0;
                $row[] = $harga ? number_format($harga, 2, ',', '.') : '';
                $row[] = $keluar[$item->id][$key] ?? 0;
            }

            $row[] = $stok[$item->id]['sisa'] ?? 0;

            $harga = $hargaTerakhir[$item->id] ?? null;
            $row[] = $harga ? number_format($harga, 2, ',', '.') : '';

            $data[] = $row;
        }

        return $data;
    }

    private function formattedPeriod(): string
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        if ($start->format('Y-m') === $end->format('Y-m')) {
            return $start->format('j') . '-' . $end->format('j') . ' ' . $end->translatedFormat('F Y');
        }

        return $start->translatedFormat('j F Y') . ' - ' . $end->translatedFormat('j F Y');
    }

    /**
     * Qty masuk dan harga beli per bahan per tanggal di rentang export.
     * Kalau satu bahan dibeli lebih dari sekali di hari yang sama, harga beli
     * yang tampil adalah pembelian terakhir hari itu.
     */
    private function getMasukHarian(): array
    {
        $rows = DB::table('purchase_details')
            ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
            ->whereBetween('purchases.tgl_masuk', [$this->startDate, $this->endDate])
            ->orderBy('purchases.tgl_masuk')
            ->orderBy('purchase_details.id')
            ->get([
                'purchase_details.bahan_id',
                DB::raw('DATE(purchases.tgl_masuk) as tgl'),
                'purchase_details.qty',
                'purchase_details.unit_price',
            ]);

        $masuk = [];
        $harga = [];
        foreach ($rows as $row) {
            $masuk[$row->bahan_id][$row->tgl] = ($masuk[$row->bahan_id][$row->tgl] ?? 0) + $row->qty;
            $harga[$row->bahan_id][$row->tgl] = $row->unit_price;
        }

        return [$masuk, $harga];
    }

    /** Qty keluar yang sudah disetujui, per bahan per tanggal. */
    private function getKeluarHarian(): array
    {
        $rows = DB::table('bahan_keluar_details')
            ->join('bahan_keluars', 'bahan_keluar_details.bahan_keluar_id', '=', 'bahan_keluars.id')
            ->where('bahan_keluars.status', 'Disetujui')
            ->whereBetween('bahan_keluars.tgl_keluar', [$this->startDate, $this->endDate])
            ->whereNotNull('bahan_keluar_details.bahan_id')
            ->groupBy('bahan_keluar_details.bahan_id', DB::raw('DATE(bahan_keluars.tgl_keluar)'))
            ->get([
                'bahan_keluar_details.bahan_id',
                DB::raw('DATE(bahan_keluars.tgl_keluar) as tgl'),
                DB::raw('SUM(bahan_keluar_details.qty) as total'),
            ]);

        $keluar = [];
        foreach ($rows as $row) {
            $keluar[$row->bahan_id][$row->tgl] = $row->total + 0;
        }

        return $keluar;
    }

    /**
     * Stok awal = total qty masuk sebelum tanggal awal.
     * Sisa (kolom Stok Akhir) = total sisa dari pembelian s.d. tanggal akhir.
     */
    private function getStokAwalDanSisa(): array
    {
        $rows = DB::table('purchase_details')
            ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
            ->where('purchases.tgl_masuk', '<=', $this->endDate)
            ->groupBy('purchase_details.bahan_id')
            ->selectRaw(
                'purchase_details.bahan_id,
                SUM(CASE WHEN purchases.tgl_masuk < ? THEN purchase_details.qty ELSE 0 END) as stok_awal,
                SUM(purchase_details.sisa) as sisa',
                [$this->startDate]
            )
            ->get();

        $stok = [];
        foreach ($rows as $row) {
            $stok[$row->bahan_id] = [
                'stok_awal' => max(0, $row->stok_awal + 0),
                'sisa' => max(0, $row->sisa + 0),
            ];
        }

        return $stok;
    }

    /** Harga beli dari pembelian terakhir s.d. tanggal akhir, per bahan. */
    private function getHargaTerakhir(): array
    {
        $tglTerakhir = DB::table('purchase_details')
            ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
            ->where('purchases.tgl_masuk', '<=', $this->endDate)
            ->groupBy('purchase_details.bahan_id')
            ->select('purchase_details.bahan_id', DB::raw('MAX(purchases.tgl_masuk) as tgl_terakhir'));

        $rows = DB::table('purchase_details')
            ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
            ->joinSub($tglTerakhir, 'terakhir', function ($join) {
                $join->on('terakhir.bahan_id', '=', 'purchase_details.bahan_id')
                    ->on('terakhir.tgl_terakhir', '=', 'purchases.tgl_masuk');
            })
            ->orderBy('purchase_details.id')
            ->get(['purchase_details.bahan_id', 'purchase_details.unit_price']);

        $harga = [];
        foreach ($rows as $row) {
            $harga[$row->bahan_id] = $row->unit_price;
        }

        return $harga;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        $sheet->getStyle('A1:A2')->getFont()->setSize(12);
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
            $sheet->mergeCells("{$col}3:{$col}4");
            $sheet->getStyle("{$col}3:{$col}4")->getFont()->setBold(true);
            $sheet->getStyle("{$col}3:{$col}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $colIndex = 6;
        $dateHeaders = [];

        foreach ($this->dates as $i => $date) {
            $hargaBeliColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(8 + $i * 3);
            $sheet->getStyle($hargaBeliColumn)->getAlignment()->setHorizontal('right');

            $columnLetter = $this->getColumnLetter($colIndex);
            $endColumnLetter = $this->getColumnLetter($colIndex + 2);

            $sheet->mergeCells("{$columnLetter}3:{$endColumnLetter}3");
            $sheet->setCellValue("{$columnLetter}3", $date->format('j/n'));

            $sheet->getStyle("{$columnLetter}3:{$endColumnLetter}3")->getFont()->setBold(true);
            $sheet->getStyle("{$columnLetter}3:{$endColumnLetter}3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("{$columnLetter}4:{$endColumnLetter}4")->getFont()->setBold(true);
            $sheet->getStyle("{$columnLetter}4:{$endColumnLetter}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $colIndex += 3;

            $dateHeaders[] = $columnLetter;
        }

        $lastColumnLetter = $this->getColumnLetter($colIndex);

        $sheet->mergeCells("A1:{$lastColumnLetter}1");
        $sheet->mergeCells("A2:{$lastColumnLetter}2");

        $columnLetter = $this->getColumnLetter($colIndex);
        $sheet->mergeCells("{$columnLetter}3:{$columnLetter}4");
        $sheet->setCellValue("{$columnLetter}3", 'Stok Akhir');
        $sheet->getStyle("{$columnLetter}3:{$columnLetter}4")->getFont()->setBold(true);
        $sheet->getStyle("{$columnLetter}3:{$columnLetter}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $colIndex++;

        $hargaColumnLetter = $this->getColumnLetter($colIndex);
        $sheet->mergeCells("{$hargaColumnLetter}3:{$hargaColumnLetter}4");
        $sheet->setCellValue("{$hargaColumnLetter}3", 'Harga Terakhir');
        $sheet->getStyle("{$hargaColumnLetter}3:{$hargaColumnLetter}4")->getFont()->setBold(true);
        $sheet->getStyle("{$hargaColumnLetter}3:{$hargaColumnLetter}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$hargaColumnLetter}5:{$hargaColumnLetter}{$sheet->getHighestRow()}")
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $headerFillStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '89D8FC'],
            ],
        ];

        $sheet->getStyle('A1:F1')->applyFromArray($headerFillStyle);
        $sheet->getStyle('A2:F2')->applyFromArray($headerFillStyle);

        $stokHeaders = ["Stok Masuk", "Harga Beli", "Stok Keluar"];
        $headerIndex = 6;
        foreach ($dateHeaders as $ignored) {
            foreach ($stokHeaders as $stokHeader) {
                $sheet->setCellValue($this->getColumnLetter($headerIndex) . '4', $stokHeader);
                $headerIndex++;
            }
        }

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];

        $sheet->getStyle("A3:{$highestColumn}{$highestRow}")->applyFromArray($borderStyle);

        // Lebar kolom tetap. setAutoSize() memaksa PhpSpreadsheet mengukur
        // setiap sel, dan itu lambat sekali untuk ribuan baris x puluhan kolom.
        $fixedWidths = ['A' => 6, 'B' => 16, 'C' => 40, 'D' => 20, 'E' => 10, 'F' => 11];
        foreach ($fixedWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        // Kolom harian (Stok Masuk/Harga Beli/Stok Keluar) dan Stok Akhir.
        for ($i = 6; $i < $colIndex; $i++) {
            $sheet->getColumnDimension($this->getColumnLetter($i))->setWidth(12);
        }
        $sheet->getColumnDimension($hargaColumnLetter)->setWidth(16);
    }

    private function getColumnLetter($index)
    {
        $letters = '';
        while ($index >= 0) {
            $letters = chr($index % 26 + 65) . $letters;
            $index = floor($index / 26) - 1;
        }
        return $letters;
    }
}
