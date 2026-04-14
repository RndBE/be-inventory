<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\BahanKeluar;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BahanKeluarExport implements FromArray, WithHeadings, WithStyles
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    public function array(): array
    {
        $data = [];

        // ── Header laporan ──────────────────────────────────────────
        $startFmt  = Carbon::parse($this->startDate)->translatedFormat('d F Y');
        $endFmt    = Carbon::parse($this->endDate)->translatedFormat('d F Y');
        $period    = "Periode: {$startFmt} s/d {$endFmt}";

        $data[] = ['Rekap Bahan Keluar (Disetujui)'];
        $data[] = [$period];

        // ── Kolom header tabel ───────────────────────────────────────
        $data[] = [
            'No',
            'Tanggal Pengajuan',
            'Tanggal Keluar',
            'Kode Transaksi',
            'Nama Bahan',
            'Kuantitas',
            'Total Item',
            'Jumlah Harga',
            'Project / Tujuan',
        ];

        // ── Query data ───────────────────────────────────────────────
        $transactions = BahanKeluar::with([
                'bahanKeluarDetails.dataBahan',
                'bahanKeluarDetails.dataProduk',
                'bahanKeluarDetails.dataProdukJadi',
                'dataUser',
            ])
            ->where('status', 'Disetujui')
            ->whereBetween('tgl_pengajuan', [$this->startDate, $this->endDate])
            ->orderBy('tgl_pengajuan', 'desc')
            ->get();

        $counter       = 1;
        $grandTotal    = 0;
        $grandTotalQty = 0;

        foreach ($transactions as $transaction) {
            // Baris header per transaksi (group header)
            $data[] = [
                '',
                $transaction->tgl_pengajuan ?? '-',
                $transaction->tgl_keluar    ?? '-',
                $transaction->kode_transaksi,
                '— ' . ($transaction->keterangan ?? '-') . ' —',
                '',
                '',
                '',
                '',
            ];

            $transactionTotal = 0;
            $transactionQty   = 0;

            foreach ($transaction->bahanKeluarDetails as $detail) {
                // Ambil nama bahan dari relasi yang tersedia
                if ($detail->bahan_id && $detail->dataBahan) {
                    $namaBahan = $detail->dataBahan->nama_bahan ?? '-';
                } elseif ($detail->produk_id && $detail->dataProduk) {
                    $namaBahan = $detail->dataProduk->nama_bahan ?? '-';
                } elseif ($detail->produk_jadis_id && $detail->dataProdukJadi) {
                    $namaBahan = $detail->dataProdukJadi->nama_produk ?? '-';
                } else {
                    $namaBahan = '-';
                }

                $subTotal          = $detail->sub_total ?? 0;
                $transactionTotal += $subTotal;
                $transactionQty   += $detail->qty ?? 0;

                $data[] = [
                    $counter++,
                    $transaction->tgl_pengajuan ?? '-',
                    $transaction->tgl_keluar    ?? '-',
                    $transaction->kode_transaksi,
                    $namaBahan,
                    $detail->qty ?? 0,
                    '',
                    $subTotal,
                    $transaction->keterangan ?? '-',
                ];
            }

            // Baris subtotal per transaksi
            $data[] = [
                '',
                '',
                '',
                '',
                '',
                'Subtotal',
                $transactionQty,
                $transactionTotal,
                '',
            ];

            $grandTotal    += $transactionTotal;
            $grandTotalQty += $transactionQty;
        }

        // ── Grand total ──────────────────────────────────────────────
        $data[] = [
            '',
            '',
            '',
            '',
            '',
            'GRAND TOTAL',
            $grandTotalQty,
            $grandTotal,
            '',
        ];

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        // ── Judul & Periode ──────────────────────────────────────────
        $lastCol = 'I';

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headerFill = [
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => '89D8FC'],
            ],
        ];
        $sheet->getStyle("A1:{$lastCol}2")->applyFromArray($headerFill);

        // ── Header kolom (baris 3) ───────────────────────────────────
        foreach (range('A', $lastCol) as $col) {
            $sheet->getStyle("{$col}3")->getFont()->setBold(true);
            $sheet->getStyle("{$col}3")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("{$col}3")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('d2d2db');
        }

        // ── Border semua sel data ────────────────────────────────────
        $highestRow    = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle("A3:{$highestColumn}{$highestRow}")->applyFromArray($borderStyle);

        // ── Format kolom Total Item (G) & Jumlah Harga (H) ──────────
        $sheet->getStyle("H4:H{$highestRow}")
            ->getNumberFormat()
            ->setFormatCode('"Rp "#,##0');

        $sheet->getStyle("G4:G{$highestRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("H4:H{$highestRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // ── Highlight baris Subtotal & Grand Total ───────────────────
        for ($row = 4; $row <= $highestRow; $row++) {
            $cellF = $sheet->getCell("F{$row}")->getValue();

            if ($cellF === 'Subtotal') {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFACD');
            }

            if ($cellF === 'GRAND TOTAL') {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFF00');
                $sheet->getStyle("G{$row}:H{$row}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            // Highlight baris group header transaksi (kolom E mulai dengan —)
            $cellE = $sheet->getCell("E{$row}")->getValue();
            if (is_string($cellE) && str_starts_with($cellE, '—')) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('E8F4FD');
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFont()->setBold(true)->setItalic(true);
            }
        }

        // ── Auto-size semua kolom ────────────────────────────────────
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
