<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\BahanKeluar;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\FromArray;
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
        $startFmt = Carbon::parse($this->startDate)->translatedFormat('d F Y');
        $endFmt   = Carbon::parse($this->endDate)->translatedFormat('d F Y');

        $data[] = ['Rekap Bahan Keluar (Disetujui)'];
        $data[] = ["Periode: {$startFmt} s/d {$endFmt}"];

        // ── Kolom header tabel (8 kolom: A–H) ───────────────────────
        $data[] = [
            'No',               // A
            'Tanggal Pengajuan',// B
            'Tanggal Keluar',   // C
            'Kode Transaksi',   // D
            'Nama Bahan',       // E
            'Kuantitas',        // F
            'Jumlah Harga',     // G
            'Project / Tujuan', // H
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

        $counter = 1;

        foreach ($transactions as $transaction) {

            // ── Baris group header per transaksi ─────────────────────
            $data[] = [
                '',
                $transaction->tgl_pengajuan ?? '-',
                $transaction->tgl_keluar    ?? '-',
                $transaction->kode_transaksi,
                '— ' . ($transaction->keterangan ?? '-') . ' —',
                '', '', '',
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

                // ── Baris detail item ─────────────────────────────────
                $data[] = [
                    $counter++,
                    $transaction->tgl_pengajuan ?? '-',
                    $transaction->tgl_keluar    ?? '-',
                    $transaction->kode_transaksi,
                    $namaBahan,
                    $detail->qty ?? 0,           // F: Kuantitas
                    $subTotal,                    // G: Jumlah Harga
                    $transaction->keterangan ?? '-',
                ];
            }

            // ── Baris "Total Item" → total qty di bawah kolom F ──────
            $data[] = ['', '', '', '', 'Total Item', $transactionQty, '', ''];

            // ── Baris "Subtotal" → total harga di bawah kolom G ──────
            $data[] = ['', '', '', '', 'Subtotal', '', $transactionTotal, ''];
        }

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = 'H'; // 8 kolom A–H

        // ── Judul & Periode ──────────────────────────────────────────
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("A1:{$lastCol}2")->applyFromArray([
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => '89D8FC'],
            ],
        ]);

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

        $sheet->getStyle("A3:{$highestColumn}{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => '000000'],
                ],
            ],
        ]);

        // ── Format angka ─────────────────────────────────────────────
        // F: Kuantitas → right-align
        $sheet->getStyle("F4:F{$highestRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // G: Jumlah Harga → format Rupiah + right-align
        $sheet->getStyle("G4:G{$highestRow}")
            ->getNumberFormat()->setFormatCode('"Rp "#,##0');
        $sheet->getStyle("G4:G{$highestRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // ── Highlight baris ringkasan ────────────────────────────────
        for ($row = 4; $row <= $highestRow; $row++) {
            $cellE = $sheet->getCell("E{$row}")->getValue();

            // Group header transaksi (diawali '—')
            if (is_string($cellE) && str_starts_with($cellE, '—')) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('E8F4FD');
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFont()->setBold(true)->setItalic(true);
            }

            // "Total Item" per transaksi — kuning sangat muda
            if ($cellE === 'Total Item') {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF9C4');
            }

            // "Subtotal" per transaksi — kuning
            if ($cellE === 'Subtotal') {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFACD');
            }

        }

        // ── Auto-size semua kolom ────────────────────────────────────
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
