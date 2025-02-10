<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Bahan;
use App\Models\Purchase;
use App\Models\PembelianBahan;
use App\Models\PurchaseDetail;
use App\Models\BahanKeluarDetails;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PembelianBahanExport implements FromArray, WithHeadings, WithStyles
{
    protected $startDate;
    protected $endDate;
    protected $companyName;

    protected $startDay;
    protected $endDay;
    protected $startMonth;
    protected $endMonth;
    protected $monthYear;

    public function __construct($startDate, $endDate, $companyName)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->companyName = $companyName;
    }

    public function array(): array
    {
        $data = [];

        $this->startDay = Carbon::parse($this->startDate)->format('j');
        $this->endDay = Carbon::parse($this->endDate)->format('j');
        $this->startMonth = Carbon::parse($this->startDate)->format('n');
        $this->endMonth = Carbon::parse($this->startDate)->format('n');
        $this->monthYear = Carbon::parse($this->endDate)->translatedFormat('F Y');

        $formattedPeriod = "Periode $this->startDay-$this->endDay $this->monthYear";

        $data[] = ["Rekap Pengajuan Pembelian " . $this->companyName];
        $data[] = ["Periode: " . $formattedPeriod];

        $headers = [
            'No', 'Tgl Pengajuan', 'Divisi', 'Pengaju', 'Jenis/Project', 'Keterangan',
            'Rincian Pengajuan', 'Qty', 'Harga Satuan', 'Total Pengajuan', 'Tgl ACC', 'Harga Satuan', 'Nominal yang dibayar',
        ];

        $data[] = $headers;

        $transactions = PembelianBahan::with(['pembelianBahanDetails', 'dataUser'])
            ->where('status', 'Disetujui')
            ->whereBetween('tgl_pengajuan', [$this->startDate, $this->endDate])
            ->orderBy('tgl_pengajuan')
            ->get();

        // Isi data transaksi
        $counter = 1;
        $totalSemua = 0;
        function formatRupiah($value)
        {
            return $value ? 'Rp ' . number_format($value, 0, ',', '.') : '-';
        }

        foreach ($transactions as $transaction) {
            $transactionRow = [
                $counter++,
                $transaction->tgl_pengajuan,
                $transaction->divisi,
                $transaction->dataUser->name ?? '-',
                '(' . $transaction->kode_transaksi . ') ' . $transaction->tujuan,
                $transaction->keterangan,
                '-',
                '-',
                '-',
                '-',
                $transaction->tgl_keluar,
                '-',
                '-',
            ];

            $data[] = $transactionRow;

            $totalNominal = 0; // Untuk menghitung total nominal per transaksi

            foreach ($transaction->pembelianBahanDetails as $detail) {
                $details = json_decode($detail->details, true);
                $newdetails = json_decode($detail->new_details, true);
                $unitPrice = $details['unit_price'] ?? '-';
                $newUnitPrice = $newdetails['new_unit_price'] ?? '-';

                $nominalDibayarkan = $detail->new_sub_total && $detail->new_sub_total > 0
                    ? $detail->new_sub_total
                    : ($detail->sub_total ?? 0);

                // Tambahkan nominal dibayarkan ke total nominal
                $totalNominal += $nominalDibayarkan;

                $data[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $detail->dataBahan->nama_bahan ?? $detail->nama_bahan ?? '-',
                    $detail->jml_bahan ?? '-',
                    $unitPrice,
                    $detail->sub_total ?? 0,
                    '',
                    $newUnitPrice,
                    $nominalDibayarkan,
                ];
            }

            // Hitung biaya tambahan sesuai jenis pengajuan
            if ($transaction->jenis_pengajuan === 'Pembelian Bahan/Barang/Alat Impor') {
                $shippingCostDibayarkan = $transaction->new_shipping_cost && $transaction->new_shipping_cost > 0
                    ? $transaction->new_shipping_cost
                    : ($transaction->shipping_cost ?? 0);
                $fullAmountDibayarkan = $transaction->new_full_amount_fee && $transaction->new_full_amount_fee > 0
                    ? $transaction->new_full_amount_fee
                    : ($transaction->full_amount_fee ?? 0);
                $valueTodayDibayarkan = $transaction->new_value_today_fee && $transaction->new_value_today_fee > 0
                    ? $transaction->new_value_today_fee
                    : ($transaction->value_today_fee ?? 0);

                $totalNominal += $shippingCostDibayarkan + $fullAmountDibayarkan + $valueTodayDibayarkan;

                $data[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Shipping Cost',
                    '',
                    $transaction->shipping_cost ?? '-',
                    '',
                    '',
                    $transaction->new_shipping_cost ?? '-',
                    $shippingCostDibayarkan,
                ];
                $data[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Full Amount Fee',
                    '',
                    $transaction->full_amount_fee ?? '-',
                    '',
                    '',
                    $transaction->new_full_amount_fee ?? '-',
                    $fullAmountDibayarkan,
                ];
                $data[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Value Today Fee',
                    '',
                    $transaction->value_today_fee ?? '-',
                    '',
                    '',
                    $transaction->new_value_today_fee ?? '-',
                    $valueTodayDibayarkan,
                ];
            } elseif ($transaction->jenis_pengajuan === 'Pembelian Bahan/Barang/Alat Lokal') {
                $ongkir = $transaction->ongkir ?? 0;
                $asuransi = $transaction->asuransi ?? 0;
                $layanan = $transaction->layanan ?? 0;
                $jasaAplikasi = $transaction->jasa_aplikasi ?? 0;

                $totalNominal += $ongkir + $asuransi + $layanan + $jasaAplikasi;

                $data[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Ongkir',
                    '',
                    $transaction->ongkir ?? '-',
                    '',
                    '',
                    '',
                    $transaction->ongkir ?? '-',
                ];
                $data[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Asuransi',
                    '',
                    $transaction->asuransi ?? '-',
                    '',
                    '',
                    '',
                    $transaction->asuransi ?? '-',
                ];
                $data[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Layanan',
                    '',
                    $transaction->layanan ?? '-',
                    '',
                    '',
                    '',
                    $transaction->layanan ?? '-',
                ];
                $data[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Jasa Aplikasi',
                    '',
                    $transaction->jasa_aplikasi ?? '-',
                    '',
                    '',
                    '',
                    $transaction->jasa_aplikasi ?? '-',
                ];
            }

            $data[] = [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'Total',
                formatRupiah($totalNominal),
            ];
        }


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

        $columns = range('A', 'M');
        foreach ($columns as $column) {
            $sheet->mergeCells("{$column}3");
            $sheet->getStyle("{$column}3")->getFont()->setBold(true);
            $sheet->getStyle("{$column}3")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("{$column}3")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('d2d2db');
        }

        $colIndex = 12;
        $dateHeaders = [];

        $lastColumnIndex = $colIndex;
        $lastColumnLetter = $this->getColumnLetter($lastColumnIndex);

        $sheet->mergeCells("A1:{$lastColumnLetter}1");
        $sheet->mergeCells("A2:{$lastColumnLetter}2");

        $columnLetter = $this->getColumnLetter($colIndex);
        $sheet->mergeCells("{$columnLetter}3");
        $colIndex++;

        $headerFillStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '89D8FC'],
            ],
        ];

        $sheet->getStyle('A1:F1')->applyFromArray($headerFillStyle);
        $sheet->getStyle('A2:F2')->applyFromArray($headerFillStyle);

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];

        $sheet->getStyle("A3:{$highestColumn}{$highestRow}")->applyFromArray($borderStyle);

        $totalColumns = $colIndex + 2;
        for ($i = 0; $i <= $totalColumns; $i++) {
            $columnLetter = $this->getColumnLetter($i);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        $sheet->getStyle("L3:L{$highestRow}")
        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("M3:M{$highestRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        for ($row = 3; $row <= $highestRow; $row++) {
            $totalCell = $sheet->getCell("L{$row}");
            if ($totalCell->getValue() === 'Total') {
                $sheet->getStyle("G{$row}:M{$row}")
                    ->getFont()->setBold(true);
                $sheet->getStyle("G{$row}:M{$row}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFF00');
                $sheet->getStyle("L{$row}:M{$row}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }
    }

    private function getColumnLetter($index)
    {
        $letters = '';
        while ($index >= 0) {
            $letters = chr($index % 26 + 65) . $letters;
            $index = floor($index / 26) - 1;
        }
        return $letters;
    }
}
