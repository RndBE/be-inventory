<?php

namespace App\Exports;

use App\Models\Bahan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BahansExport implements FromCollection, WithEvents, WithHeadings, WithStyles
{
    public function collection()
    {
        return Bahan::with('jenisBahan', 'dataUnit', 'suppliers')
            ->orderBy('nama_bahan') // ✅ Urutkan berdasarkan abjad nama_bahan
            ->get()
            ->map(function ($item) {
                return [
                    'kode_bahan' => $item->kode_bahan,
                    'nama_bahan' => $item->nama_bahan,
                    'jenis_bahan_id' => $item->jenisBahan->nama ?? 'N/A',
                    'unit_id' => $item->dataUnit->nama ?? 'N/A',
                    'penempatan' => $item->penempatan,
                    'status' => $item->status,
                    'supplier' => $item->suppliers->pluck('nama')->implode(', '),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Kode Bahan',
            'Nama Bahan',
            'Jenis Bahan',
            'Satuan Unit',
            'Penempatan',
            'Status',
            'Supplier',
        ];
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4F81BD'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                foreach (range('A', 'G') as $column) {
                    $event->sheet->getDelegate()->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }
}
