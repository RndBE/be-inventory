<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\ProjekRnd;
use App\Services\ProductFlowService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjekRndRekapExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected $search;
    protected ProductFlowService $flowService;

    // Baris tempat header kolom berada (dihitung dari blok judul di atasnya).
    protected $headingRow = 5;

    public function __construct($search = null)
    {
        $this->search = $search;
        $this->flowService = new ProductFlowService();
    }

    public function array(): array
    {
        $projek_rnds = ProjekRnd::with([
            'projekRndDetails.dataBahan.dataUnit',
            'projekRndDetails.dataProduk.bahanSetengahjadi',
        ])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('mulai_projek_rnd', 'like', '%' . $this->search . '%')
                        ->orWhere('selesai_projek_rnd', 'like', '%' . $this->search . '%')
                        ->orWhere('nama_projek_rnd', 'like', '%' . $this->search . '%')
                        ->orWhere('status', 'like', '%' . $this->search . '%')
                        ->orWhere('kode_projek_rnd', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('id', 'desc')
            ->get();

        $data = [];

        // Blok judul
        $data[] = ['PT ARTA TEKNOLOGI COMUNINDO'];
        $data[] = ['REKAP PROYEK RnD'];
        $data[] = ['Tanggal Cetak : ' . Carbon::now()->timezone('Asia/Jakarta')->format('d F Y H:i')];
        $data[] = [''];

        // Header kolom (baris ke-$headingRow)
        $data[] = [
            'No',
            'Kode Proyek',
            'Nama Proyek',
            'Serial Number',
            'Pengaju',
            'Jenis Riset',
            'Mulai Proyek',
            'Selesai Proyek',
            'Status',
            'Keterangan Proyek',
            'Proposal Riset',
            'Surat Tugas Riset',
            'Laporan Hasil Riset',
            'Keterangan Status Akhir',
            'Nama Barang/Bahan',
            'Qty',
            'Satuan',
            'Rincian Harga',
            'Total',
            'Keterangan Barang',
            'Jenis Item',
            'Kode Sumber',
            'Asal Flow',
            'Serial Number Flow',
            'Tujuan Flow',
            'Kode Tujuan',
            'Status Flow',
        ];

        $grandTotal = 0;

        foreach ($projek_rnds as $index => $projek_rnd) {
            $no = $index + 1;
            $jenisRiset = $projek_rnd->is_riset_lapangan ? 'Riset Lapangan' : 'Riset Internal';
            $mulai = $projek_rnd->mulai_projek_rnd
                ? Carbon::parse($projek_rnd->mulai_projek_rnd)->format('d F Y')
                : '-';
            $selesai = $projek_rnd->selesai_projek_rnd
                ? Carbon::parse($projek_rnd->selesai_projek_rnd)->format('d F Y')
                : '-';

            // Status dokumen riset (mengikuti tampilan halaman info)
            $proposalStatus = $projek_rnd->is_riset_lapangan
                ? ($projek_rnd->file_proposal_riset ? 'Sudah diupload' : 'Belum diupload')
                : 'Tidak diperlukan';
            $suratTugasStatus = $projek_rnd->is_riset_lapangan
                ? ($projek_rnd->file_surat_tugas_riset ? 'Sudah diupload' : 'Belum diupload')
                : 'Tidak diperlukan';
            $laporanStatus = $projek_rnd->file_laporan ? 'Sudah diupload' : 'Belum diupload';
            $keteranganStatus = $projek_rnd->keterangan_status ?? '-';

            $details = $projek_rnd->projekRndDetails;

            if ($details->isEmpty()) {
                // Tetap tampilkan proyek walau belum ada detail bahan.
                $data[] = [
                    $no,
                    $projek_rnd->kode_projek_rnd,
                    $projek_rnd->nama_projek_rnd,
                    $projek_rnd->serial_number ?? '-',
                    $projek_rnd->pengaju,
                    $jenisRiset,
                    $mulai,
                    $selesai,
                    $projek_rnd->status,
                    $projek_rnd->keterangan,
                    $proposalStatus,
                    $suratTugasStatus,
                    $laporanStatus,
                    $keteranganStatus,
                    '-',
                    0,
                    '-',
                    '-',
                    0,
                    '-',
                    'Tidak diketahui',
                    '-',
                    'Tidak diketahui',
                    '-',
                    'Proyek RnD',
                    $projek_rnd->kode_projek_rnd,
                    $projek_rnd->status ?? '-',
                ];
                continue;
            }

            $first = true;
            foreach ($details as $detail) {
                $detail->setRelation('projekRnd', $projek_rnd);

                $detailsArray = json_decode($detail->details, true) ?? [];
                $detailsFormatted = [];
                foreach ($detailsArray as $item) {
                    $detailsFormatted[] = ($item['qty'] ?? 0) . 'x' . ($item['unit_price'] ?? 0);
                }
                $rincianHarga = implode(', ', $detailsFormatted);

                $namaBarang = $detail->dataProduk
                    ? $detail->dataProduk->nama_bahan . ' (' . ($detail->serial_number ?? '-') . ')'
                    : ($detail->dataBahan->nama_bahan ?? '-');

                // Kolom informasi proyek hanya ditulis di baris bahan pertama;
                // baris bahan berikutnya dari proyek yang sama dikosongkan.
                $data[] = [
                    $first ? $no : '',
                    $first ? $projek_rnd->kode_projek_rnd : '',
                    $first ? $projek_rnd->nama_projek_rnd : '',
                    $first ? ($projek_rnd->serial_number ?? '-') : '',
                    $first ? $projek_rnd->pengaju : '',
                    $first ? $jenisRiset : '',
                    $first ? $mulai : '',
                    $first ? $selesai : '',
                    $first ? $projek_rnd->status : '',
                    $first ? $projek_rnd->keterangan : '',
                    $first ? $proposalStatus : '',
                    $first ? $suratTugasStatus : '',
                    $first ? $laporanStatus : '',
                    $first ? $keteranganStatus : '',
                    $namaBarang,
                    $detail->qty,
                    $detail->dataBahan->dataUnit->nama ?? 'Pcs',
                    $rincianHarga,
                    $detail->sub_total,
                    $detail->keterangan_penanggungjawab ?? '-',
                    ...$this->flowService->values($this->flowService->forProjekRndDetail($detail)),
                ];

                $grandTotal += $detail->sub_total;
                $first = false;
            }
        }

        // Baris total keseluruhan (grand total di kolom S / Total)
        $data[] = [
            'Total HPP Seluruh Proyek RnD',
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            $grandTotal,
            '', '', '', '', '', '', '', '',
        ];

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        $headingRow = $this->headingRow;
        $lastRow = $sheet->getHighestRow();
        $lastCol = 'AA';

        // Judul
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->mergeCells('A2:' . $lastCol . '2');
        $sheet->mergeCells('A3:' . $lastCol . '3');
        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header kolom
        $sheet->getStyle('A' . $headingRow . ':' . $lastCol . $headingRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $headingRow . ':' . $lastCol . $headingRow)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Format angka pada kolom Total (S)
        for ($row = $headingRow + 1; $row <= $lastRow; $row++) {
            $sheet->getStyle('S' . $row)->getNumberFormat()->setFormatCode('[$-421] #,##0');
        }

        // Border seluruh tabel
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];
        $sheet->getStyle('A' . $headingRow . ':' . $lastCol . $lastRow)->applyFromArray($borderStyle);

        // Baris total (baris terakhir) — gabung sampai sebelum kolom Total (S)
        $sheet->mergeCells('A' . $lastRow . ':R' . $lastRow);
        $sheet->getStyle('A' . $lastRow . ':' . $lastCol . $lastRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function title(): string
    {
        return 'Rekap Proyek RnD';
    }
}
