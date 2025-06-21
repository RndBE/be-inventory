<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Bahan;
use App\Models\PurchaseDetail;
use App\Models\BahanSetengahjadi;
use App\Models\BahanKeluarDetails;
use App\Models\BahanSetengahjadiDetails;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\FromArray;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;

class BahanSetengahjadisExport implements FromArray, WithHeadings, WithStyles, WithEvents, WithCharts
{
    protected $startDate;
    protected $endDate;
    protected $companyName;

    protected $startDay;
    protected $endDay;
    protected $startMonth;
    protected $endMonth;
    protected $monthYear;
    protected $kodeTransaksiMap = [];
    protected $summaryStartRow;
    protected $summaryEndRow;
    protected $produkSummary = [];
    protected $stokAkhirColIndex = [];




    public function __construct($startDate, $endDate, $companyName)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->companyName = $companyName;
    }

    public function array(): array
    {
        $data = [];

        $this->startDay   = Carbon::parse($this->startDate)->format('j');
        $this->endDay     = Carbon::parse($this->endDate)->format('j');
        $this->startMonth = Carbon::parse($this->startDate)->format('n');
        $this->endMonth   = Carbon::parse($this->startDate)->format('n');
        $this->monthYear  = Carbon::parse($this->endDate)->translatedFormat('F Y');

        $formattedPeriod = "Periode $this->startDay-$this->endDay $this->monthYear";

        $data[] = ["LAPORAN STOK PRODUK " . $this->companyName];
        $data[] = ["Periode: " . $formattedPeriod];

        // Header baris ke-3
        $tglBlnHeaders = ['No', 'Kode Transaksi/Produksi', 'Nama Produk', 'Seri Produk', 'Satuan', 'Stok Awal'];

        for ($day = $this->startDay; $day <= $this->endDay; $day++) {
            $tglBlnHeaders[] = "$day/$this->startMonth";
        }
        $tglBlnHeaders[] = 'Stok Akhir';
        $tglBlnHeaders[] = 'Harga Terakhir';

        $data[] = $tglBlnHeaders;

        // Header baris ke-4
        $dateNames = [];
        for ($day = $this->startDay; $day <= $this->endDay; $day++) {
            $dateNames[] = "Stok Masuk";
            $dateNames[] = "Harga Beli";
            $dateNames[] = "Stok Keluar";
        }
        $data[] = array_merge([], [], [], [], [], [], $dateNames, []);

        $produk = BahanSetengahjadi::with(['bahanSetengahjadiDetails'])->get();

        $allDetails = [];

        foreach ($produk as $item) {
            foreach ($item->bahanSetengahjadiDetails as $detail) {
                $allDetails[] = [
                    'item'   => $item,
                    'detail' => $detail
                ];
            }
        }

        // Urutkan berdasarkan nama_bahan (abjad A-Z)
        usort($allDetails, function ($a, $b) {
            return strcmp($a['detail']->nama_bahan, $b['detail']->nama_bahan);
        });

        $transactionMap = [];
        $rowIndex = 5;
        $no = 1;

        foreach ($allDetails as $entry) {
            $item = $entry['item'];
            $detail = $entry['detail'];

            $kode = $item->kode_transaksi;
            $serial = $detail->serial_number;

            // Siapkan merge info per kode_transaksi
            if (!isset($transactionMap[$kode])) {
                $transactionMap[$kode] = ['start' => $rowIndex];
            }

            $stokAwal = $this->getPreviousDayStokAkhir($item->id, $this->startDate);

            $row = [
                null, // no: akan diisi nanti setelah merge
                $kode,
                $detail->nama_bahan,
                $serial,
                "Pcs",
                $stokAwal
            ];

            for ($day = $this->startDay; $day <= $this->endDay; $day++) {
                $tanggal = Carbon::create(null, $this->startMonth, $day)->toDateString();

                $stokMasuk = BahanSetengahjadiDetails::whereHas('bahanSetengahjadi', function ($query) use ($tanggal) {
                    $query->whereDate('tgl_masuk', $tanggal);
                })
                ->where('id', $detail->id)
                ->sum('qty');

                $hargaBeli = BahanSetengahjadiDetails::whereHas('bahanSetengahjadi', function ($query) use ($tanggal) {
                    $query->whereDate('tgl_masuk', $tanggal);
                })
                ->where('id', $detail->id)
                ->value('unit_price') ?? '';
                $hargaBeli = $hargaBeli ? number_format($hargaBeli, 2, ',', '.') : '';

                $stokKeluar = BahanKeluarDetails::whereHas('bahanKeluar', function ($query) use ($tanggal) {
                    $query->whereDate('tgl_keluar', $tanggal)->where('status', 'Disetujui');
                })
                ->where('produk_id', $item->id)
                ->where('serial_number', $serial)
                ->sum('qty');

                $row[] = $stokMasuk;
                $row[] = $hargaBeli;
                $row[] = $stokKeluar;
            }

            $row[] = $this->getSisaStokAkhir($item->id, $this->endDate, $serial);

            $hargaFormatted = $detail->unit_price ? number_format($detail->unit_price, 2, ',', '.') : '';
            $row[] = $hargaFormatted;

            $data[] = $row;
            $transactionMap[$kode]['end'] = $rowIndex;
            $rowIndex++;
        }

        // Isi nomor urut dan data merge
        $no = 1;
        $this->kodeTransaksiMap = [];
        foreach ($transactionMap as $kode => $map) {
            for ($i = $map['start']; $i <= $map['end']; $i++) {
                $data[$i - 1][0] = $no; // Kolom "No" (index 0)
            }

            $this->kodeTransaksiMap[] = [
                'kode'  => $kode,
                'start' => $map['start'],
                'end'   => $map['end']
            ];
            $no++;
        }

        $stokAkhirValue = $this->getSisaStokAkhir($item->id, $this->endDate, $serial);
$row[] = $stokAkhirValue;

// Simpan index kolom stok akhir
$this->stokAkhirColIndex = count($row) - 3;

        // dd($row);


        return $data;
    }



    private function getSisaStokAkhir($bahanId, $endDate, $serial = null)
    {
        $query = BahanSetengahjadiDetails::whereHas('bahanSetengahjadi', function ($q) use ($endDate) {
            $q->whereDate('tgl_masuk', '<=', $endDate);
        })
        ->where('bahan_setengahjadi_id', $bahanId);

        if ($serial) {
            $query->where('serial_number', $serial);
        }

        return max(0, $query->sum('sisa'));
    }



    private function getPreviousDayStokAkhir($bahanId, $startDate)
    {
        // Ambil total pembelian s.d. sebelum startDate
        $totalMasuk = BahanSetengahjadiDetails::whereHas('bahanSetengahjadi', function ($query) use ($startDate) {
            $query->whereDate('tgl_masuk', '<', $startDate);
        })
        ->where('bahan_setengahjadi_id', $bahanId)
        ->orderBy('bahan_setengahjadi_id')
        ->get(['qty', 'sisa']);

        $totalMasukQty = $totalMasuk->sum('qty');

        // Simulasi FIFO: stok awal adalah stok masuk sebelum startDate, dikurangi pemakaian setelah startDate
        $stokAwal = $totalMasukQty;

        return $stokAwal > 0 ? $stokAwal : 0;
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

        $sheet->mergeCells('A3:A4');
        $sheet->mergeCells('B3:B4');
        $sheet->mergeCells('C3:C4');
        $sheet->mergeCells('D3:D4');
        $sheet->mergeCells('E3:E4');
        $sheet->mergeCells('F3:F4');
        $sheet->getStyle('A3:A4')->getFont()->setBold(true);
        $sheet->getStyle('B3:B4')->getFont()->setBold(true);
        $sheet->getStyle('C3:C4')->getFont()->setBold(true);
        $sheet->getStyle('D3:D4')->getFont()->setBold(true);
        $sheet->getStyle('E3:E4')->getFont()->setBold(true);
        $sheet->getStyle('F3:F4')->getFont()->setBold(true);
        $sheet->getStyle('A3:A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B3:B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C3:C4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D3:D4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E3:E4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F3:F4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


        $colIndex = 6;
        $dateHeaders = [];

        for ($day = $this->startDay; $day <= $this->endDay; $day++) {
            $columnIndex = 7 + ($day - $this->startDay) * 3 + 1; // Calculate column index for each "Harga Beli"
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getStyle($columnLetter)->getAlignment()->setHorizontal('right');
            $columnLetter = $this->getColumnLetter($colIndex);
            $endColumnLetter = $this->getColumnLetter($colIndex + 2);

            $sheet->mergeCells("{$columnLetter}3:{$endColumnLetter}3");
            $sheet->setCellValue("{$columnLetter}3", "$day/" . $this->startMonth);

            $sheet->getStyle("{$columnLetter}3:{$endColumnLetter}3")->getFont()->setBold(true);
            $sheet->getStyle("{$columnLetter}3:{$endColumnLetter}3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("{$columnLetter}4:{$endColumnLetter}4")->getFont()->setBold(true);
            $sheet->getStyle("{$columnLetter}4:{$endColumnLetter}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $colIndex += 3;

            $dateHeaders[] = $columnLetter;
        }

        $lastColumnIndex = $colIndex;
        $lastColumnLetter = $this->getColumnLetter($lastColumnIndex);

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
        $colIndex++;


        $headerFillStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '89D8FC'],
            ],
        ];

        $sheet->getStyle('A1:F1')->applyFromArray($headerFillStyle);
        $sheet->getStyle('A2:F2')->applyFromArray($headerFillStyle);

        $colIndex = 6;
        $stokHeaders = ["Stok Masuk", "Harga Beli", "Stok Keluar"];

        for ($i = 0; $i < count($dateHeaders); $i++) {
            $columnLetter = $dateHeaders[$i];

            foreach ($stokHeaders as $stokHeader) {
                $sheet->setCellValue("{$columnLetter}4", $stokHeader);
                $columnLetter = $this->getColumnLetter(++$colIndex);
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

        $totalColumns = $colIndex + 2;
        for ($i = 0; $i <= $totalColumns; $i++) {
            $columnLetter = $this->getColumnLetter($i);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        // Buat isi kolom Seri Produk (kolom D) rata kiri
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle("A5:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B5:B{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D5:D{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Merge Kode Transaksi dan Nomor jika memiliki baris lebih dari 1
        foreach ($sheet->toArray() as $row) {
    if (!isset($row[2]) || !isset($row[$this->stokAkhirColIndex])) continue;

    $namaProduk = $row[2];
    $stokAkhirRaw = $row[$this->stokAkhirColIndex];

    $stokAkhir = is_numeric(str_replace(['.', ','], '', $stokAkhirRaw))
        ? (int) str_replace(['.', ','], '', $stokAkhirRaw)
        : (int) $stokAkhirRaw;

    if (!isset($this->produkSummary[$namaProduk])) {
        $this->produkSummary[$namaProduk] = 0;
    }

    $this->produkSummary[$namaProduk] += $stokAkhir;
}


        // Hitung total stok akhir per produk
        $this->produkSummary = [];
         foreach ($sheet->toArray() as $row) {
    if (!isset($row[2]) || !isset($row[$this->stokAkhirColIndex])) continue;

    $namaProduk = $row[2];
    $stokAkhirRaw = $row[$this->stokAkhirColIndex];

    // Hapus pemisah ribuan dan koma
    $cleaned = str_replace(['.', ','], '', $stokAkhirRaw);

    $stokAkhir = is_numeric($cleaned) ? (int)$cleaned : 0;

    if (!isset($this->produkSummary[$namaProduk])) {
        $this->produkSummary[$namaProduk] = 0;
    }

    $this->produkSummary[$namaProduk] += $stokAkhir;
}



        // Tambahkan ringkasan ke bawah sheet
        $this->summaryStartRow = $sheet->getHighestRow() + 2;
        $summaryColumn1 = 'B';
        $summaryColumn2 = 'C';

        $sheet->setCellValue("{$summaryColumn1}{$this->summaryStartRow}", 'Nama Produk');
        $sheet->setCellValue("{$summaryColumn2}{$this->summaryStartRow}", 'Total Stok Akhir');
        $sheet->getStyle("{$summaryColumn1}{$this->summaryStartRow}:{$summaryColumn2}{$this->summaryStartRow}")
            ->getFont()->setBold(true);

        $row = $this->summaryStartRow + 1;
        foreach ($this->produkSummary as $nama => $jumlah) {
            $sheet->setCellValue("{$summaryColumn1}{$row}", $nama);
            $sheet->setCellValue("{$summaryColumn2}{$row}", $jumlah);
            $row++;
        }
        $this->summaryEndRow = $row - 1;


    }
    public function registerEvents(): array
{
    return [
        AfterSheet::class => function(AfterSheet $event) {
            if (empty($this->produkSummary)) {
                return;
            }

            $summaryColumn1 = 'B'; // Nama Produk
            $summaryColumn2 = 'C'; // Total Stok Akhir

            $startRow = $this->summaryStartRow ?? 30;
            $endRow = $this->summaryEndRow ?? ($startRow + 10);
            $countProduk = count($this->produkSummary);

            $labels = [
                new DataSeriesValues(
                    'String',
                    "'{$event->sheet->getTitle()}'!{$summaryColumn1}" . ($startRow + 1) . ":{$summaryColumn1}{$endRow}",
                    null,
                    $countProduk
                ),
            ];
            $values = [
                new DataSeriesValues(
                    'Number',
                    "'{$event->sheet->getTitle()}'!{$summaryColumn2}" . ($startRow + 1) . ":{$summaryColumn2}{$endRow}",
                    null,
                    $countProduk
                ),
            ];

            $series = new DataSeries(
                DataSeries::TYPE_PIECHART,
                null,
                range(0, count($values) - 1),
                $labels,  // <-- penting untuk tampilkan label
                $labels,
                $values,
                null,
                null,
                true   // <-- ini akan menampilkan label nama produk di pie
            );

            $plotArea = new PlotArea(null, [$series]);
            $chart = new Chart(
                'StokAkhirPieChart',
                new Title('Distribusi Stok Akhir per Produk'),
                new Legend(Legend::POSITION_RIGHT, null, false), // tampilkan legend di kanan
                $plotArea
            );

            $chart->setTopLeftPosition("E{$startRow}");
            $chart->setBottomRightPosition("K" . ($startRow + 15));

            $event->sheet->getDelegate()->addChart($chart);
        },
    ];
}

    public function charts()
    {
        return [];
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
