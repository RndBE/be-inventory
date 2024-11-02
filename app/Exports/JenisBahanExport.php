<?php

namespace App\Exports;

use App\Models\JenisBahan;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class JenisBahanExport implements FromCollection
{
    public function collection()
    {
        return JenisBahan::all()->map(function ($item, $index) {
            return [
                'nomor' => $index + 1,
                'nama' => $item->nama,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [ // Header row
                'font' => [
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4F81BD'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
            // Apply borders to all rows and columns
            'A1:B' . (count($this->collection()) + 1) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                foreach (range('A', 'B') as $column) { // Adjusted to E for correct range
                    $event->sheet->getDelegate()->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }
}
