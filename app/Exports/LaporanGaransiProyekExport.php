<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Projek;
use App\Models\BahanRusak;
use App\Models\GaransiProjek;
use App\Models\LaporanProyek;
use App\Models\LaporanGaransiProyek;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanGaransiProyekExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected $garansi_proyek_id;

    public function __construct($garansi_proyek_id)
    {
        $this->garansi_proyek_id = $garansi_proyek_id;
    }

    public function array(): array
    {
        $data = [];
        $totalQty = 0;
        $totalSubTotal = 0;
        $projek = GaransiProjek::with('garansiProjekDetails.dataBahan', 'garansiProjekDetails.dataBahan.dataUnit', 'garansiProjekDetails.dataProduk',)->findOrFail($this->garansi_proyek_id);

        $formattedStartDate = Carbon::parse($projek->mulai_garansi)->format('d F Y');
        $formattedEndDate = Carbon::parse($projek->selesai_garansi)->format('d F Y');

        $data[] = ['PT ARTA TEKNOLOGI COMUNINDO', '', '', '', '', '', ''];
        $data[] = ['HPP GARANSI PROYEK', '', '', '', '', '', ''];
        $data[] = [''];

        $data[] = ['Kode Garansi', '', ': '.$projek->kode_garansi];
        $data[] = ['Nama Garansi', '', ': '.$projek->dataKontrak->nama_kontrak];
        $data[] = ['Masa Pekerjaan', '', ': '.$formattedStartDate . ' - ' . $formattedEndDate];
        $data[] = [''];

        $data[] = ['No', 'Nama Barang/Bahan', 'Qty', 'Satuan', 'Harga Satuan', 'Total'];

        foreach ($projek->garansiProjekDetails as $index => $detail) {
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
                number_format($detail->sub_total, 2, ',', '.'),
            ];


            $totalQty += $detail->qty;
            $totalSubTotal += $detail->sub_total;
        }


        $data[] = [
            'Total Pengeluaran Bahan',
            '',
            $totalQty,
            '',
            '',
            number_format($totalSubTotal, 2, ',', '.'),
        ];

        // Tambahkan Data Laporan Proyek di Bawah List Bahan
        $data[] = [''];
        $data[] = ['No', 'Biaya Tambahan', 'Qty', 'Satuan', 'Unit Price', 'Total Biaya', 'Keterangan', 'Tanggal'];

        $laporanProyek = LaporanGaransiProyek::where('garansi_projek_id', $this->garansi_proyek_id)->get();
        $totalQtyTambahan = 0;
        $totalSubTotalTambahan = 0;

        foreach ($laporanProyek as $index => $laporan) {
            $data[] = [
                $index + 1,
                $laporan->nama_biaya_tambahan,
                $laporan->qty,
                $laporan->satuan,
                number_format($laporan->unit_price, 2, ',', '.'),
                number_format($laporan->total_biaya, 2, ',', '.'),
                $laporan->keterangan,
                Carbon::parse($laporan->tanggal)->translatedFormat('d F Y')
            ];
            // $totalBiaya += $laporan->total_biaya;

            $totalQtyTambahan += $laporan->qty;
            $totalSubTotalTambahan += $laporan->total_biaya;
        }

        $data[] = [
            'Total Biaya Tambahan',
            '',
            $totalQtyTambahan,
            '',
            '',
            number_format($totalSubTotalTambahan, 2, ',', '.'),
            '',
            '',
        ];


        // Tambahkan Data Bahan Rusak di Bawah Laporan Proyek
        $data[] = [''];
        $data[] = ['No', 'Kode Transaksi', 'Nama Barang/Bahan', 'Qty', 'Satuan', 'Harga Satuan', 'Total', 'Tanggal Keluar'];
        $bahanRusak = BahanRusak::with('bahanRusakDetails.dataBahan', 'bahanRusakDetails.dataProduk')->where('garansi_projek_id', $this->garansi_proyek_id)->get();
        $totalQtyRusak = 0;
        $totalSubTotalRusak = 0;

        $noUrut = 1;
        foreach ($bahanRusak as $index => $rusak) {
            // Tambahkan header transaksi bahan rusak
            foreach ($rusak->bahanRusakDetails as $detailIndex => $detail) {
                $namaBarang = '-'; // Default jika keduanya null
                if ($detail->dataBahan) {
                    $namaBarang = $detail->dataBahan->nama_bahan;
                } elseif ($detail->dataProduk) {
                    $namaBarang = $detail->dataProduk->nama_bahan;
                    if (!empty($detail->serial_number)) {
                        $namaBarang .= ' (' . $detail->serial_number . ')';
                    }
                }

                $tanggalKeluar = $rusak->tgl_diterima ? Carbon::parse($rusak->tgl_diterima)->translatedFormat('d F Y') : '-';

                $data[] = [
                    $noUrut++,
                    $rusak->kode_transaksi, // Menampilkan kode transaksi
                    $namaBarang,
                    $detail->qty,
                    $detail->bahan->dataUnit->nama ?? 'Pcs',
                    number_format($detail->unit_price, 2, ',', '.'),
                    number_format($detail->sub_total, 2, ',', '.'),
                    $tanggalKeluar,
                ];

                $totalQtyRusak += $detail->qty;
                $totalSubTotalRusak += $detail->sub_total;
            }
        }

        // Tambahkan Total Keseluruhan
        $data[] = [
            'Total Pengeluaran Bahan Rusak',
            '',
            '',
            $totalQtyRusak,
            '',
            '',
            number_format($totalSubTotalRusak, 2, ',', '.'),
        ];


        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        $projek = GaransiProjek::with('garansiProjekDetails.dataBahan', 'garansiProjekDetails.dataBahan.dataUnit', 'garansiProjekDetails.dataProduk')->findOrFail($this->garansi_proyek_id);
        $rowAwalBahan = 8;
        $jumlahBahan = count($projek->garansiProjekDetails);
        $lastRowBahan = $rowAwalBahan + $jumlahBahan + 1; // Simpan posisi terakhir bagian bahan (termasuk total bahan)

        $laporanProyek = LaporanGaransiProyek::where('garansi_projek_id', $this->garansi_proyek_id)->get();
        $rowAwalLaporan = $lastRowBahan + 2; // Baris kosong sebelum header laporan proyek
        $jumlahLaporan = count($laporanProyek);
        $lastRowLaporan = $rowAwalLaporan + $jumlahLaporan + 1; // Simpan posisi terakhir bagian laporan proyek

        $bahanRusak = BahanRusak::with('bahanRusakDetails.dataBahan')->where('garansi_projek_id', $this->garansi_proyek_id)->get();
        $rowAwalBahanRusak = $lastRowLaporan + 2; // Baris kosong sebelum header laporan proyek
        // Menghitung jumlah total bahan rusak dari semua transaksi
        $jumlahBahanRusak = $bahanRusak->sum(fn($rusak) => $rusak->bahanRusakDetails->count());
        $lastRowBahanRusak = $rowAwalBahanRusak + $jumlahBahanRusak + 1; // Simpan posisi terakhir bagian laporan proyek


        // ===== Styling untuk Header =====
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

        // ===== Styling untuk Bagian Bahan =====
        $sheet->getStyle("A{$rowAwalBahan}:F{$rowAwalBahan}")->getFont()->setBold(true);
        $sheet->getStyle("A{$rowAwalBahan}:F{$rowAwalBahan}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        for ($row = $rowAwalBahan; $row <= $lastRowBahan; $row++) {
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('[$-421] #,##0');
            $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('[$-421] #,##0');
            $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getStyle("A{$rowAwalBahan}:F{$lastRowBahan}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        $sheet->mergeCells("A{$lastRowBahan}:B{$lastRowBahan}");
        $sheet->getStyle("A{$lastRowBahan}:F{$lastRowBahan}")->getFont()->setBold(true);
        $sheet->getStyle("A{$lastRowBahan}:B{$lastRowBahan}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ===== Styling untuk Bagian Biaya Tambahan (Laporan Proyek) =====
        $laporanHeaderRow = $rowAwalLaporan;

        $sheet->getStyle("A{$laporanHeaderRow}:H{$laporanHeaderRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$laporanHeaderRow}:H{$laporanHeaderRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$laporanHeaderRow}:H{$laporanHeaderRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        for ($row = $laporanHeaderRow; $row <= $lastRowLaporan; $row++) {
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('[$-421] #,##0');
            $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('[$-421] #,##0');
            $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getStyle("A{$laporanHeaderRow}:H{$lastRowLaporan}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        $sheet->mergeCells("A{$lastRowLaporan}:B{$lastRowLaporan}");
        $sheet->getStyle("A{$lastRowLaporan}:F{$lastRowLaporan}")->getFont()->setBold(true);
        $sheet->getStyle("A{$lastRowLaporan}:B{$lastRowLaporan}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


        // ===== Styling untuk Bagian Bahan Rusak =====
        $bahanRusakHeaderRow = $rowAwalBahanRusak;

        // Header Tabel
        $sheet->getStyle("A{$bahanRusakHeaderRow}:H{$bahanRusakHeaderRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$bahanRusakHeaderRow}:H{$bahanRusakHeaderRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$bahanRusakHeaderRow}:H{$bahanRusakHeaderRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        // Styling untuk Nomor, Qty, Satuan, dan Tanggal Keluar agar rata tengah
        for ($row = $bahanRusakHeaderRow + 1; $row <= $lastRowBahanRusak; $row++) {
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // No
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Qty
            $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Satuan
            $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Harga Satuan
            $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Total
            $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Tanggal Keluar

            // Format angka untuk harga satuan dan total
            $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('[$-421] #,##0');
            $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('[$-421] #,##0');
        }

        // Garis tepi untuk seluruh tabel bahan rusak
        $sheet->getStyle("A{$bahanRusakHeaderRow}:H{$lastRowBahanRusak}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        // Styling untuk baris total
        $sheet->mergeCells("A{$lastRowBahanRusak}:B{$lastRowBahanRusak}");
        $sheet->getStyle("A{$lastRowBahanRusak}:H{$lastRowBahanRusak}")->getFont()->setBold(true);
        $sheet->getStyle("A{$lastRowBahanRusak}:B{$lastRowBahanRusak}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


        $totalSubTotal = $projek->garansiProjekDetails->sum(fn($detail) => $detail->sub_total);
        $biayaTambahan = $laporanProyek->sum('total_biaya');
        $totalSubTotalRusak = $bahanRusak->sum(fn($rusak) => $rusak->bahanRusakDetails->sum(fn($detail) => $detail->sub_total));
        // dd($totalSubTotalRusak);
        // ===== Tambahkan 4 Baris Biaya di Bawah Bahan Rusak =====
        $Anggaran = $projek->anggaran ?? null;
        $biayaBahanProduk = $totalSubTotal;
        $biayaBahanRusak = $totalSubTotalRusak;
        $totalPengeluaran = $totalSubTotal + $biayaTambahan + $totalSubTotalRusak;
        $sisaAnggaran = $Anggaran - $totalPengeluaran;


        // Posisi awal untuk biaya tambahan setelah tabel bahan rusak
        $startRowBiaya = $lastRowBahanRusak + 2;

        $sheet->setCellValue("F{$startRowBiaya}", "Anggaran:");
        $sheet->setCellValue("G{$startRowBiaya}", "Rp. " . number_format($Anggaran, 2, ',', '.'));

        $sheet->setCellValue("F" . ($startRowBiaya + 1), "Biaya Bahan/Produk:");
        $sheet->setCellValue("G" . ($startRowBiaya + 1), "Rp. " . number_format($biayaBahanProduk, 2, ',', '.'));

        $sheet->setCellValue("F" . ($startRowBiaya + 2), "Biaya Tambahan:");
        $sheet->setCellValue("G" . ($startRowBiaya + 2), "Rp. " . number_format($biayaTambahan, 2, ',', '.'));

        $sheet->setCellValue("F" . ($startRowBiaya + 3), "Bahan Rusak:");
        $sheet->setCellValue("G" . ($startRowBiaya + 3), "Rp. " . number_format($biayaBahanRusak, 2, ',', '.'));

        $sheet->setCellValue("F" . ($startRowBiaya + 4), "Total Pengeluaran:");
        $sheet->setCellValue("G" . ($startRowBiaya + 4), "Rp. " . number_format($totalPengeluaran, 2, ',', '.'));

        $sheet->setCellValue("F" . ($startRowBiaya + 5), "Sisa Anggaran:");
        $sheet->setCellValue("G" . ($startRowBiaya + 5), "Rp. " . number_format($sisaAnggaran, 2, ',', '.'));

        // Buat format angka di kolom biaya agar tetap dalam format mata uang
        for ($row = $startRowBiaya; $row <= $startRowBiaya + 5; $row++) {
            $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('[$-421] #,##0.00');
        }

        // Buat teks di kolom F bold
        $sheet->getStyle("F{$startRowBiaya}:F" . ($startRowBiaya + 5))->getFont()->setBold(true);
        $sheet->getStyle("F{$startRowBiaya}:F" . ($startRowBiaya + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    }






    public function title(): string
    {
        return 'HPP PROJECT';
    }
}

