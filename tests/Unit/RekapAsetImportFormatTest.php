<?php

namespace Tests\Unit;

use App\Imports\RekapAsetImport;
use PHPUnit\Framework\TestCase;

class RekapAsetImportFormatTest extends TestCase
{
    public function test_it_maps_opname_headers_to_internal_columns(): void
    {
        $this->assertSame('tanggal_perolehan', RekapAsetImport::namaKolom('Tanggal Akuisisi'));
        $this->assertSame('harga_perolehan', RekapAsetImport::namaKolom('Nilai Perolehan'));
        $this->assertSame('ruangan', RekapAsetImport::namaKolom('Lokasi'));
        $this->assertSame('pic_jabatan', RekapAsetImport::namaKolom('PIC'));
        $this->assertSame('nama_pic', RekapAsetImport::namaKolom("Person yang\nMembawa"));
        $this->assertSame('kondisi_aset', RekapAsetImport::namaKolom("Kondisi\n(Baik/Rusak Ringan/Rusak)"));
        $this->assertSame('status_fisik', RekapAsetImport::namaKolom("Status Fisik\n(Ada/Tidak Ada/Dipinjam)"));
    }

    public function test_it_finds_dynamic_header_and_skips_total_and_repeated_headers(): void
    {
        $rows = [
            ['', '', 'PT Arta Teknologi Comunindo'],
            ['', '', 'WORKSHEET ASET OPNAME ACCOUNTING'],
            ['Peralatan Elektronik'],
            ['No', 'Nomor Aset', 'Nama Aset', 'Tanggal Akuisisi', 'Nilai Perolehan', 'Lokasi', 'PIC', 'Person yang Membawa', 'Kondisi (Baik/Rusak)', 'Status Fisik (Ada/Tidak Ada/Dipinjam)', 'Keterangan'],
            [1, '1/ATC/VII/2024', 'Advan Tab', '25/07/2024', '1,000,000.00', 'Ruang Supply Chain', 'Leader Supply Chain', 'Andi', 'B', 'A', 'SN: 123'],
            ['', '', 'Total', '', '1,000,000.00'],
            ['No', 'Nomor Aset', 'Nama Aset', 'Tanggal Akuisisi', 'Nilai Perolehan', 'Lokasi', 'PIC', 'Person yang Membawa', 'Kondisi', 'Status Fisik', 'Keterangan'],
            [2, '2/ATC/VII/2024', 'Laptop', '26/07/2024', 5000000, 'Ruang RnD', 'Manajer Hardware', 'Budi', 'RR', 'Dipinjam', ''],
        ];

        $hasil = (new RekapAsetImport)->bacaSheet($rows);

        $this->assertCount(2, $hasil);
        $this->assertSame('1/ATC/VII/2024', $hasil[0]['nomor_aset']);
        $this->assertSame('25/07/2024', $hasil[0]['tanggal_perolehan']);
        $this->assertSame('Leader Supply Chain', $hasil[0]['pic_jabatan']);
        $this->assertSame('Andi', $hasil[0]['nama_pic']);
        $this->assertSame(5, $hasil[0]['_baris']);
        $this->assertSame('2/ATC/VII/2024', $hasil[1]['nomor_aset']);
        $this->assertSame(8, $hasil[1]['_baris']);
    }
}
