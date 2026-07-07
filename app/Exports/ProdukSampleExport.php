<?php

namespace App\Exports;

use App\Models\ProdukSample;
use App\Services\ProductFlowService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProdukSampleExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected ProductFlowService $flowService;

    public function __construct(protected int $produkSampleId)
    {
        $this->flowService = new ProductFlowService();
    }

    public function array(): array
    {
        $produkSample = ProdukSample::with([
            'produkSampleDetails.dataBahan.dataUnit',
            'produkSampleDetails.dataProduk.bahanSetengahjadi',
            'produkSampleDetails.dataProdukJadi.ProdukJadis',
        ])->findOrFail($this->produkSampleId);

        $mulai = $produkSample->mulai_produk_sample
            ? Carbon::parse($produkSample->mulai_produk_sample)->format('d F Y')
            : '-';
        $selesai = $produkSample->selesai_produk_sample
            ? Carbon::parse($produkSample->selesai_produk_sample)->format('d F Y')
            : '-';

        $data = [
            ['PT ARTA TEKNOLOGI COMUNINDO'],
            ['HPP PRODUK SAMPLE'],
            [''],
            ['Kode Produk Sample', '', ': ' . $produkSample->kode_produk_sample],
            ['Nama Produk Sample', '', ': ' . $produkSample->nama_produk_sample],
            ['Pengaju', '', ': ' . ($produkSample->pengaju ?? '-')],
            ['Status', '', ': ' . ($produkSample->status ?? '-')],
            ['Masa Pekerjaan', '', ': ' . $mulai . ' - ' . $selesai],
            [''],
            [
                'No',
                'Nama Barang/Bahan',
                'Qty',
                'Satuan',
                'Harga Satuan',
                'Total',
                'Keterangan',
                'Jenis Item',
                'Kode Sumber',
                'Asal Flow',
                'Serial Number Flow',
                'Tujuan Flow',
                'Kode Tujuan',
                'Status Flow',
            ],
        ];

        $totalQty = 0;
        $totalSubTotal = 0;

        foreach ($produkSample->produkSampleDetails as $index => $detail) {
            $detail->setRelation('produkSample', $produkSample);

            $detailsArray = json_decode($detail->details, true) ?? [];
            $detailsFormatted = [];
            foreach ($detailsArray as $item) {
                $detailsFormatted[] = ($item['qty'] ?? 0) . 'x' . ($item['unit_price'] ?? 0);
            }

            $namaBarang = $detail->dataProdukJadi
                ? ($detail->dataProdukJadi->nama_produk ?? '-') . ' (' . ($detail->serial_number ?? '-') . ')'
                : ($detail->dataProduk
                    ? ($detail->dataProduk->nama_bahan ?? '-') . ' (' . ($detail->serial_number ?? '-') . ')'
                    : ($detail->dataBahan->nama_bahan ?? '-'));

            $data[] = [
                $index + 1,
                $namaBarang,
                $detail->qty,
                $detail->dataBahan?->dataUnit?->nama ?? 'Pcs',
                implode(', ', $detailsFormatted),
                $detail->sub_total,
                $detail->keterangan_penanggungjawab ?? '-',
                ...$this->flowService->values($this->flowService->forProdukSampleDetail($detail)),
            ];

            $totalQty += $detail->qty;
            $totalSubTotal += $detail->sub_total;
        }

        $data[] = [
            'Total HPP Produk Sample',
            '',
            $totalQty,
            '',
            '',
            $totalSubTotal,
            '',
            '', '', '', '', '', '', '',
        ];

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:N1');
        $sheet->mergeCells('A2:N2');
        $sheet->mergeCells('A4:B4');
        $sheet->mergeCells('A5:B5');
        $sheet->mergeCells('A6:B6');
        $sheet->mergeCells('A7:B7');
        $sheet->mergeCells('A8:B8');
        $sheet->mergeCells('C4:N4');
        $sheet->mergeCells('C5:N5');
        $sheet->mergeCells('C6:N6');
        $sheet->mergeCells('C7:N7');
        $sheet->mergeCells('C8:N8');

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A10:N10')->getFont()->setBold(true);
        $sheet->getStyle('A10:N10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('F10:F' . $lastRow)->getNumberFormat()->setFormatCode('[$-421] #,##0');

        $sheet->getStyle('A10:N' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        $sheet->mergeCells('A' . $lastRow . ':B' . $lastRow);
        $sheet->getStyle('A' . $lastRow . ':N' . $lastRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $lastRow . ':B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function title(): string
    {
        return 'HPP PRODUK SAMPLE';
    }
}
