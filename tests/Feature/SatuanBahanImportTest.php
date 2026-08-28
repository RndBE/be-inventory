<?php

namespace Tests\Feature;

use App\Imports\BahanImport;
use App\Models\Bahan;
use App\Models\JenisBahan;
use App\Models\PurchaseDetail;
use App\Models\Unit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/**
 * Panjang standar yang masuk lewat import bahan.
 *
 * Import adalah jalur kedua yang bisa mengubah `bahan.panjang_standar`, di
 * samping form edit. Bedanya, satu sel Excel bisa mengubah puluhan bahan
 * sekaligus tanpa ada yang melihat satu per satu — jadi penjagaannya harus
 * sama ketat dengan yang di BahanController::update(), bukan lebih longgar.
 *
 * Yang dijaga: begitu panjang standar terisi, aplikasi membaca kolom `sisa`
 * sebagai cm. Kalau lot lama menyimpan angkanya dalam batang dan tidak ikut
 * dikonversi, stok yang tadinya 10 batang mendadak jadi 10 cm — menyusut 600
 * kali, tanpa error apa pun.
 */
class SatuanBahanImportTest extends TestCase
{
    private const PANJANG = 600;

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
            $table->integer('panjang_standar')->nullable();
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
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('bahan_id');
            $table->integer('panjang_standar')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('sisa', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->timestamps();
        });

        JenisBahan::create(['nama' => 'Umum']);
        Unit::create(['nama' => 'Batang']);
    }

    private function pipa(?int $panjangStandar = null): Bahan
    {
        return Bahan::create([
            'kode_bahan' => 'PIPA-01',
            'nama_bahan' => 'Pipa Uji',
            'status' => 'Digunakan',
            'jenis_bahan_id' => 1,
            'stok_awal' => 0,
            'unit_id' => 1,
            'panjang_standar' => $panjangStandar,
            'kondisi' => 'Baik',
            'penempatan' => 'Rak A',
        ]);
    }

    private function lot(Bahan $bahan, $qty, $sisa, ?int $panjangStandar = null): PurchaseDetail
    {
        return PurchaseDetail::create([
            'purchase_id' => 1,
            'bahan_id' => $bahan->id,
            'panjang_standar' => $panjangStandar,
            'qty' => $qty,
            'sisa' => $sisa,
            'unit_price' => 175000,
            'sub_total' => $qty * 175000,
        ]);
    }

    /**
     * Jalankan import satu baris dengan kolom panjang opsional.
     *
     * `$panjang` null berarti kolomnya ada tapi selnya kosong — itu yang
     * dipakai untuk menguji pengosongan. Untuk menguji file yang kolomnya tidak
     * ada sama sekali, pakai importTanpaKolomPanjang().
     */
    private function import(?string $panjang): void
    {
        $rows = (new BahanImport)->bacaSheet([
            ['Kode Bahan', 'Nama Bahan', 'Jenis Bahan', 'Satuan Unit', 'Penempatan', 'Panjang per Batang (cm)'],
            ['PIPA-01', 'Pipa Uji', 'Umum', 'Batang', 'Rak A', $panjang ?? ''],
        ]);

        DB::transaction(fn () => (new BahanImport)->prosesBaris($rows));
    }

    private function importTanpaKolomPanjang(): void
    {
        $rows = (new BahanImport)->bacaSheet([
            ['Kode Bahan', 'Nama Bahan', 'Jenis Bahan', 'Satuan Unit', 'Penempatan'],
            ['PIPA-01', 'Pipa Diperbarui', 'Umum', 'Batang', 'Rak B'],
        ]);

        DB::transaction(fn () => (new BahanImport)->prosesBaris($rows));
    }

    public function test_bahan_tanpa_lot_boleh_diisi_panjang_standar(): void
    {
        $pipa = $this->pipa();

        $this->import('600');

        $this->assertSame(self::PANJANG, $pipa->refresh()->panjang_standar);
    }

    /**
     * Sisa yang masih berjalan bukan halangan untuk pengisian pertama: angkanya
     * masih dalam batang dan ikut dikonversi, jadi tidak ada stok yang berubah
     * arti diam-diam. Ini yang membuat bahan lama bisa dijadikan batangan tanpa
     * harus menghabiskan stoknya lebih dulu.
     */
    public function test_panjang_standar_boleh_diisi_walau_stok_masih_berjalan(): void
    {
        $pipa = $this->pipa();
        $lot = $this->lot($pipa, 10, 4);

        $this->import('600');

        $this->assertSame(self::PANJANG, $pipa->refresh()->panjang_standar);

        $lot->refresh();
        $this->assertEquals(6000, $lot->qty);
        $this->assertEquals(2400, $lot->sisa, 'Sisa 4 batang harus jadi 2400 cm, bukan tetap 4.');
        $this->assertSame(self::PANJANG, (int) $lot->panjang_standar);
        $this->assertEqualsWithDelta(291.6667, (float) $lot->unit_price, 0.0001);
    }

    /**
     * Yang tidak bisa dipulihkan adalah mengubah panjang yang sudah dipakai:
     * lot berjalan sudah menyimpan cm, dan tidak ada acuan untuk mengonversinya
     * ulang ke ukuran baru.
     */
    public function test_panjang_standar_ditolak_kalau_diubah_saat_stok_berjalan(): void
    {
        $pipa = $this->pipa(self::PANJANG);
        $this->lot($pipa, 6000, 3000, self::PANJANG);

        try {
            $this->import('400');
            $this->fail('Import seharusnya ditolak karena panjangnya sudah dipakai lot berjalan.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('masih punya sisa stok', $e->getMessage());
            $this->assertStringContainsString('PIPA-01', $e->getMessage());
        }

        $this->assertSame(self::PANJANG, $pipa->refresh()->panjang_standar);
    }

    /**
     * Lot berjalan yang sudah membawa salinan panjang berarti angkanya sudah cm
     * padahal masternya bukan bahan batangan. Mengonversinya lagi akan
     * menggandakannya, jadi pengisian pertama pun ditolak di kasus ini.
     */
    public function test_panjang_standar_ditolak_kalau_ada_lot_berjalan_yang_sudah_cm(): void
    {
        $pipa = $this->pipa();
        $this->lot($pipa, 6000, 3000, self::PANJANG);

        try {
            $this->import('600');
            $this->fail('Import seharusnya ditolak karena ada lot berjalan yang sudah tercatat cm.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('sudah tercatat dalam cm', $e->getMessage());
        }

        $this->assertNull($pipa->refresh()->panjang_standar);
    }

    public function test_panjang_standar_tidak_bisa_dikosongkan_kalau_sudah_ada_riwayat(): void
    {
        $pipa = $this->pipa(self::PANJANG);
        $this->lot($pipa, 6000, 0, self::PANJANG);

        try {
            $this->import(null);
            $this->fail('Import seharusnya ditolak karena riwayatnya sudah dalam cm.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('tidak bisa dikosongkan', $e->getMessage());
        }

        $this->assertSame(self::PANJANG, $pipa->refresh()->panjang_standar);
    }

    /**
     * Lot yang sisanya sudah nol tidak punya angka stok yang bisa salah dibaca,
     * jadi boleh lewat — tapi angkanya tetap harus dikonversi supaya riwayatnya
     * terbaca benar.
     */
    public function test_lot_lama_ikut_dikonversi_saat_panjang_standar_diisi(): void
    {
        $pipa = $this->pipa();
        $lot = $this->lot($pipa, 10, 0);

        $this->import('600');

        $lot->refresh();
        $this->assertEquals(6000, $lot->qty);
        $this->assertEquals(0, $lot->sisa);
        $this->assertSame(self::PANJANG, (int) $lot->panjang_standar);
        $this->assertEqualsWithDelta(291.6667, (float) $lot->unit_price, 0.0001);
        $this->assertEquals(1750000, $lot->sub_total, 'Angka pembukuan tidak boleh ikut berubah.');
    }

    /**
     * File hasil export lama tidak punya kolom ini. Ketiadaannya tidak boleh
     * dibaca sebagai perintah mengosongkan.
     */
    public function test_file_tanpa_kolom_panjang_tidak_menyentuh_panjang_standar(): void
    {
        $pipa = $this->pipa(self::PANJANG);
        $this->lot($pipa, 6000, 3000, self::PANJANG);

        $this->importTanpaKolomPanjang();

        $pipa->refresh();
        $this->assertSame(self::PANJANG, $pipa->panjang_standar);
        $this->assertSame('Pipa Diperbarui', $pipa->nama_bahan);
    }

    /**
     * Import ulang file yang sama tidak boleh ditolak: nilainya tidak berubah,
     * jadi tidak ada angka stok yang berpindah arti.
     */
    public function test_panjang_standar_yang_sama_lolos_meski_stok_berjalan(): void
    {
        $pipa = $this->pipa(self::PANJANG);
        $this->lot($pipa, 6000, 3000, self::PANJANG);

        $this->import('600');

        $this->assertSame(self::PANJANG, $pipa->refresh()->panjang_standar);
    }

    /**
     * Konversi dan perubahan masternya satu paket. Kalau import gagal di baris
     * berikutnya, lot yang sudah dikonversi harus ikut dibatalkan — kalau tidak,
     * angkanya sudah dikali 600 sementara panjang standarnya masih kosong.
     */
    public function test_konversi_ikut_dibatalkan_kalau_import_gagal(): void
    {
        $pipa = $this->pipa();
        $lot = $this->lot($pipa, 10, 0);

        $rows = (new BahanImport)->bacaSheet([
            ['Kode Bahan', 'Nama Bahan', 'Jenis Bahan', 'Satuan Unit', 'Penempatan', 'Panjang per Batang (cm)'],
            ['PIPA-01', 'Pipa Uji', 'Umum', 'Batang', 'Rak A', '600'],
            ['PIPA-02', 'Pipa Lain', 'Jenis Belum Ada', 'Batang', 'Rak B', '400'],
        ]);

        try {
            DB::transaction(fn () => (new BahanImport)->prosesBaris($rows));
            $this->fail('Import seharusnya gagal karena jenis bahannya belum terdaftar.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Jenis Bahan', $e->getMessage());
        }

        $this->assertNull($pipa->refresh()->panjang_standar);
        $this->assertEquals(10, $lot->refresh()->qty);
        $this->assertNull($lot->panjang_standar);
    }
}
