<?php

namespace Tests\Feature;

use App\Imports\BahanImport;
use App\Models\Bahan;
use App\Models\JenisBahan;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class BahanImportUpsertTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('jenis_bahan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });
        Schema::create('unit', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });
        Schema::create('supplier', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });
        Schema::create('bahan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_bahan')->unique();
            $table->string('nama_bahan');
            $table->string('status')->default('Digunakan');
            $table->foreignId('jenis_bahan_id');
            $table->integer('stok_awal');
            $table->foreignId('unit_id');
            $table->string('kondisi');
            $table->string('penempatan');
            $table->string('gambar')->nullable();
            $table->timestamps();
        });
        Schema::create('bahan_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bahan_id');
            $table->foreignId('supplier_id');
            $table->timestamps();
        });
    }

    public function test_it_adds_new_materials_and_updates_existing_materials_from_one_import(): void
    {
        $jenisLama = JenisBahan::create(['nama' => 'Umum']);
        $jenisBaru = JenisBahan::create(['nama' => 'Elektronik']);
        $unitLama = Unit::create(['nama' => 'Pcs']);
        $unitBaru = Unit::create(['nama' => 'Meter']);
        $supplierLama = Supplier::create(['nama' => 'Supplier Lama']);
        $supplierBaru = Supplier::create(['nama' => 'Supplier Baru']);

        $existing = Bahan::create([
            'kode_bahan' => 'BHN-001',
            'nama_bahan' => 'Nama Lama',
            'status' => 'Digunakan',
            'jenis_bahan_id' => $jenisLama->id,
            'stok_awal' => 12,
            'unit_id' => $unitLama->id,
            'kondisi' => 'Baik',
            'penempatan' => 'Rak Lama',
        ]);
        $existing->suppliers()->attach($supplierLama);

        $rows = (new BahanImport)->bacaSheet([
            ['Kode Bahan', 'Nama Bahan', 'Jenis Bahan', 'Satuan Unit', 'Penempatan', 'Status', 'Supplier'],
            ['BHN-001', 'Kabel LAN', 'Elektronik', 'Meter', 'Rak A', 'Tidak digunakan', 'Supplier Baru'],
            ['BHN-002', 'Konektor', 'Umum', 'Pcs', 'Rak B', 'Digunakan', 'Supplier Lama'],
        ]);

        $import = new BahanImport;
        DB::transaction(fn () => $import->prosesBaris($rows));

        $existing->refresh();
        $this->assertSame('Kabel LAN', $existing->nama_bahan);
        $this->assertSame($jenisBaru->id, $existing->jenis_bahan_id);
        $this->assertSame($unitBaru->id, $existing->unit_id);
        $this->assertSame('Tidak digunakan', $existing->status);
        $this->assertSame(12, $existing->stok_awal, 'Kolom yang tidak ada dalam file tidak boleh dihapus.');
        $this->assertSame([$supplierBaru->id], $existing->suppliers()->pluck('supplier.id')->all());

        $created = Bahan::where('kode_bahan', 'BHN-002')->firstOrFail();
        $this->assertSame('Konektor', $created->nama_bahan);
        $this->assertSame(0, $created->stok_awal);
        $this->assertSame('Baik', $created->kondisi);
        $this->assertSame([$supplierLama->id], $created->suppliers()->pluck('supplier.id')->all());
        $this->assertSame(1, $import->jumlahBaru);
        $this->assertSame(1, $import->jumlahDiperbarui);
        $this->assertSame(0, $import->jumlahTidakBerubah);
    }

    public function test_it_rolls_back_the_whole_file_when_one_row_is_invalid(): void
    {
        JenisBahan::create(['nama' => 'Umum']);
        Unit::create(['nama' => 'Pcs']);

        $rows = (new BahanImport)->bacaSheet([
            ['Kode Bahan', 'Nama Bahan', 'Jenis Bahan', 'Satuan Unit', 'Penempatan', 'Supplier'],
            ['BHN-010', 'Baris Valid', 'Umum', 'Pcs', 'Rak A', ''],
            ['BHN-011', 'Baris Tidak Valid', 'Umum', 'Pcs', 'Rak B', 'Supplier Belum Ada'],
        ]);

        try {
            DB::transaction(fn () => (new BahanImport)->prosesBaris($rows));
            $this->fail('Import seharusnya gagal karena supplier belum terdaftar.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString("Supplier 'Supplier Belum Ada'", $e->getMessage());
        }

        $this->assertDatabaseCount('bahan', 0);
    }
}
