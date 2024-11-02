<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Purchase;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchasesExport implements FromArray, WithHeadings, WithEvents
{
    protected $startDate;
    protected $endDate;
    protected $companyName;

    public function __construct($startDate, $endDate, $companyName)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->companyName = $companyName;
    }

    public function array(): array
    {
        $data = [];

        // Format the start and end dates
        $formattedStartDate = Carbon::parse($this->startDate)->format('d-F-Y');
        $formattedEndDate = Carbon::parse($this->endDate)->format('d-F-Y');

        // Add company name and period, merge them for A-G
        $data[] = [$this->companyName];
        $data[] = ["Periode: " . $formattedStartDate . " - " . $formattedEndDate];
        $data[] = []; // Empty line for spacing

        // Get the purchase details
        $purchases = Purchase::with(['purchaseDetails.dataBahan'])
            ->whereBetween('tgl_masuk', [$this->startDate, $this->endDate])
            ->get();

        // Group by bahan
        $bahanDetails = [];
        foreach ($purchases as $purchase) {
            foreach ($purchase->purchaseDetails as $detail) {
                $bahan = $detail->dataBahan;
                if (!isset($bahanDetails[$bahan->id])) {
                    $bahanDetails[$bahan->id] = [
                        'bahan' => $bahan,
                        'details' => [],
                        'totalQty' => 0,
                        'totalSubTotal' => 0,
                    ];
                }

                $bahanDetails[$bahan->id]['details'][] = [
                    'tgl_masuk' => \Carbon\Carbon::parse($purchase->tgl_masuk)->format('d-F-Y H:i:s'),
                    'kode_transaksi' => $purchase->kode_transaksi,
                    'unit_id' => $detail->dataBahan->dataUnit->nama ?? null, // Assuming this field exists
                    'unit_price' => $detail->unit_price,
                    'qty' => $detail->qty,
                    'sub_total' => $detail->sub_total,
                ];

                // Update totals
                $bahanDetails[$bahan->id]['totalQty'] += $detail->qty;
                $bahanDetails[$bahan->id]['totalSubTotal'] += $detail->sub_total;
            }
        }

        // Build the data array
        foreach ($bahanDetails as $bahanDetail) {
            // Header for each bahan
            $data[] = [$bahanDetail['bahan']->kode_bahan, $bahanDetail['bahan']->nama_bahan];
            $data[] = ['Tanggal Masuk', 'Kode Transaksi', 'Satuan Unit', 'Unit Price', 'Qty', 'Sub Total'];

            // Add details for each bahan
            foreach ($bahanDetail['details'] as $detail) {
                $data[] = [
                    $detail['tgl_masuk'],
                    $detail['kode_transaksi'],
                    $detail['unit_id'], // Ensure this field is provided in the PurchaseDetail model
                    $detail['unit_price'],
                    $detail['qty'],
                    $detail['sub_total'],
                ];
            }

            // Summary for each bahan
            $data[] = ['', '', '', 'Total:' , $bahanDetail['totalQty'], $bahanDetail['totalSubTotal']];

            // Add an empty row for spacing (merge columns A to F)
            $data[] = ['', '', '', '', '', '']; // Empty row for spacing
            $data[] = []; // Add another empty row for more spacing
        }

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge cells for company name and period
                $sheet->mergeCells('A1:F1');
                $sheet->mergeCells('A2:F2');
                $sheet->getStyle('A1:F1')->getFont()->setBold(true);
                $sheet->getStyle('A2:F2')->getFont()->setBold(true);
                $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A2:F2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Initialize row counter
                $rowCounter = 4;

                // Get the number of rows
                $totalRows = count($sheet->toArray());

                // Loop through the sheet to style specific rows
                foreach (range(4, $totalRows) as $row) {

                    if ($rowCounter < $totalRows && $sheet->getCell("A{$rowCounter}")->getValue() === 'Tanggal Masuk') {
                        // Set color for this header row
                        $headerRange = "A{$rowCounter}:F{$rowCounter}";
                        $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $sheet->getStyle($headerRange)->getFill()->getStartColor()->setARGB('d3d3d3'); // Color code
                        $sheet->getStyle($headerRange)->getFont()->setBold(true);
                        // Center the text in the header
                        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    }
                    // Apply background color to the "Total" row
                    if ($sheet->getCell("D{$rowCounter}")->getValue() === 'Total:') {
                        $totalRowRange = "A{$rowCounter}:F{$rowCounter}";
                        $sheet->getStyle($totalRowRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $sheet->getStyle($totalRowRange)->getFill()->getStartColor()->setARGB('d3d3d3'); // Light yellow color
                        $sheet->getStyle($totalRowRange)->getFont()->setBold(true);
                        $sheet->getStyle($totalRowRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    }

                    // Check if this row is for summary to merge and style it
                    if ($sheet->getCell("A{$rowCounter}")->getValue() === '') {
                        $rowCounter++;
                        continue;
                    }

                    // Format the unit price and sub total columns as Rupiah currency
                    $sheet->getStyle("D{$rowCounter}")->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle("F{$rowCounter}")->getNumberFormat()->setFormatCode('[$-421] #,##0');

                    // Move to the next row
                    $rowCounter++;
                }

                // Auto-size the columns
                foreach (range('A', 'F') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }


}
