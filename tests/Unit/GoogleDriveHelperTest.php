<?php

namespace Tests\Unit;

use App\Helpers\GoogleDriveHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Penguraian tautan Google Drive.
 *
 * Yang dikunci di sini terutama bentuk tautan SELAIN '/view': dulu modal gambar
 * memotong di '/view', sehingga tautan '?usp=sharing' dan '/edit' menyisakan
 * ekor dan membuat preview-nya kosong — sementara thumbnail di tabel, yang
 * memotong di '/', tetap tampil. Perbedaan itulah yang bikin gejalanya
 * membingungkan.
 */
class GoogleDriveHelperTest extends TestCase
{
    public static function tautan(): array
    {
        return [
            'view + usp' => ['https://drive.google.com/file/d/1AbC_dEfGhIjKlM/view?usp=sharing', '1AbC_dEfGhIjKlM'],
            'view saja' => ['https://drive.google.com/file/d/1AbC_dEfGhIjKlM/view', '1AbC_dEfGhIjKlM'],
            'edit' => ['https://drive.google.com/file/d/1AbC_dEfGhIjKlM/edit', '1AbC_dEfGhIjKlM'],
            'tanpa ekor' => ['https://drive.google.com/file/d/1AbC_dEfGhIjKlM', '1AbC_dEfGhIjKlM'],
            'query langsung' => ['https://drive.google.com/file/d/1AbC_dEfGhIjKlM?usp=drive_link', '1AbC_dEfGhIjKlM'],
            'open?id=' => ['https://drive.google.com/open?id=1AbC_dEfGhIjKlM', '1AbC_dEfGhIjKlM'],
            'uc export' => ['https://drive.google.com/uc?export=view&id=1AbC_dEfGhIjKlM', '1AbC_dEfGhIjKlM'],
            'id polos' => ['1AbC_dEfGhIjKlM', '1AbC_dEfGhIjKlM'],
        ];
    }

    #[Test]
    #[DataProvider('tautan')]
    public function membaca_id_dari_berbagai_bentuk_tautan(string $url, string $harap): void
    {
        $this->assertSame($harap, GoogleDriveHelper::fileId($url));
    }

    #[Test]
    public function tautan_bukan_drive_dan_nilai_kosong_menghasilkan_null(): void
    {
        $this->assertNull(GoogleDriveHelper::fileId(null));
        $this->assertNull(GoogleDriveHelper::fileId(''));
        $this->assertNull(GoogleDriveHelper::fileId('   '));
        $this->assertNull(GoogleDriveHelper::fileId('https://contoh.test/foto.jpg'));
        // Terlalu pendek untuk sebuah id Drive — jangan sampai potongan acak
        // dianggap id lalu menghasilkan URL preview yang pasti gagal.
        $this->assertNull(GoogleDriveHelper::fileId('abc'));
    }

    #[Test]
    public function pola_lama_yang_memotong_di_view_memang_salah_untuk_tautan_ini(): void
    {
        $url = 'https://drive.google.com/file/d/1AbC_dEfGhIjKlM?usp=sharing';

        // Cara lama di modal gambar: explode('/d/')[1] lalu explode('/view')[0].
        $lama = explode('/view', explode('/d/', $url)[1])[0];

        $this->assertSame('1AbC_dEfGhIjKlM?usp=sharing', $lama, 'pola lama menyisakan ekor');
        $this->assertSame('1AbC_dEfGhIjKlM', GoogleDriveHelper::fileId($url));
    }

    #[Test]
    public function membangun_alamat_thumbnail_dan_preview(): void
    {
        $url = 'https://drive.google.com/file/d/1AbC_dEfGhIjKlM/view?usp=sharing';

        $this->assertSame(
            'https://drive.google.com/thumbnail?id=1AbC_dEfGhIjKlM&sz=w120',
            GoogleDriveHelper::thumbnail($url, 120)
        );
        $this->assertSame(
            'https://drive.google.com/file/d/1AbC_dEfGhIjKlM/preview',
            GoogleDriveHelper::preview($url)
        );
        $this->assertNull(GoogleDriveHelper::thumbnail('https://contoh.test/x.jpg'));
        $this->assertNull(GoogleDriveHelper::preview(null));
    }
}
