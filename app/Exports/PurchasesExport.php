<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Bahan;
use App\Models\PurchaseDetail;
use App\Models\BahanKeluarDetails;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchasesExport implements FromArray, WithHeadings, WithStyles
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

        $data[] = ["LAPORAN STOK BARANG " . $this->companyName];
        $data[] = ["Periode: " . $formattedPeriod];

        $tglBlnHeaders = ['No', 'Kode Barang', 'Nama Barang', 'Seri Barang', 'Satuan', 'Stok Awal'];

        for ($day = $this->startDay; $day <= $this->endDay; $day++) {
            $tglBlnHeaders[] = "$day/$this->startMonth";
        }

        $tglBlnHeaders[] = 'Stok Akhir';
        $tglBlnHeaders[] = 'Harga Terakhir';

        $data[] = $tglBlnHeaders;

        $dateNames = [];
        for ($day = $this->startDay; $day <= $this->endDay; $day++) {
            $dateNames[] = "Stok Masuk";
            $dateNames[] = "Harga Beli";
            $dateNames[] = "Stok Keluar";
        }

        $data[] = array_merge([], [], [], [], [], [], $dateNames, []);

        $bahan = Bahan::with(['dataUnit', 'jenisBahan'])
            ->whereHas('jenisBahan', function ($query) {
                $query->where('nama', '!=', 'Produksi')->where('nama', '!=', 'Projek RnD');
            })
            ->get();

        foreach ($bahan as $index => $item) {
            $stokAwal = $this->getPreviousDayStokAkhir($item->id, $this->startDate);
            $row = [
                $index + 1,
                $item->kode_bahan,
                $item->nama_bahan,
                $item->seri_bahan,
                $item->dataUnit->nama ?? Null,
                $stokAwal
            ];

            // $stokAkhir = $stokAwal;

            for ($day = $this->startDay; $day <= $this->endDay; $day++) {
                $stokMasuk = PurchaseDetail::whereHas('purchase', function ($query) use ($day) {
                    $query->whereDate('tgl_masuk', Carbon::parse($this->startMonth . '/' . $day . '/' . $this->startDate)->toDateString());
                })
                ->where('bahan_id', $item->id)
                ->sum('qty');

                $hargaBeli = PurchaseDetail::whereHas('purchase', function ($query) use ($day) {
                    $query->whereDate('tgl_masuk', Carbon::parse($this->startMonth . '/' . $day . '/' . $this->startDate)->toDateString());
                })
                ->where('bahan_id', $item->id)
                ->value('unit_price') ?? '';
                $hargaBeli = $hargaBeli ? number_format($hargaBeli, 2, ',', '.') : '';

                $stokKeluar = BahanKeluarDetails::whereHas('bahanKeluar', function ($query) use ($day) {
                    $query->whereDate('tgl_keluar', Carbon::parse($this->startMonth . '/' . $day . '/' . $this->startDate)->toDateString())
                        ->where('status', 'Disetujui');
                })
                ->where('bahan_id', $item->id)
                ->sum('qty');

                // $stokAkhir += $stokMasuk - $stokKeluar;

                $row[] = $stokMasuk;
                $row[] = $hargaBeli;
                $row[] = $stokKeluar;
            }

            // $row[] = $stokAkhir;
            // $row[] = $this->getSisaStokAkhir($item->id, $this->endDate);
            $stokMasukPeriode = $this->getTotalStokMasuk($item->id, $this->startDate, $this->endDate);
            $stokKeluarPeriode = $this->getTotalStokKeluar($item->id, $this->startDate, $this->endDate);
            $stokAkhir = $stokAwal + $stokMasukPeriode - $stokKeluarPeriode;
            $row[] = $stokAkhir;

            // Tambah harga terakhir
            $hargaTerakhir = PurchaseDetail::join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
                ->where('purchase_details.bahan_id', $item->id)
                ->whereDate('purchases.tgl_masuk', '<=', Carbon::parse($this->endDate)->toDateString()) // Menggunakan $this->endDate
                ->orderBy('purchases.tgl_masuk', 'desc')
                ->value('purchase_details.unit_price');

            // Format harga terakhir jika ditemukan
            $hargaFormatted = $hargaTerakhir ? number_format($hargaTerakhir, 2, ',', '.') : '';
            $row[] = $hargaFormatted;


            $data[] = $row;
        }

        return $data;
    }

    private function getTotalStokMasuk($bahanId, $startDate, $endDate)
    {
        return PurchaseDetail::whereHas('purchase', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('tgl_masuk', [$startDate, $endDate]);
        })
        ->where('bahan_id', $bahanId)
        ->sum('qty');
    }

    private function getTotalStokKeluar($bahanId, $startDate, $endDate)
    {
        return BahanKeluarDetails::whereHas('bahanKeluar', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('tgl_keluar', [$startDate, $endDate])
                ->where('status', 'Disetujui');
        })
        ->where('bahan_id', $bahanId)
        ->sum('qty');
    }



    private function getPreviousDayStokAkhir($bahanId, $startDate)
    {
        // Total masuk sebelum startDate
        $totalMasuk = PurchaseDetail::whereHas('purchase', function ($query) use ($startDate) {
            $query->whereDate('tgl_masuk', '<', $startDate);
        })
        ->where('bahan_id', $bahanId)
        ->sum('qty');

        // Total keluar sebelum startDate
        $totalKeluar = BahanKeluarDetails::whereHas('bahanKeluar', function ($query) use ($startDate) {
            $query->whereDate('tgl_keluar', '<', $startDate)
                ->where('status', 'Disetujui');
        })
        ->where('bahan_id', $bahanId)
        ->sum('qty');

        $stokAwal = $totalMasuk - $totalKeluar;

        return $stokAwal > 0 ? $stokAwal : 0;
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

        $sheet->mergeCells('A3:A4');
        $sheet->mergeCells('B3:B4');
        $sheet->mergeCells('C3:C4');
        $sheet->mergeCells('D3:D4');
        $sheet->mergeCells('E3:E4');
        $sheet->mergeCells('F3:F4');
        $sheet->getStyle('A3:A4')->getFont()->setBold(true);
        $sheet->getStyle('B3:B4')->getFont()->setBold(true);
        $sheet->getStyle('C3:C4')->getFont()->setBold(true);
        $sheet->getStyle('D3:D4')->getFont()->setBold(true);
        $sheet->getStyle('E3:E4')->getFont()->setBold(true);
        $sheet->getStyle('F3:F4')->getFont()->setBold(true);
        $sheet->getStyle('A3:A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B3:B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C3:C4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D3:D4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E3:E4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F3:F4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


        $colIndex = 6;
        $dateHeaders = [];

        for ($day = $this->startDay; $day <= $this->endDay; $day++) {
            $columnIndex = 7 + ($day - $this->startDay) * 3 + 1; // Calculate column index for each "Harga Beli"
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getStyle($columnLetter)->getAlignment()->setHorizontal('right');
            $columnLetter = $this->getColumnLetter($colIndex);
            $endColumnLetter = $this->getColumnLetter($colIndex + 2);

            $sheet->mergeCells("{$columnLetter}3:{$endColumnLetter}3");
            $sheet->setCellValue("{$columnLetter}3", "$day/" . $this->startMonth);

            $sheet->getStyle("{$columnLetter}3:{$endColumnLetter}3")->getFont()->setBold(true);
            $sheet->getStyle("{$columnLetter}3:{$endColumnLetter}3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("{$columnLetter}4:{$endColumnLetter}4")->getFont()->setBold(true);
            $sheet->getStyle("{$columnLetter}4:{$endColumnLetter}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $colIndex += 3;

            $dateHeaders[] = $columnLetter;
        }

        $lastColumnIndex = $colIndex;
        $lastColumnLetter = $this->getColumnLetter($lastColumnIndex);

        $sheet->mergeCells("A1:{$lastColumnLetter}1");
        $sheet->mergeCells("A2:{$lastColumnLetter}2");

        $columnLetter = $this->getColumnLetter($colIndex);
        $sheet->mergeCells("{$columnLetter}3:{$columnLetter}4");
        $sheet->setCellValue("{$columnLetter}3", 'Stok Akhir');
        $sheet->getStyle("{$columnLetter}3:{$columnLetter}4")->getFont()->setBold(true);
        $sheet->getStyle("{$columnLetter}3:{$columnLetter}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $colIndex++;

        $hargaColumnLetter = $this->getColumnLetter($colIndex);
        $sheet->mergeCells("{$hargaColumnLetter}3:{$hargaColumnLetter}4");
        $sheet->setCellValue("{$hargaColumnLetter}3", 'Harga Terakhir');
        $sheet->getStyle("{$hargaColumnLetter}3:{$hargaColumnLetter}4")->getFont()->setBold(true);
        $sheet->getStyle("{$hargaColumnLetter}3:{$hargaColumnLetter}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$hargaColumnLetter}5:{$hargaColumnLetter}{$sheet->getHighestRow()}")
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $colIndex++;


        $headerFillStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '89D8FC'],
            ],
        ];

        $sheet->getStyle('A1:F1')->applyFromArray($headerFillStyle);
        $sheet->getStyle('A2:F2')->applyFromArray($headerFillStyle);

        $colIndex = 6;
        $stokHeaders = ["Stok Masuk", "Harga Beli", "Stok Keluar"];

        for ($i = 0; $i < count($dateHeaders); $i++) {
            $columnLetter = $dateHeaders[$i];

            foreach ($stokHeaders as $stokHeader) {
                $sheet->setCellValue("{$columnLetter}4", $stokHeader);
                $columnLetter = $this->getColumnLetter(++$colIndex);
            }
        }

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
