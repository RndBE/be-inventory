<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Bahan;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\BahanKeluarDetails;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PembelianBahanExport implements FromArray, WithHeadings, WithStyles
{
    protected $startDate;
    protected $endDate;
    protected $companyName;

    protected $startDay;
    protected $endDay;
    protected $startMonth;
    protected $endMonth;
    protected $monthYear;

    public function __construct($startDate, $endDate, $companyName)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->companyName = $companyName;
    }

    public function array(): array
    {
        $data = [];

        $this->startDay = Carbon::parse($this->startDate)->format('j');
        $this->endDay = Carbon::parse($this->endDate)->format('j');
        $this->startMonth = Carbon::parse($this->startDate)->format('n');
        $this->endMonth = Carbon::parse($this->startDate)->format('n');
        $this->monthYear = Carbon::parse($this->endDate)->translatedFormat('F Y');

        $formattedPeriod = "Periode $this->startDay-$this->endDay $this->monthYear";

        $data[] = ["LAPORAN STOK BARANG " . $this->companyName];
        $data[] = ["Periode: " . $formattedPeriod];

        $tglBlnHeaders = ['No', 'Tgl Pengajuan', 'Divisi', 'Pengaju', 'Jenis/Project', 'Keterangan', 'Rincian Pengajuan', 'Qty', 'Harga Satuan', 'Sub Total', 'Tgl Keluar'];


        $data[] = $tglBlnHeaders;

        $data[] = array_merge([], [], [], [], [], [],[], [], [], [], [], [],);

        return $data;
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
        $sheet->mergeCells('G3:G4');
        $sheet->mergeCells('H3:H4');
        $sheet->mergeCells('I3:I4');
        $sheet->mergeCells('J3:J4');
        $sheet->mergeCells('K3:K4');
        $sheet->getStyle('A3:A4')->getFont()->setBold(true);
        $sheet->getStyle('B3:B4')->getFont()->setBold(true);
        $sheet->getStyle('C3:C4')->getFont()->setBold(true);
        $sheet->getStyle('D3:D4')->getFont()->setBold(true);
        $sheet->getStyle('E3:E4')->getFont()->setBold(true);
        $sheet->getStyle('F3:F4')->getFont()->setBold(true);
        $sheet->getStyle('G3:G4')->getFont()->setBold(true);
        $sheet->getStyle('A3:A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B3:B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C3:C4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D3:D4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E3:E4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F3:F4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


        $colIndex = 11;
        $dateHeaders = [];

        $lastColumnIndex = $colIndex;
        $lastColumnLetter = $this->getColumnLetter($lastColumnIndex);

        $sheet->mergeCells("A1:{$lastColumnLetter}1");
        $sheet->mergeCells("A2:{$lastColumnLetter}2");

        $columnLetter = $this->getColumnLetter($colIndex);
        $sheet->mergeCells("{$columnLetter}3:{$columnLetter}4");
        $colIndex++;

        $headerFillStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '89D8FC'],
            ],
        ];

        $sheet->getStyle('A1:F1')->applyFromArray($headerFillStyle);
        $sheet->getStyle('A2:F2')->applyFromArray($headerFillStyle);

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
