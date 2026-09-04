<?php

namespace Tests\Unit;

use App\Models\AuditPerubahanData;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Penjagaan tambah-saja pada baris audit, dan label yang ditampilkannya.
 *
 * Jejak yang bisa disunting bukan jejak. Penjagaan ada di tingkat model, bukan
 * cuma di controller, supaya tetap berlaku kalau nanti ada kode lain yang memuat
 * model ini — halaman ekspor, perintah artisan, atau pembersih data.
 *
 * Sengaja tanpa database: kedua event dilempar sebelum query apa pun dikirim,
 * jadi cukup instance yang ditandai sudah tersimpan.
 */
class AuditPerubahanDataTest extends TestCase
{
    private function barisTersimpan(): AuditPerubahanData
    {
        $audit = new AuditPerubahanData([
            'modul' => 'purchases',
            'modul_id' => 1,
            'field' => 'no_invoice',
            'nilai_lama' => 'INV-1',
            'nilai_baru' => 'INV-2',
            'alasan' => 'salah ketik nomor invoice',
        ]);

        // Ditandai sudah tersimpan supaya save() menempuh jalur update, bukan
        // insert. Tidak ada query yang dikirim: event updating dilempar lebih
        // dulu dan langsung membatalkan prosesnya.
        $audit->exists = true;
        $audit->id = 1;

        return $audit;
    }

    #[Test]
    public function baris_audit_tidak_boleh_diubah(): void
    {
        $audit = $this->barisTersimpan();
        $audit->nilai_baru = 'INV-3';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/tidak boleh diubah/');

        $audit->save();
    }

    #[Test]
    public function baris_audit_tidak_boleh_dihapus(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/tidak boleh dihapus/');

        $this->barisTersimpan()->delete();
    }

    #[Test]
    public function tidak_punya_kolom_updated_at(): void
    {
        // Kolomnya memang tidak ada di tabel. Kalau konstanta ini hilang,
        // setiap insert akan gagal dengan "unknown column updated_at".
        $this->assertNull(AuditPerubahanData::UPDATED_AT);
    }

    #[Test]
    public function label_modul_dan_kolom_diambil_dari_daftar_putih(): void
    {
        $audit = new AuditPerubahanData(['modul' => 'purchase_details', 'field' => 'unit_price']);

        // Namanya bukan lagi "Harga Lot": modulnya sekarang memuat qty juga.
        $this->assertSame('Lot Bahan Masuk', $audit->labelModul());
        $this->assertSame('Harga per Unit', $audit->labelField());
    }

    #[Test]
    public function modul_yang_dikeluarkan_dari_daftar_putih_tetap_tampil(): void
    {
        // Baris lama tidak boleh jadi kosong hanya karena config-nya berubah:
        // yang membaca audit perlu tahu kolom apa yang dulu diubah.
        $audit = new AuditPerubahanData(['modul' => 'modul_lama', 'field' => 'kolom_lama']);

        $this->assertSame('modul_lama', $audit->labelModul());
        $this->assertSame('kolom_lama', $audit->labelField());
    }
}
