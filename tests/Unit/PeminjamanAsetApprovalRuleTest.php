<?php

namespace Tests\Unit;

use App\Models\PeminjamanAset;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Aturan "siapa boleh memutus tahap approval apa".
 *
 * Aturan ini dulu hanya hidup di Blade, sementara controller cuma memeriksa
 * permission — sehingga pemegang approve-leader bisa mengirim POST untuk
 * pengajuan siapa pun, lintas divisi, termasuk pengajuannya sendiri. Setelah
 * dipindah ke model, tes ini yang menjaga keduanya tidak melenceng lagi.
 *
 * Sengaja tanpa database: phpunit.xml pada proyek ini masih mengarah ke koneksi
 * MySQL yang sama dengan pengembangan (baris DB_CONNECTION sqlite masih
 * dikomentari), jadi RefreshDatabase akan menghapus data kerja. Ketiga method
 * yang diuji murni membaca atribut & relasi, jadi cukup model yang belum
 * tersimpan.
 */
class PeminjamanAsetApprovalRuleTest extends TestCase
{
    /**
     * User tanpa database. hasRole() ditimpa supaya tidak menyentuh tabel
     * permission Spatie — signature-nya mengikuti HasRoles::hasRole().
     */
    private function user(int $id, array $atribut = [], bool $superadmin = false): User
    {
        $user = new class extends User {
            public bool $superadminPalsu = false;

            public function hasRole($roles, ?string $guard = null): bool
            {
                return $this->superadminPalsu && $roles === 'superadmin';
            }
        };

        $user->superadminPalsu = $superadmin;
        $user->id = $id;
        $user->atasan_level3_id = $atribut['atasan_level3_id'] ?? null;
        $user->atasan_level2_id = $atribut['atasan_level2_id'] ?? null;

        return $user;
    }

    private function pengajuan(User $pengaju, array $status = []): PeminjamanAset
    {
        $peminjaman = new PeminjamanAset(array_merge([
            'status_leader' => 'Belum disetujui',
            'status_manager' => 'Belum disetujui',
            'status' => 'Belum disetujui',
            'status_hrd' => 'Belum disetujui',
        ], $status));

        $peminjaman->setRelation('dataUser', $pengaju);

        return $peminjaman;
    }

    // ---------------- garis komando ----------------

    #[Test]
    public function atasan_level3_boleh_memutus_tahap_leader(): void
    {
        $pengaju = $this->user(10, ['atasan_level3_id' => 7, 'atasan_level2_id' => 9]);
        $atasan = $this->user(7);

        $this->assertTrue($this->pengajuan($pengaju)->beradaDiGarisKomando($atasan, 'leader'));
    }

    #[Test]
    public function pemegang_izin_di_luar_garis_komando_tidak_boleh_memutus_tahap_leader(): void
    {
        $pengaju = $this->user(10, ['atasan_level3_id' => 7, 'atasan_level2_id' => 9]);
        // Punya permission approve-leader, tapi bukan atasan siapa pun di sini.
        $orangLain = $this->user(99);

        $this->assertFalse($this->pengajuan($pengaju)->beradaDiGarisKomando($orangLain, 'leader'));
    }

    #[Test]
    public function pengaju_tidak_boleh_menyetujui_pengajuannya_sendiri(): void
    {
        $pengaju = $this->user(10, ['atasan_level3_id' => 7, 'atasan_level2_id' => 9]);

        $peminjaman = $this->pengajuan($pengaju);

        $this->assertFalse($peminjaman->beradaDiGarisKomando($pengaju, 'leader'));
        $this->assertFalse($peminjaman->beradaDiGarisKomando($pengaju, 'manager'));
    }

    #[Test]
    public function tanpa_atasan_level3_maka_level2_merangkap_tahap_leader(): void
    {
        $pengaju = $this->user(10, ['atasan_level3_id' => null, 'atasan_level2_id' => 9]);
        $manager = $this->user(9);

        $this->assertTrue($this->pengajuan($pengaju)->beradaDiGarisKomando($manager, 'leader'));
    }

    #[Test]
    public function level2_bukan_atasan_tidak_merangkap_tahap_leader(): void
    {
        $pengaju = $this->user(10, ['atasan_level3_id' => null, 'atasan_level2_id' => 9]);
        $bukanAtasan = $this->user(8);

        $this->assertFalse($this->pengajuan($pengaju)->beradaDiGarisKomando($bukanAtasan, 'leader'));
    }

    #[Test]
    public function tahap_manager_terbuka_kalau_pengaju_tidak_punya_atasan_level2(): void
    {
        $pengaju = $this->user(10, ['atasan_level3_id' => 7, 'atasan_level2_id' => null]);
        $siapaPun = $this->user(99);

        $this->assertTrue($this->pengajuan($pengaju)->beradaDiGarisKomando($siapaPun, 'manager'));
    }

    #[Test]
    public function ga_dan_hrd_tidak_dibatasi_garis_komando(): void
    {
        $pengaju = $this->user(10, ['atasan_level3_id' => 7, 'atasan_level2_id' => 9]);
        $peminjaman = $this->pengajuan($pengaju);
        $petugas = $this->user(99);

        $this->assertTrue($peminjaman->beradaDiGarisKomando($petugas, 'ga'));
        $this->assertTrue($peminjaman->beradaDiGarisKomando($petugas, 'hrd'));
    }

    #[Test]
    public function superadmin_boleh_menggantikan_approver_mana_pun(): void
    {
        $pengaju = $this->user(10, ['atasan_level3_id' => 7, 'atasan_level2_id' => 9]);
        $superadmin = $this->user(1, [], true);

        $peminjaman = $this->pengajuan($pengaju);

        foreach (['leader', 'manager', 'ga', 'hrd'] as $tahap) {
            $this->assertTrue($peminjaman->beradaDiGarisKomando($superadmin, $tahap), $tahap);
        }
    }

    #[Test]
    public function tanpa_user_atau_tahap_tak_dikenal_selalu_ditolak(): void
    {
        $peminjaman = $this->pengajuan($this->user(10, ['atasan_level3_id' => 7]));

        $this->assertFalse($peminjaman->beradaDiGarisKomando(null, 'leader'));
        $this->assertFalse($peminjaman->beradaDiGarisKomando($this->user(7), 'tahap-karangan'));
    }

    // ---------------- keputusan tidak boleh ditimpa ----------------

    #[Test]
    public function tahap_yang_sudah_diputus_tidak_boleh_diputus_lagi(): void
    {
        $pengaju = $this->user(10);

        foreach (['Disetujui', 'Ditolak'] as $keputusan) {
            $peminjaman = $this->pengajuan($pengaju, ['status_leader' => $keputusan]);
            $this->assertFalse($peminjaman->tahapBelumDiputus('leader'), $keputusan);
        }
    }

    #[Test]
    public function belum_disetujui_bukan_keputusan_sehingga_masih_boleh_diputus(): void
    {
        // "Belum disetujui" juga dipakai approver yang hanya mencatat kendala.
        $peminjaman = $this->pengajuan($this->user(10), ['status_leader' => 'Belum disetujui']);

        $this->assertTrue($peminjaman->tahapBelumDiputus('leader'));
    }

    #[Test]
    public function tahap_tak_dikenal_dianggap_tidak_boleh_diputus(): void
    {
        $this->assertFalse($this->pengajuan($this->user(10))->tahapBelumDiputus('tahap-karangan'));
    }

    // ---------------- urutan tahap ----------------

    #[Test]
    public function leader_adalah_tahap_pertama_sehingga_tidak_menunggu_siapa_pun(): void
    {
        $this->assertTrue($this->pengajuan($this->user(10))->tahapSebelumnyaSudahDisetujui('leader'));
    }

    #[Test]
    public function tahap_lanjutan_menunggu_seluruh_tahap_sebelumnya(): void
    {
        $pengaju = $this->user(10);

        $kosong = $this->pengajuan($pengaju);
        $this->assertFalse($kosong->tahapSebelumnyaSudahDisetujui('manager'));
        $this->assertFalse($kosong->tahapSebelumnyaSudahDisetujui('ga'));
        $this->assertFalse($kosong->tahapSebelumnyaSudahDisetujui('hrd'));

        $leaderSaja = $this->pengajuan($pengaju, ['status_leader' => 'Disetujui']);
        $this->assertTrue($leaderSaja->tahapSebelumnyaSudahDisetujui('manager'));
        $this->assertFalse($leaderSaja->tahapSebelumnyaSudahDisetujui('ga'));

        $sampaiManager = $this->pengajuan($pengaju, [
            'status_leader' => 'Disetujui',
            'status_manager' => 'Disetujui',
        ]);
        $this->assertTrue($sampaiManager->tahapSebelumnyaSudahDisetujui('ga'));
        $this->assertFalse($sampaiManager->tahapSebelumnyaSudahDisetujui('hrd'));

        $sampaiGa = $this->pengajuan($pengaju, [
            'status_leader' => 'Disetujui',
            'status_manager' => 'Disetujui',
            'status' => 'Disetujui',
        ]);
        $this->assertTrue($sampaiGa->tahapSebelumnyaSudahDisetujui('hrd'));
    }

    #[Test]
    public function penolakan_di_tahap_awal_menutup_tahap_sesudahnya(): void
    {
        $ditolak = $this->pengajuan($this->user(10), ['status_leader' => 'Ditolak']);

        $this->assertFalse($ditolak->tahapSebelumnyaSudahDisetujui('manager'));
        $this->assertFalse($ditolak->tahapSebelumnyaSudahDisetujui('ga'));
        $this->assertFalse($ditolak->tahapSebelumnyaSudahDisetujui('hrd'));
    }
}
