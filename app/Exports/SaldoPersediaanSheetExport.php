<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SaldoPersediaanSheetExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithTitle, WithCustomStartCell
{
    public function __construct(
        protected array $rows,
        protected string $title,
        protected string $periodLabel
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Periode',
            'Kode Bahan',
            'Nama Bahan',
            'Satuan',
            'Saldo Awal Qty',
            'Masuk Qty',
            'Keluar Qty',
            'Saldo Akhir Qty',
            'Saldo Awal Nilai',
            'Nilai Masuk',
            'Nilai Keluar',
            'Saldo Akhir Nilai',
        ];
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            4 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0F766E'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->setCellValue('A1', 'Rekap Data Saldo Bahan');
                $sheet->setCellValue('A2', $this->periodLabel);
                $sheet->mergeCells('A1:L1');
                $sheet->mergeCells('A2:L2');

                $sheet->freezePane('A5');
                $sheet->getStyle("A1:L{$highestRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A2')->getFont()->setSize(11);
                $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A5:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B5:B{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D5:D{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("E5:H{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                $sheet->getStyle("I5:L{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');
            },
        ];
    }

    public function title(): string
    {
        return $this->title;
    }
}
