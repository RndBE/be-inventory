<?php

namespace Tests\Unit;

use App\Models\PenunjukanPerbaikanData;
use App\Models\User;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Gerbang siapa yang boleh mengisi bagian pelaksanaan surat penunjukan.
 *
 * Yang diuji di sini satu-satunya pemeriksaan hak pada endpoint pelaksanaan:
 * route-nya sengaja tidak dipasangi middleware permission, karena pelaksana
 * yang namanya tertulis di surat harus bisa mengisinya tanpa lebih dulu diberi
 * permission satu per satu. Kalau pemeriksaan ini bocor, siapa pun yang bisa
 * membuka halaman detail bisa menyatakan pekerjaan orang lain selesai.
 *
 * Tidak menyentuh database: modelnya diisi lewat setRawAttributes, dan User-nya
 * mock. Riwayat migration proyek ini belum bisa jalan dari database kosong,
 * jadi test yang butuh tabel akan gagal karena hal yang tidak diujinya.
 */
class PenunjukanPerbaikanDataTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function penunjukan(int $ditunjukUserId): PenunjukanPerbaikanData
    {
        $penunjukan = new PenunjukanPerbaikanData;
        $penunjukan->setRawAttributes([
            'id' => 1,
            'ditunjuk_user_id' => $ditunjukUserId,
        ], true);

        return $penunjukan;
    }

    private function user(int $id, bool $punyaIzin): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = $id;
        $user->shouldReceive('can')
            ->with('isi-pelaksanaan-perbaikan-data')
            ->andReturn($punyaIzin);

        return $user;
    }

    #[Test]
    public function pelaksana_yang_ditunjuk_boleh_mengisi_tanpa_permission(): void
    {
        $this->assertTrue(
            $this->penunjukan(7)->bolehDiisiOleh($this->user(7, false))
        );
    }

    #[Test]
    public function orang_lain_tanpa_permission_tidak_boleh_mengisi(): void
    {
        $this->assertFalse(
            $this->penunjukan(7)->bolehDiisiOleh($this->user(9, false))
        );
    }

    #[Test]
    public function orang_lain_dengan_permission_boleh_mengisi(): void
    {
        $this->assertTrue(
            $this->penunjukan(7)->bolehDiisiOleh($this->user(9, true))
        );
    }

    #[Test]
    public function tanpa_user_yang_login_tidak_boleh_mengisi(): void
    {
        $this->assertFalse($this->penunjukan(7)->bolehDiisiOleh(null));
    }

    #[Test]
    public function pelaksanaan_dianggap_belum_diisi_selama_tanggalnya_kosong(): void
    {
        $penunjukan = new PenunjukanPerbaikanData;

        // Status sengaja sudah terisi tanpa tanggal: status bisa dipindah lebih
        // dulu, dan yang menentukan surat ini sudah punya jawaban adalah adanya
        // tanggal pelaksanaan.
        $penunjukan->setRawAttributes([
            'status' => 'Selesai Sebagian',
            'tgl_pelaksanaan' => null,
        ], true);

        $this->assertFalse($penunjukan->sudahDilaksanakan());

        $penunjukan->setRawAttributes([
            'status' => 'Selesai Sebagian',
            'tgl_pelaksanaan' => '2026-09-02 10:00:00',
        ], true);

        $this->assertTrue($penunjukan->sudahDilaksanakan());
    }

    /**
     * Ubah dan hapus tidak lagi bergantung status — tidak ada gerbangnya.
     *
     * Test ini dulu menjaga `masihBisaDiubah()`, yang menutup penyuntingan
     * begitu pelaksanaannya diisi. Yang terlewat waktu itu: unggahan surat
     * bertanda tangan lewat form Ubah yang sama, dan kertas sering baru kembali
     * dari meja tanda tangan setelah softwarenya selesai mengerjakan. Jendelanya
     * tertutup justru sebelum berkas paling penting sempat masuk.
     *
     * Yang tersisa dijaga: `sudahDilaksanakan()`, yang masih dipakai untuk
     * membedakan surat yang sudah dijawab pelaksananya — diuji test di atas.
     */
    #[Test]
    public function tidak_ada_lagi_gerbang_status_untuk_ubah_dan_hapus(): void
    {
        $this->assertFalse(
            method_exists(PenunjukanPerbaikanData::class, 'masihBisaDiubah'),
            'masihBisaDiubah() dihidupkan lagi. Kalau gerbangnya memang dibutuhkan, '
            . 'pastikan unggahan surat bertanda tangan punya jalur lain yang tidak ikut tertutup.'
        );
    }

    /**
     * Daftar status harus datang dari config yang sama dengan kotak centang di
     * PDF-nya. Kalau keduanya berbeda, akan ada surat berstatus yang tidak punya
     * kotak untuk dicentang.
     */
    #[Test]
    public function pilihan_status_mengikuti_kotak_centang_di_surat(): void
    {
        $this->assertSame(
            config('surat_penunjukan.konfirmasi.status'),
            PenunjukanPerbaikanData::pilihanStatus()
        );

        // Status awal bukan salah satu kotak centang: surat yang baru terbit
        // belum punya jawaban.
        $this->assertNotContains(
            PenunjukanPerbaikanData::STATUS_AWAL,
            PenunjukanPerbaikanData::pilihanStatus()
        );
    }

    #[Test]
    public function nomor_surat_dipakai_lebih_dulu_lalu_kode_internalnya(): void
    {
        $penunjukan = new PenunjukanPerbaikanData;

        $penunjukan->setRawAttributes([
            'kode_penunjukan' => 'PN-20260902153000-AB12',
            'nomor_surat' => '008/ACC-PD/IX/2026',
        ], true);
        $this->assertSame('008/ACC-PD/IX/2026', $penunjukan->nomorSuratCetak());

        // Baris lama yang dibuat sebelum kolom nomor surat ada tetap punya
        // pengenal untuk dicetak, bukan "-".
        $penunjukan->setRawAttributes([
            'kode_penunjukan' => 'PN-20260902153000-AB12',
            'nomor_surat' => null,
        ], true);
        $this->assertSame('PN-20260902153000-AB12', $penunjukan->nomorSuratCetak());
    }

    #[Test]
    public function tim_pemohon_jatuh_ke_default_config_kalau_kosong(): void
    {
        $penunjukan = new PenunjukanPerbaikanData;

        $penunjukan->setRawAttributes(['tim_pemohon' => 'Tim Purchasing'], true);
        $this->assertSame('Tim Purchasing', $penunjukan->timPemohon());

        $penunjukan->setRawAttributes(['tim_pemohon' => null], true);
        $this->assertSame(config('surat_penunjukan.tim_pemohon_default'), $penunjukan->timPemohon());
    }
}
