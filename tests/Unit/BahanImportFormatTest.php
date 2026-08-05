<?php

namespace Tests\Unit;

use App\Imports\BahanImport;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BahanImportFormatTest extends TestCase
{
    public function test_it_maps_export_headers_and_keeps_excel_row_numbers(): void
    {
        $rows = [
            ['Kode Bahan', 'Nama Bahan', 'Jenis Bahan', 'Satuan Unit', 'Penempatan', 'Status', 'Supplier'],
            ['BHN-001', 'Kabel LAN', 'Elektronik', 'Meter', 'Rak A', 'Digunakan', 'Supplier A, Supplier B'],
        ];

        $result = (new BahanImport)->bacaSheet($rows);

        $this->assertCount(1, $result);
        $this->assertSame('BHN-001', $result[0]['kode_bahan']);
        $this->assertSame('Meter', $result[0]['satuan_unit']);
        $this->assertSame('Supplier A, Supplier B', $result[0]['supplier']);
        $this->assertSame(2, $result[0]['_baris']);
    }

    public function test_it_accepts_old_export_without_optional_status_and_supplier_columns(): void
    {
        $rows = [
            ['Kode Bahan', 'Nama Bahan', 'Jenis Bahan', 'Satuan Unit', 'Penempatan'],
            ['BHN-002', 'Konektor', 'Elektronik', 'Pcs', 'Rak B'],
        ];

        $result = (new BahanImport)->bacaSheet($rows);

        $this->assertArrayNotHasKey('status', $result[0]);
        $this->assertArrayNotHasKey('supplier', $result[0]);
    }

    public function test_it_rejects_a_file_without_required_columns(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kolom wajib tidak ditemukan: Satuan Unit, Penempatan.');

        (new BahanImport)->bacaSheet([
            ['Kode Bahan', 'Nama Bahan', 'Jenis Bahan'],
            ['BHN-003', 'Kabel', 'Elektronik'],
        ]);
    }
}
