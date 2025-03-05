<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Projek;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjekExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected $projek_id;

    public function __construct($projek_id)
    {
        $this->projek_id = $projek_id;
    }

    public function array(): array
    {
        $data = [];
        $totalQty = 0;
        $totalSubTotal = 0;
        $projek = Projek::with('projekDetails.dataBahan', 'projekDetails.dataBahan.dataUnit', 'projekDetails.dataProduk',)->findOrFail($this->projek_id);

        $formattedStartDate = Carbon::parse($projek->mulai_projek)->format('d F Y');
        $formattedEndDate = Carbon::parse($projek->selesai_projek)->format('d F Y');

        $data[] = ['PT ARTA TEKNOLOGI COMUNINDO', '', '', '', '', '', ''];
        $data[] = ['HPP PROYEK', '', '', '', '', '', ''];
        $data[] = [''];

        $data[] = ['Kode Proyek', '', ': '.$projek->kode_projek];
        $data[] = ['Nama Proyek', '', ': '.$projek->dataKontrak->nama_kontrak];
        $data[] = ['Masa Pekerjaan', '', ': '.$formattedStartDate . ' - ' . $formattedEndDate];
        $data[] = [''];

        $data[] = ['No', 'Nama Barang/Bahan', 'Qty', 'Satuan', 'Harga Satuan', 'Total'];

        foreach ($projek->projekDetails as $index => $detail) {
            $detailsArray = json_decode($detail->details, true);
            $detailsFormatted = [];

            foreach ($detailsArray as $item) {
                $detailsFormatted[] = $item['qty'] . 'x' . $item['unit_price'];
            }

            $formattedDetailsString = implode(', ', $detailsFormatted);

            $data[] = [
                $index + 1,
                ($detail->dataProduk ? $detail->dataProduk->nama_bahan . ' (' . ($detail->serial_number ?? '-') . ')' : $detail->dataBahan->nama_bahan ?? null),
                $detail->qty,
                $detail->dataBahan->dataUnit->nama ?? 'Pcs',
                $formattedDetailsString,
                $detail->sub_total,
            ];


            $totalQty += $detail->qty;
            $totalSubTotal += $detail->sub_total;
        }


        $data[] = [
            'Total HPP Proyek',
            '',
            $totalQty,
            '',
            '',
            $totalSubTotal,
        ];

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

        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');

        $sheet->mergeCells('A4:B4');
        $sheet->mergeCells('A5:B5');
        $sheet->mergeCells('A6:B6');

        $sheet->mergeCells('C4:F4');
        $sheet->mergeCells('C5:F5');
        $sheet->mergeCells('C6:F6');

        $sheet->getStyle('A8:F8')->getFont()->setBold(true);
        $sheet->getStyle('A8:F8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $lastRow = $sheet->getHighestRow();

        for ($row = 8; $row <= $lastRow; $row++) {
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('[$-421] #,##0');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('[$-421] #,##0');
        }

        $sheet->getStyle('F' . $lastRow)->getNumberFormat()->setFormatCode('[$-421] #,##0');

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        $sheet->getStyle('A8:F' . $lastRow)->applyFromArray($borderStyle);

        $sheet->mergeCells('A' . $lastRow . ':B' . $lastRow);
        $sheet->getStyle('A' . $lastRow . ':F' . $lastRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $lastRow . ':B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }



    public function title(): string
    {
        return 'HPP PROJECT';
    }
}


