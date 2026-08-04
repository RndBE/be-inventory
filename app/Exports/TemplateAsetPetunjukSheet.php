<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet berisi seluruh petunjuk pengisian, dipindahkan ke sini supaya
 * jendela import di aplikasi tetap ringkas.
 *
 * Sheet ini tidak ikut terbaca saat file diunggah kembali, karena
 * RekapAsetImport hanya membaca sheet pertama.
 */
class TemplateAsetPetunjukSheet implements FromArray, WithStyles, WithTitle
{
    private const JUDUL = [1, 4, 10, 18, 26];

    public function array(): array
    {
        return [
            ['PETUNJUK PENGISIAN TEMPLATE IMPORT REKAP ASET'],
            ['Isi datanya di sheet "Data Aset". Jangan mengubah atau menghapus baris judul kolomnya.'],
            [''],

            ['1. KOLOM WAJIB (khusus aset baru)'],
            ['nomor_aset', 'Nomor aset, harus unik. Ini yang dipakai sistem untuk mencocokkan data.'],
            ['nama_aset', 'Nama barang. Harus sudah terdaftar di menu Data Master > Barang Aset.'],
            ['nama_penanggungjawab', 'Nama penanggung jawab, harus sama dengan nama user di sistem.'],
            ['', 'Untuk aset yang NOMORNYA SUDAH ADA, ketiga kolom ini boleh dikosongkan.'],
            [''],

            ['2. KOLOM OPSIONAL'],
            ['serial_number', 'Nomor seri dari pabrik, bebas diisi.'],
            ['merek', 'Merek atau tipe aset, mis. Lenovo ThinkPad E14. Dicetak di kolom'],
            ['', 'Merek/Tipe pada Berita Acara Serah Terima Aset, jadi sebaiknya diisi.'],
            ['link_gambar', 'Tautan foto aset, mis. tautan Google Drive.'],
            ['tanggal_perolehan', 'Tanggal aset diperoleh. Pakai format tanggal Excel, atau ketik'],
            ['', '2025-05-31 maupun 31-05-2025 (hari-bulan-tahun).'],
            ['jumlah_aset', 'Jumlah unit, isi angka saja.'],
            ['keterangan', 'Catatan bebas tentang aset ini.'],
            [''],

            ['3. ATURAN PENGISIAN'],
            ['harga_perolehan', 'Boleh ditulis 1500000 maupun 1.500.000. Simbol Rp boleh ikut.'],
            ['', 'Catatan: kalau mengetik nilai pendek seperti 1.500, Excel akan mengubahnya'],
            ['', 'menjadi 1,5. Untuk nilai ribuan pendek, tulis saja 1500.'],
            ['kondisi_aset', 'Hanya boleh diisi Baik atau Rusak. Selain itu akan ditolak.'],
            ['ruangan', 'Boleh diisi kode maupun nama ruangan, tapi harus sudah terdaftar'],
            ['', 'di menu Aset > Ruangan Aset.'],
            ['nama_pic', 'Nama karyawan pemegang aset. Harus sama dengan nama user di sistem.'],
            [''],

            ['4. CARA KERJA IMPORT ULANG'],
            ['', 'File yang sama boleh diimport berkali-kali. Pencocokannya lewat nomor_aset:'],
            ['', '- Nomor yang belum ada akan dibuat sebagai aset baru.'],
            ['', '- Nomor yang sudah ada hanya diperbarui pada kolom yang isinya berbeda.'],
            ['', '- Sel yang dikosongkan di Excel diabaikan, jadi data lama tidak terhapus.'],
            ['', '- Kalau ada satu baris yang salah, seluruh import dibatalkan.'],
            [''],
            ['', 'Setelah import selesai, sistem memberi tahu berapa aset yang dibuat,'],
            ['', 'berapa yang diperbarui, dan berapa yang tidak berubah.'],
        ];
    }

    public function title(): string
    {
        return 'Petunjuk Pengisian';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        foreach (self::JUDUL as $baris) {
            $sheet->getStyle("A{$baris}")->getFont()->setBold(true);
        }

        // Nama kolom di sisi kiri dibedakan supaya mudah dipindai.
        foreach (range(1, 35) as $baris) {
            $nilai = $sheet->getCell("A{$baris}")->getValue();

            if ($nilai && !in_array($baris, self::JUDUL) && !str_contains((string) $nilai, ' ')) {
                $sheet->getStyle("A{$baris}")->getFont()->getColor()->setRGB('1D4ED8');
            }
        }

        $sheet->getColumnDimension('A')->setWidth(24);
        $sheet->getColumnDimension('B')->setWidth(80);
        $sheet->getStyle('B1:B40')->getAlignment()->setWrapText(true);

        return [];
    }
}
