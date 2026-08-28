<?php

namespace Tests\Unit;

use App\Helpers\SatuanBahanHelper;
use App\Models\Bahan;
use Tests\TestCase;

/**
 * Aritmatika satuan batang/cm.
 *
 * Semua kasus di sini murni angka dan tidak menyentuh database, jadi aman
 * dijalankan di working directory yang .env-nya menunjuk database produksi.
 *
 * Pipa 600 cm dengan harga Rp 175.000 per batang dipakai sebagai contoh tetap
 * supaya angka harapannya bisa dilacak balik dengan hitungan tangan.
 */
class SatuanBahanHelperTest extends TestCase
{
    private const PANJANG = 600;

    private const HARGA_PER_BATANG = 175000;

    public function test_qty_batang_dikali_panjang_standar(): void
    {
        $this->assertSame(3000.0, SatuanBahanHelper::keSatuanDasar(5, 'batang', self::PANJANG));
    }

    public function test_qty_cm_tidak_ikut_dikali(): void
    {
        $this->assertSame(5.0, SatuanBahanHelper::keSatuanDasar(5, 'cm', self::PANJANG));
    }

    /**
     * Regresi paling penting: 2077 bahan non-batangan tidak boleh berubah.
     */
    public function test_bahan_tanpa_panjang_standar_diteruskan_apa_adanya(): void
    {
        $this->assertSame(5.0, SatuanBahanHelper::keSatuanDasar(5, 'batang', null));
        $this->assertSame(5.0, SatuanBahanHelper::keSatuanDasar(5, null, null));
        $this->assertSame(175000.0, SatuanBahanHelper::keHargaSatuanDasar(self::HARGA_PER_BATANG, 'batang', null));
        $this->assertTrue(SatuanBahanHelper::kelipatanBatang(7.5, null));
        $this->assertSame([], SatuanBahanHelper::pilihanSatuan(null));
    }

    public function test_harga_per_batang_jadi_harga_per_cm(): void
    {
        $this->assertSame(291.6667, SatuanBahanHelper::keHargaSatuanDasar(self::HARGA_PER_BATANG, 'batang', self::PANJANG));
    }

    public function test_harga_yang_diketik_per_cm_tidak_dibagi_lagi(): void
    {
        $this->assertSame(300.0, SatuanBahanHelper::keHargaSatuanDasar(300, 'cm', self::PANJANG));
    }

    /**
     * Subtotal dihitung dari angka yang diketik user supaya eksak. Kalau
     * dihitung dari harga per cm hasilnya Rp 875.000,10.
     */
    public function test_subtotal_eksak_dari_angka_input(): void
    {
        $this->assertSame(875000.0, SatuanBahanHelper::subTotal(5, self::HARGA_PER_BATANG));
        $this->assertNotSame(875000.0, SatuanBahanHelper::nilaiSatuanDasar(3000, 291.6667));
    }

    public function test_nilai_pengambilan_sebagian_dari_harga_per_cm(): void
    {
        $this->assertSame(11666.67, SatuanBahanHelper::nilaiSatuanDasar(40, 291.6667));
    }

    /**
     * Bolak-balik harga tidak pulih sempurna karena penyimpanannya dibulatkan
     * ke 4 desimal. Selisihnya Rp 0,02 per batang dan itu memang disengaja.
     */
    public function test_harga_bolak_balik_meleset_dua_sen(): void
    {
        $perCm = SatuanBahanHelper::keHargaSatuanDasar(self::HARGA_PER_BATANG, 'batang', self::PANJANG);

        $this->assertSame(175000.02, SatuanBahanHelper::dariHargaSatuanDasar($perCm, 'batang', self::PANJANG));
    }

    public function test_qty_bolak_balik_pulih_utuh(): void
    {
        $this->assertSame(5.0, SatuanBahanHelper::dariSatuanDasar(3000, 'batang', self::PANJANG));
    }

    public function test_format_gabungan_batang_dan_sisa_cm(): void
    {
        $this->assertSame('6 Batang + 40 cm', SatuanBahanHelper::format(3640, self::PANJANG, 'Batang'));
    }

    public function test_format_pas_batang_tidak_menulis_sisa(): void
    {
        $this->assertSame('5 Batang', SatuanBahanHelper::format(3000, self::PANJANG, 'Batang'));
    }

    public function test_format_di_bawah_satu_batang(): void
    {
        $this->assertSame('40 cm', SatuanBahanHelper::format(40, self::PANJANG, 'Batang'));
    }

    public function test_format_bahan_biasa_seperti_sebelumnya(): void
    {
        $this->assertSame('12 Pcs', SatuanBahanHelper::format(12, null, 'Pcs'));
    }

    public function test_format_memakai_nama_unit_bahan(): void
    {
        $this->assertSame('2 Lonjor', SatuanBahanHelper::format(1200, self::PANJANG, 'Lonjor'));
    }

    public function test_pecah_membagi_batang_utuh_dan_sisa(): void
    {
        $this->assertSame(['batang' => 6, 'sisa' => 40.0], SatuanBahanHelper::pecah(3640, self::PANJANG));
    }

    /**
     * Kolom qty bertipe decimal, jadi pembandingan "pas satu batang" tidak
     * boleh memakai == mentah.
     */
    public function test_pecah_toleran_terhadap_pecahan_desimal(): void
    {
        $this->assertSame(5, SatuanBahanHelper::pecah(2999.99995, self::PANJANG)['batang']);
    }

    public function test_kelipatan_batang_untuk_validasi_retur(): void
    {
        $this->assertTrue(SatuanBahanHelper::kelipatanBatang(3000, self::PANJANG));
        $this->assertFalse(SatuanBahanHelper::kelipatanBatang(3640, self::PANJANG));
    }

    /**
     * Alur lama tidak mengirim satuan sama sekali, dan angkanya di sana memang
     * sudah dalam satuan dasar.
     */
    public function test_satuan_kosong_dianggap_satuan_dasar(): void
    {
        $this->assertSame('cm', SatuanBahanHelper::normalkanSatuan(null));
        $this->assertSame('cm', SatuanBahanHelper::normalkanSatuan(''));
        $this->assertSame('cm', SatuanBahanHelper::normalkanSatuan('lonjor'));
        $this->assertSame('batang', SatuanBahanHelper::normalkanSatuan('BATANG'));
        $this->assertSame('batang', SatuanBahanHelper::normalkanSatuan(' batang '));
    }

    public function test_panjang_standar_menerima_model_array_dan_angka(): void
    {
        $bahan = new Bahan(['panjang_standar' => self::PANJANG]);

        $this->assertSame(self::PANJANG, SatuanBahanHelper::panjangStandar($bahan));
        $this->assertSame(self::PANJANG, SatuanBahanHelper::panjangStandar(['panjang_standar' => self::PANJANG]));
        $this->assertSame(self::PANJANG, SatuanBahanHelper::panjangStandar(self::PANJANG));
        $this->assertNull(SatuanBahanHelper::panjangStandar(new Bahan()));
        $this->assertNull(SatuanBahanHelper::panjangStandar(0));
        $this->assertNull(SatuanBahanHelper::panjangStandar(''));
    }

    public function test_dropdown_hanya_muncul_untuk_bahan_batangan(): void
    {
        $this->assertSame(
            ['batang' => 'Lonjor', 'cm' => 'cm'],
            SatuanBahanHelper::pilihanSatuan(self::PANJANG, 'Lonjor')
        );
        $this->assertSame(['batang' => 'Batang', 'cm' => 'cm'], SatuanBahanHelper::pilihanSatuan(self::PANJANG));
    }

    public function test_ke_batang_boleh_pecahan(): void
    {
        $this->assertSame(6.0, SatuanBahanHelper::keBatang(3600, self::PANJANG));
        $this->assertSame(0.5, SatuanBahanHelper::keBatang(300, self::PANJANG));
    }
}
