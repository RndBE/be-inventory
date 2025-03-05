<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Produksi;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProduksiExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected $produksi_id;

    public function __construct($produksi_id)
    {
        $this->produksi_id = $produksi_id;
    }

    public function array(): array
    {
        $data = [];
        $totalQty = 0;
        $totalSubTotal = 0;
        $produksi = Produksi::with('produksiDetails.dataBahan', 'produksiDetails.dataBahan.dataUnit')->findOrFail($this->produksi_id);

        $formattedStartDate = Carbon::parse($produksi->mulai_produkis)->format('d F Y');
        $formattedEndDate = Carbon::parse($produksi->selesai_produksi)->format('d F Y');

        $data[] = ['PT ARTA TEKNOLOGI COMUNINDO', '', '', '', '', '', ''];
        $data[] = ['HPP PRODUKSI', '', '', '', '', '', ''];
        $data[] = [''];

        $data[] = ['Kode Produksi', '', ': '.$produksi->kode_produksi];
        $data[] = ['Nama Produk', '', ': '.$produksi->dataBahan->nama_bahan];
        $data[] = ['Serial Number', '', ': '.$produksi->serial_number];
        $data[] = ['Masa Pekerjaan', '', ': '.$formattedStartDate . ' - ' . $formattedEndDate];
        $data[] = [''];

        $data[] = ['No', 'Nama Barang/Bahan', 'Qty', 'Satuan', 'Harga Satuan', 'Total'];

        foreach ($produksi->produksiDetails as $index => $detail) {
            // Assuming $detail->details contains the JSON string
            $detailsArray = json_decode($detail->details, true); // Decode the JSON into an associative array
            $detailsFormatted = [];

            // Format each detail as 'qty x unit_price'
            foreach ($detailsArray as $item) {
                $detailsFormatted[] = $item['qty'] . 'x' . $item['unit_price'];
            }

            // Join the formatted details into a single string
            $formattedDetailsString = implode(', ', $detailsFormatted);

            $data[] = [
                $index + 1,
                $detail->dataBahan->nama_bahan,
                $detail->qty,
                $detail->dataBahan->dataUnit->nama ?? null,
                $formattedDetailsString, // Use the formatted details string
                $detail->sub_total,
            ];

            $totalQty += $detail->qty;
            $totalSubTotal += $detail->sub_total;
        }


        $data[] = [
            'Total HPP Produksi',
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
        // Gaya untuk baris judul dan perusahaan
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        $sheet->getStyle('A1:A2')->getFont()->setSize(12);
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');

        $sheet->mergeCells('A4:B4');
        $sheet->mergeCells('A5:B5');
        $sheet->mergeCells('A6:B6');
        $sheet->mergeCells('A7:B7');

        $sheet->mergeCells('C4:F4');
        $sheet->mergeCells('C5:F5');
        $sheet->mergeCells('C6:F6');
        $sheet->mergeCells('C7:F7');

        // Gaya untuk header tabel
        $sheet->getStyle('A9:F9')->getFont()->setBold(true);
        $sheet->getStyle('A9:F9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

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

        // $sheet->getStyle('A7:F' . $lastRow)->applyFromArray($borderStyle);
        // Apply the border style to the entire data range including the merged total row
        $sheet->getStyle('A9:F' . $lastRow)->applyFromArray($borderStyle);

        // Merge the last row for the "Total HPP Project"
        $sheet->mergeCells('A' . $lastRow . ':B' . $lastRow); // Merge from column A to b in the last row
        $sheet->getStyle('A' . $lastRow . ':F' . $lastRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $lastRow . ':B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Align text to the right
    }



    public function title(): string
    {
        return 'HPP PRODUKSI';
    }
}
