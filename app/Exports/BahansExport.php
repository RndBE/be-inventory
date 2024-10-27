<?php

namespace App\Exports;

use App\Models\Bahan;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BahansExport implements FromCollection, WithHeadings, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Bahan::with('jenisBahan') // Eager load the jenisBahan relationship
            ->select("kode_bahan", "nama_bahan", "jenis_bahan_id", "penempatan")
            ->get()
            ->map(function ($item) {
                return [
                    'kode_bahan' => $item->kode_bahan,
                    'nama_bahan' => $item->nama_bahan,
                    'jenis_bahan' => $item->jenisBahan->nama ?? 'N/A', // Get the name of the related jenis_bahan
                    'penempatan' => $item->penempatan,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Kode Bahan',
            'Nama Bahan',
            'Jenis Bahan',
            'Penempatan',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Set the first row (headings) to bold and set the background color
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => Color::COLOR_WHITE],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4F81BD'], // Blue background
                ],
            ],
        ];
    }
}
