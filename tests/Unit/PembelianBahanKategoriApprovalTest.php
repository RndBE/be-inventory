<?php

namespace Tests\Unit;

use App\Models\PembelianBahan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Toggle kategori pada pengajuan Pembelian Bahan/Barang/Alat (Lokal & Impor):
 * 'Produksi' memakai approval atasan level 3 (Leader) lalu tahap-tahap
 * berikutnya, 'Riset' melewati atasan level 3 — slot Leader diputus Manager
 * (atasan level 2). Tidak ada tahap yang di-auto-approve selama approver-nya ada.
 *
 * Kategori masih boleh dipindah sampai Purchasing memutus.
 *
 * Sengaja tanpa database: phpunit.xml proyek ini masih mengarah ke koneksi MySQL
 * yang sama dengan pengembangan (baris DB_CONNECTION sqlite masih dikomentari),
 * jadi RefreshDatabase akan menghapus data kerja. Yang diuji murni perhitungan
 * atribut, jadi cukup model yang belum tersimpan.
 */
class PembelianBahanKategoriApprovalTest extends TestCase
{
    #[Test]
    public function produksi_diputus_atasan_level3(): void
    {
        $this->assertSame(7, PembelianBahan::approverLeaderId(PembelianBahan::KATEGORI_PRODUKSI, 7, 3));
        $this->assertSame(
            'Belum disetujui',
            PembelianBahan::statusLeaderAwal(PembelianBahan::KATEGORI_PRODUKSI, 7, 3)
        );
    }

    #[Test]
    public function produksi_tanpa_atasan_level3_jatuh_ke_manager(): void
    {
        $this->assertSame(3, PembelianBahan::approverLeaderId(PembelianBahan::KATEGORI_PRODUKSI, null, 3));
    }

    #[Test]
    public function riset_diputus_manager_meski_atasan_level3_ada(): void
    {
        $this->assertSame(3, PembelianBahan::approverLeaderId(PembelianBahan::KATEGORI_RISET, 7, 3));
        $this->assertSame(
            'Belum disetujui',
            PembelianBahan::statusLeaderAwal(PembelianBahan::KATEGORI_RISET, 7, 3)
        );
    }

    #[Test]
    public function tanpa_approver_slot_leader_otomatis_disetujui(): void
    {
        // Riset: atasan level 3 dilewati, jadi tanpa atasan level 2 tidak ada
        // yang bisa memutus.
        $this->assertNull(PembelianBahan::approverLeaderId(PembelianBahan::KATEGORI_RISET, 7, null));
        $this->assertSame(
            'Disetujui',
            PembelianBahan::statusLeaderAwal(PembelianBahan::KATEGORI_RISET, 7, null)
        );
        $this->assertSame(
            'Disetujui',
            PembelianBahan::statusLeaderAwal(PembelianBahan::KATEGORI_PRODUKSI, null, null)
        );
    }

    #[Test]
    public function hanya_riset_yang_memindah_slot_leader_ke_manager(): void
    {
        $riset = $this->pengajuan('Pembelian Bahan/Barang/Alat Lokal', [
            'kategori_pengajuan' => PembelianBahan::KATEGORI_RISET,
        ]);
        $produksi = $this->pengajuan('Pembelian Bahan/Barang/Alat Impor|USD');
        $asetRiset = $this->pengajuan('Pembelian Aset Lokal', [
            'kategori_pengajuan' => PembelianBahan::KATEGORI_RISET,
        ]);

        $this->assertTrue($riset->leaderDiputusManager());
        $this->assertFalse($produksi->leaderDiputusManager());
        $this->assertFalse($asetRiset->leaderDiputusManager());
    }

    #[Test]
    public function kategori_hanya_dipakai_jenis_bahan_barang_alat(): void
    {
        $this->assertTrue($this->pengajuan('Pembelian Bahan/Barang/Alat Lokal')->pakaiKategoriPengajuan());
        $this->assertTrue($this->pengajuan('Pembelian Bahan/Barang/Alat Impor|USD')->pakaiKategoriPengajuan());
        $this->assertFalse($this->pengajuan('Pembelian Aset Lokal')->pakaiKategoriPengajuan());
        $this->assertFalse($this->pengajuan('Pembelian Aset Impor|EUR')->pakaiKategoriPengajuan());
    }

    #[Test]
    public function kategori_bisa_diubah_sampai_purchasing_memutus(): void
    {
        $this->assertTrue($this->pengajuan('Pembelian Bahan/Barang/Alat Lokal')->kategoriMasihBisaDiubah());

        // Slot Leader sudah disetujui, Purchasing belum: masih boleh pindah.
        $this->assertTrue(
            $this->pengajuan('Pembelian Bahan/Barang/Alat Lokal', [
                'status_leader' => 'Disetujui',
            ])->kategoriMasihBisaDiubah()
        );

        // Kolom status_purchasing masih null pada data lama.
        $this->assertTrue(
            $this->pengajuan('Pembelian Bahan/Barang/Alat Lokal', [
                'status_purchasing' => null,
            ])->kategoriMasihBisaDiubah()
        );
    }

    #[Test]
    public function kategori_terkunci_setelah_purchasing_atau_penolakan(): void
    {
        $purchasingSetuju = $this->pengajuan('Pembelian Bahan/Barang/Alat Lokal', [
            'status_leader' => 'Disetujui',
            'status_purchasing' => 'Disetujui',
        ]);
        $purchasingTolak = $this->pengajuan('Pembelian Bahan/Barang/Alat Lokal', [
            'status_purchasing' => 'Ditolak',
        ]);
        $ditolakLeader = $this->pengajuan('Pembelian Bahan/Barang/Alat Lokal', [
            'status_leader' => 'Ditolak',
            'status' => 'Ditolak',
        ]);
        $ditolakTahapLain = $this->pengajuan('Pembelian Bahan/Barang/Alat Lokal', [
            'status_leader' => 'Disetujui',
            'status' => 'Ditolak',
        ]);

        $this->assertFalse($purchasingSetuju->kategoriMasihBisaDiubah());
        $this->assertFalse($purchasingTolak->kategoriMasihBisaDiubah());
        $this->assertFalse($ditolakLeader->kategoriMasihBisaDiubah());
        $this->assertFalse($ditolakTahapLain->kategoriMasihBisaDiubah());
        $this->assertFalse($this->pengajuan('Pembelian Aset Lokal')->kategoriMasihBisaDiubah());
    }

    private function pengajuan(string $jenisPengajuan, array $status = []): PembelianBahan
    {
        return new PembelianBahan(array_merge([
            'jenis_pengajuan' => $jenisPengajuan,
            'kategori_pengajuan' => PembelianBahan::KATEGORI_PRODUKSI,
            'status_leader' => 'Belum disetujui',
            'status_purchasing' => 'Belum disetujui',
            'status_manager' => 'Belum disetujui',
            'status' => 'Belum disetujui',
        ], $status));
    }
}
