<?php

namespace App\Exports;

use App\Models\Ruangan;
use App\Models\BarangAset;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet tempat mengisi data. Baris contoh diambil dari data nyata supaya
 * langsung terlihat formatnya, dan diberi warna abu-abu agar jelas harus dihapus.
 */
class TemplateAsetDataSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function headings(): array
    {
        return [
            'nomor_aset',
            'nama_aset',
            'serial_number',
            'merek',
            'link_gambar',
            'tanggal_perolehan',
            'jumlah_aset',
            'harga_perolehan',
            'kondisi_aset',
            'keterangan',
            'nama_penanggungjawab',
            'nama_pic',
            'ruangan',
        ];
    }

    public function array(): array
    {
        $contohBarang = BarangAset::value('nama_barang') ?? 'Nama barang yang sudah terdaftar';
        $contohRuangan = Ruangan::value('nama_ruangan') ?? 'Nama ruangan yang sudah terdaftar';

        return [
            [
                'INV/ATC/CONTOH-001/DIR/2025',
                $contohBarang,
                'SN-CONTOH-001',
                'Lenovo ThinkPad E14',
                '',
                date('Y-m-d'),
                1,
                1500000,
                'Baik',
                'baris contoh, silakan dihapus',
                'Nama penanggung jawab sesuai data user',
                '',
                $contohRuangan,
            ],
        ];
    }

    public function title(): string
    {
        return 'Data Aset';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        $sheet->getStyle('A1:M1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E5E7EB');

        // Kolom wajib diberi warna berbeda supaya langsung terlihat.
        // K1 = nama_penanggungjawab, bergeser dari J setelah 'merek' disisipkan.
        foreach (['A1', 'B1', 'K1'] as $sel) {
            $sheet->getStyle($sel)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('FEE2E2');
        }

        $sheet->getStyle('A2:M2')->getFont()->getColor()->setRGB('9CA3AF');

        foreach (range('A', 'M') as $kolom) {
            $sheet->getColumnDimension($kolom)->setAutoSize(true);
        }

        $sheet->freezePane('A2');

        return [];
    }
}
