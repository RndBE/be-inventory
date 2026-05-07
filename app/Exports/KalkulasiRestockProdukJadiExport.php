<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KalkulasiRestockProdukJadiExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $kalkulasiResult;
    protected $selectedProjects;

    public function __construct(array $kalkulasiResult, $selectedProjects)
    {
        $this->kalkulasiResult = $kalkulasiResult;
        $this->selectedProjects = $selectedProjects;
    }

    public function headings(): array
    {
        $headers = [
            'Kode Bahan',
            'Nama Bahan',
            'Unit',
            'Stok Sekarang',
            'Harga Terakhir'
        ];

        // Dynamic columns for each selected product number
        foreach ($this->selectedProjects as $project) {
            $headers[] = 'Kebutuhan (' . (optional($project->dataBahan)->nama_bahan ?? $project->id) . ')';
        }

        $headers = array_merge($headers, [
            'Total Dibutuhkan',
            'Kekurangan',
            'Estimasi Biaya',
            'Status'
        ]);

        return $headers;
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->kalkulasiResult as $row) {
            $item = [
                $row['kode_bahan'],
                $row['nama_bahan'],
                $row['unit'],
                $row['stok_sekarang'],
                $row['harga_terakhir'],
            ];

            // Fill dynamic columns
            foreach ($this->selectedProjects as $project) {
                $item[] = $row['breakdown'][$project->bahan_id] ?? 0;
            }

            // Append remaining columns
            $item = array_merge($item, [
                $row['total_butuh'],
                $row['kekurangan'] > 0 ? '-' . $row['kekurangan'] : '0',
                $row['total_kekurangan_biaya'] > 0 ? '-' . $row['total_kekurangan_biaya'] : '0',
                $row['status']
            ]);

            $data[] = $item;
        }

        // Add summary row at the bottom
        $summaryRow = array_fill(0, count($this->headings()), '');
        $summaryRow[0] = 'TOTAL ESTIMASI BIAYA RESTOCK';
        
        $estimasiColIndex = count($this->headings()) - 2; // Second to last column
        $summaryRow[$estimasiColIndex] = collect($this->kalkulasiResult)->sum('total_kekurangan_biaya');

        $data[] = $summaryRow;

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->kalkulasiResult) + 2; // +1 for header, +1 for summary row

        $styles = [
            // Header row styling
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4B5563'], // Gray-600
                ],
            ],
            // Summary row styling
            $lastRow => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF3F4F6'], // Gray-100
                ],
            ],
        ];

        return $styles;
    }
}
