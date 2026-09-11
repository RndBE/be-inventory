<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Nama & tanda tangan General Affair di PDF dulu tidak pernah disimpan di
 * record, jadi pejabatnya dicari ulang tiap PDF dibuat dan satu kali pergantian
 * GA menulis ulang nama di seluruh dokumen lama.
 *
 * Sekarang approver dibekukan di `pembelian_bahan.ga_id` saat tombol
 * persetujuan ditekan. Resolver berbasis tanggal tetap ada sebagai jaring
 * pengaman untuk baris lama dan pengajuan yang belum sampai tahap GA.
 */
class TandaTanganGeneralAffairPdfTest extends TestCase
{
    private const CONTROLLERS = [
        'Http/Controllers/PengajuanPembelianController.php',
        'Http/Controllers/PembelianBahanController.php',
    ];

    public function test_kedua_controller_punya_resolver_general_affair_sadar_tanggal(): void
    {
        foreach (self::CONTROLLERS as $controller) {
            $source = file_get_contents(app_path($controller));

            $this->assertStringContainsString(
                'private function resolveGeneralAffairUser($tglPengajuan = null)',
                $source,
                "{$controller} belum punya resolver General Affair."
            );

            // Cutoff sama dengan pergantian Purchasing & Finance.
            $this->assertStringContainsString(
                "strtotime((string) \$tglPengajuan) >= strtotime('2026-07-31 00:00:00')",
                $source,
                "{$controller}: cutoff serah terima GA hilang."
            );

            // Dokumen sebelum cutoff tetap atas nama pejabat lama.
            $this->assertStringContainsString(
                "User::where('name', 'WIDYA ANNISA RAHMAWATI')->first()",
                $source,
                "{$controller}: cabang dokumen lama tidak lagi menunjuk GA lama."
            );

            // Dokumen setelah cutoff mengikuti pemegang role yang masih aktif.
            $this->assertStringContainsString("->where('status', 'Aktif')", $source);
            $this->assertStringContainsString("->orderBy('id')", $source);
        }
    }

    public function test_pdf_mengambil_general_affair_lewat_tanggal_pengajuan(): void
    {
        foreach (self::CONTROLLERS as $controller) {
            $source = file_get_contents(app_path($controller));

            $this->assertStringContainsString(
                'resolveGeneralAffairUser($pembelianBahan->tgl_pengajuan ?? null)',
                $source,
                "{$controller}: PDF tidak meneruskan tgl_pengajuan ke resolver."
            );

            // Lookup lama tanpa tanggal: siapa pun pemegang role saat ini, id terkecil menang.
            $this->assertStringNotContainsString(
                "cache()->remember('general_user'",
                $source,
                "{$controller}: lookup GA lama tanpa penanda waktu masih tersisa."
            );
        }
    }

    public function test_notifikasi_general_affair_hanya_ke_pemegang_role_aktif(): void
    {
        $berkas = [
            'Http/Controllers/PengajuanPembelianController.php' => '$generalAffairUser = User::whereHas(',
            'Http/Controllers/PembelianBahanController.php' => '$targetUser = User::whereHas(',
            'Http/Controllers/PeminjamanAsetController.php' => '$target = User::whereHas(',
        ];

        foreach ($berkas as $controller => $penanda) {
            // Baris komentar dibuang dulu: kedua controller masih menyimpan versi
            // lama lookup ini sebagai komentar, dan itu bukan kode yang jalan.
            $baris = preg_grep('/^\s*\/\//', file(app_path($controller)), PREG_GREP_INVERT);
            $source = implode('', $baris);
            $posisi = strpos($source, $penanda);

            $this->assertNotFalse($posisi, "{$controller}: lookup notifikasi GA tidak ditemukan.");

            // Role approval tidak selalu dicabut saat karyawan keluar, dan id
            // pemegang lama lebih kecil - tanpa filter status dia yang menang.
            $potongan = substr($source, $posisi, 300);
            $this->assertStringContainsString("->where('status', 'Aktif')", $potongan, $controller);
            $this->assertStringContainsString("->orderBy('id')", $potongan, $controller);
        }
    }

    public function test_pdf_mendahulukan_approver_yang_dibekukan_di_record(): void
    {
        foreach (self::CONTROLLERS as $controller) {
            $source = file_get_contents(app_path($controller));

            // ga_id menang; resolver tanggal hanya fallback.
            $this->assertStringContainsString(
                '$generalUser = $pembelianBahan->generalAffair',
                $source,
                "{$controller}: PDF tidak membaca approver yang dibekukan."
            );
            $this->assertStringContainsString(
                '?? $this->resolveGeneralAffairUser($pembelianBahan->tgl_pengajuan ?? null);',
                $source,
                "{$controller}: fallback resolver tanggal hilang."
            );
        }

        $model = file_get_contents(app_path('Models/PembelianBahan.php'));
        $this->assertStringContainsString("return \$this->belongsTo(User::class, 'ga_id');", $model);
    }

    public function test_approver_general_affair_dicatat_saat_persetujuan(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/PembelianBahanController.php'));

        // Satu-satunya titik tulis tahap GA.
        $posisi = strpos($source, "\$data->tgl_approve_general_manager = \$tgl_approve_general_manager;");
        $this->assertNotFalse($posisi, 'Titik tulis persetujuan GA tidak ditemukan.');

        $this->assertStringContainsString(
            "\$data->ga_id = Auth::id();",
            substr($source, $posisi, 400),
            'Approver GA tidak ikut disimpan saat persetujuan.'
        );
    }

    public function test_migration_menambah_ga_id_dan_mengisi_data_lama(): void
    {
        $files = glob(database_path('migrations/*_add_ga_id_to_pembelian_bahan_table.php'));
        $this->assertNotEmpty($files, 'Migration ga_id tidak ditemukan.');

        $source = file_get_contents($files[0]);

        $this->assertStringContainsString('add `ga_id` bigint unsigned null', $source);
        $this->assertStringContainsString('drop `ga_id`', $source);

        // Backfill memakai pembagian yang sama dengan resolver, supaya dokumen
        // lama tidak berubah isi saat migration jalan.
        $this->assertStringContainsString("'2026-07-31 00:00:00'", $source);
        $this->assertStringContainsString("'WIDYA ANNISA RAHMAWATI'", $source);
        $this->assertStringContainsString("'AVISSA NOVA FAUZISTIKA'", $source);

        // Baris yang belum melewati tahap GA sengaja dibiarkan null.
        $this->assertStringContainsString("whereNotNull('tgl_approve_general_manager')", $source);
        $this->assertStringContainsString("where('status_general_manager', '!=', 'Belum disetujui')", $source);
    }
}
