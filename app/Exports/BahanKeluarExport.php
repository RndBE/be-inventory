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

        // ── Kolom header tabel (10 kolom: A–J) ──────────────────────
        //  A    B                C               D              E           F                G            H            I              J
        $data[] = [
            'No',
            'Tanggal Pengajuan',
            'Jam Pengajuan',
            'Tanggal Keluar',
            'Jam Keluar',
            'Kode Transaksi',
            'Nama Bahan',
            'Kuantitas',
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

        $counter = 1;

        foreach ($transactions as $transaction) {

            // ── Parse tanggal & jam ───────────────────────────────────
            $tglPengajuan = $transaction->tgl_pengajuan
                ? Carbon::parse($transaction->tgl_pengajuan)->format('d/m/Y')
                : '-';
            $jamPengajuan = $transaction->tgl_pengajuan
                ? Carbon::parse($transaction->tgl_pengajuan)->format('H:i:s')
                : '-';

            $tglKeluar = $transaction->tgl_keluar
                ? Carbon::parse($transaction->tgl_keluar)->format('d/m/Y')
                : '-';
            $jamKeluar = $transaction->tgl_keluar
                ? Carbon::parse($transaction->tgl_keluar)->format('H:i:s')
                : '-';

            // ── Baris group header per transaksi ─────────────────────
            $data[] = [
                '',
                $tglPengajuan,
                $jamPengajuan,
                $tglKeluar,
                $jamKeluar,
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
                    $tglPengajuan,
                    $jamPengajuan,
                    $tglKeluar,
                    $jamKeluar,
                    $transaction->kode_transaksi,
                    $namaBahan,
                    $detail->qty ?? 0,    // H: Kuantitas
                    $subTotal,             // I: Jumlah Harga
                    $transaction->keterangan ?? '-',
                ];
            }

            // ── Baris "Total Item" (di bawah kolom Kuantitas / H) ────
            $data[] = ['', '', '', '', '', '', 'Total Item', $transactionQty, '', ''];

            // ── Baris "Subtotal" (di bawah kolom Jumlah Harga / I) ───
            $data[] = ['', '', '', '', '', '', 'Subtotal', '', $transactionTotal, ''];
        }

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = 'J'; // 10 kolom A–J

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

        // ── Format kolom Tanggal & Jam sebagai teks (cegah auto-konversi Excel) ─
        foreach (['B', 'C', 'D', 'E'] as $col) {
            $sheet->getStyle("{$col}4:{$col}{$highestRow}")
                ->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        }

        // ── Format angka ─────────────────────────────────────────────
        // H: Kuantitas → right-align
        $sheet->getStyle("H4:H{$highestRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // I: Jumlah Harga → format Rupiah 2 desimal + right-align
        $sheet->getStyle("I4:I{$highestRow}")
            ->getNumberFormat()->setFormatCode('"Rp "#,##0.00');
        $sheet->getStyle("I4:I{$highestRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // ── Highlight baris ringkasan ────────────────────────────────
        for ($row = 4; $row <= $highestRow; $row++) {
            $cellG = $sheet->getCell("G{$row}")->getValue();

            // Group header transaksi (kolom G diawali '—')
            if (is_string($cellG) && str_starts_with($cellG, '—')) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('E8F4FD');
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFont()->setBold(true)->setItalic(true);
            }

            // Baris "Total Item" — kuning muda
            if ($cellG === 'Total Item') {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF9C4');
            }

            // Baris "Subtotal" — kuning
            if ($cellG === 'Subtotal') {
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
