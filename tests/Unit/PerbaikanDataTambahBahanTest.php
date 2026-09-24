<?php

namespace Tests\Unit;

use App\Exceptions\PerbaikanDataDitolak;
use App\Models\AuditPerubahanData;
use App\Services\PerbaikanDataService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Kolom "Tambah Bahan": bahan yang lupa diajukan dicatat pada record induknya
 * dengan nilai lama kosong. Baris detailnya tetap di-insert tim software.
 */
class PerbaikanDataTambahBahanTest extends TestCase
{
    private PerbaikanDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PerbaikanDataService();

        Schema::create('uji_induk', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('kode_transaksi')->nullable();
            $tabel->timestamps();
        });

        Schema::create('uji_detail', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->unsignedBigInteger('uji_induk_id');
            $tabel->unsignedBigInteger('bahan_id');
            $tabel->timestamps();
        });

        Schema::create('unit', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nama');
            $tabel->timestamps();
        });

        Schema::create('bahan', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('kode_bahan');
            $tabel->string('nama_bahan');
            $tabel->unsignedBigInteger('unit_id')->nullable();
            $tabel->timestamps();
        });

        Schema::create('audit_perubahan_data', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->unsignedBigInteger('perbaikan_data_id')->nullable();
            $tabel->string('modul');
            $tabel->unsignedBigInteger('modul_id');
            $tabel->string('tabel_target')->nullable();
            $tabel->unsignedBigInteger('baris_target_id')->nullable();
            $tabel->string('field');
            $tabel->text('nilai_lama')->nullable();
            $tabel->text('nilai_baru')->nullable();
            $tabel->text('alasan');
            $tabel->unsignedBigInteger('pengaju_id')->nullable();
            $tabel->unsignedBigInteger('approver_id')->nullable();
            $tabel->boolean('disetujui_sendiri')->default(false);
            $tabel->string('ip_address')->nullable();
            $tabel->timestamp('created_at')->nullable();
        });

        DB::table('unit')->insert(['id' => 1, 'nama' => 'Pcs']);
        DB::table('bahan')->insert([
            ['id' => 189, 'kode_bahan' => 'BUZ-001', 'nama_bahan' => 'Buzzer 5V', 'unit_id' => 1],
            ['id' => 1769, 'kode_bahan' => 'RND - 106', 'nama_bahan' => '9.5*5 09 side passive buzzer', 'unit_id' => 1],
        ]);
        DB::table('uji_induk')->insert(['id' => 1, 'kode_transaksi' => 'PRD-UJI']);
        DB::table('uji_detail')->insert(['uji_induk_id' => 1, 'bahan_id' => 1769]);

        config()->set('perbaikan_data.modul.uji_induk', [
            'label' => 'Uji Produksi',
            'model' => ModelUjiIndukTambahBahan::class,
            'kode' => 'kode_transaksi',
            'jenis' => ['Produksi Produk Setengah Jadi'],
            'field' => [
                'kode_transaksi' => ['label' => 'Kode', 'tipe' => 'string'],
                'tambah_bahan' => [
                    'label' => 'Tambah Bahan',
                    'tipe' => 'tambah_bahan',
                    'detail' => 'detail',
                    'wajib_lampiran' => true,
                ],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['uji_induk', 'uji_detail', 'unit', 'bahan', 'audit_perubahan_data'] as $tabel) {
            Schema::dropIfExists($tabel);
        }

        parent::tearDown();
    }

    #[Test]
    public function kiriman_form_dibakukan_jadi_teks_terbaca(): void
    {
        $baku = $this->service->periksaTambahBahan('uji_induk', 1, 'tambah_bahan', json_encode(['bahan_id' => 189, 'qty' => '2,5']));

        $this->assertSame('Buzzer 5V [BUZ-001] × 2.5 Pcs', $baku);
        $this->assertSame('BUZ-001', $this->service->kodeBahanTambahan($baku));
    }

    #[Test]
    public function bentuk_baku_bisa_dibaca_ulang_tanpa_berubah(): void
    {
        $baku = 'Buzzer 5V [BUZ-001] × 5 Pcs';

        $this->assertSame($baku, $this->service->periksaTambahBahan('uji_induk', 1, 'tambah_bahan', $baku));
    }

    #[Test]
    public function bahan_yang_sudah_ada_di_transaksi_ditolak(): void
    {
        $this->expectException(PerbaikanDataDitolak::class);
        $this->expectExceptionMessage('sudah ada di PRD-UJI');

        $this->service->periksaTambahBahan('uji_induk', 1, 'tambah_bahan', json_encode(['bahan_id' => 1769, 'qty' => 5]));
    }

    #[Test]
    public function bahan_di_luar_master_ditolak(): void
    {
        $this->expectException(PerbaikanDataDitolak::class);

        $this->service->periksaTambahBahan('uji_induk', 1, 'tambah_bahan', json_encode(['bahan_id' => 999, 'qty' => 5]));
    }

    #[Test]
    public function jumlah_nol_ditolak(): void
    {
        $this->expectException(PerbaikanDataDitolak::class);

        $this->service->periksaTambahBahan('uji_induk', 1, 'tambah_bahan', json_encode(['bahan_id' => 189, 'qty' => 0]));
    }

    #[Test]
    public function nilai_lama_tambah_bahan_selalu_kosong(): void
    {
        $this->assertNull($this->service->nilaiSekarang('uji_induk', 1, 'tambah_bahan'));
    }

    #[Test]
    public function pencatatan_menunjuk_tabel_detail_dan_tidak_menulis_baris_bahan(): void
    {
        $audit = $this->service->terapkan([
            'modul' => 'uji_induk',
            'modul_id' => 1,
            'field' => 'tambah_bahan',
            'nilai_lama' => null,
            'nilai_baru' => 'Buzzer 5V [BUZ-001] × 5 Pcs',
            'alasan' => 'Bahan lupa diajukan',
        ]);

        $this->assertInstanceOf(AuditPerubahanData::class, $audit);
        $this->assertNull($audit->nilai_lama);
        $this->assertSame('Buzzer 5V [BUZ-001] × 5 Pcs', $audit->nilai_baru);
        $this->assertSame('uji_detail', $audit->tabel_target);
        $this->assertNull($audit->baris_target_id);
        $this->assertSame(1, DB::table('uji_detail')->count());
    }

    #[Test]
    public function pencatatan_ditolak_kalau_bahannya_keburu_dimasukkan(): void
    {
        DB::table('uji_detail')->insert(['uji_induk_id' => 1, 'bahan_id' => 189]);

        $this->expectException(PerbaikanDataDitolak::class);

        $this->service->terapkan([
            'modul' => 'uji_induk',
            'modul_id' => 1,
            'field' => 'tambah_bahan',
            'nilai_lama' => null,
            'nilai_baru' => 'Buzzer 5V [BUZ-001] × 5 Pcs',
            'alasan' => 'Bahan lupa diajukan',
        ]);
    }

    #[Test]
    public function katalog_kolom_membawa_tipe_untuk_form(): void
    {
        $kolom = collect($this->service->katalogKolom())->firstWhere('nilai', 'uji_induk::tambah_bahan');

        $this->assertSame('tambah_bahan', $kolom['tipe']);
        $this->assertSame('Uji Produksi — Tambah Bahan', $kolom['label']);
    }
}

class ModelUjiIndukTambahBahan extends Model
{
    protected $table = 'uji_induk';

    protected $guarded = [];

    public function detail()
    {
        return $this->hasMany(ModelUjiDetailTambahBahan::class, 'uji_induk_id');
    }
}

class ModelUjiDetailTambahBahan extends Model
{
    protected $table = 'uji_detail';

    protected $guarded = [];
}
