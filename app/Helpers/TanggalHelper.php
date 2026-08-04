<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * Tanggal dalam huruf untuk dokumen resmi.
 *
 * Format Berita Acara Serah Terima Aset menulis tanggalnya sebagai kalimat:
 * "Pada hari Senin tanggal tiga bulan Agustus tahun dua ribu dua puluh enam".
 * Di berkas Word aslinya bagian ini dikosongkan untuk ditulis tangan — di PDF
 * bisa diisi otomatis, jadi tidak ada lagi kolom yang terlupa.
 */
class TanggalHelper
{
    private const SATUAN = [
        'nol', 'satu', 'dua', 'tiga', 'empat', 'lima',
        'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas',
    ];

    /**
     * Bilangan bulat jadi huruf bahasa Indonesia.
     *
     * Cukup sampai ribuan: yang perlu diucapkan di dokumen ini hanya tanggal
     * (1–31) dan tahun (mis. 2026). Nilai di luar itu dikembalikan sebagai angka
     * biasa daripada menghasilkan kalimat yang salah.
     */
    public static function keHuruf(int $angka): string
    {
        if ($angka < 0 || $angka > 9999) {
            return (string) $angka;
        }

        if ($angka < 12) {
            return self::SATUAN[$angka];
        }

        if ($angka < 20) {
            return self::keHuruf($angka - 10) . ' belas';
        }

        if ($angka < 100) {
            $puluh = intdiv($angka, 10);
            $sisa = $angka % 10;

            return self::keHuruf($puluh) . ' puluh' . ($sisa ? ' ' . self::keHuruf($sisa) : '');
        }

        if ($angka < 200) {
            return 'seratus' . ($angka % 100 ? ' ' . self::keHuruf($angka % 100) : '');
        }

        if ($angka < 1000) {
            $ratus = intdiv($angka, 100);
            $sisa = $angka % 100;

            return self::keHuruf($ratus) . ' ratus' . ($sisa ? ' ' . self::keHuruf($sisa) : '');
        }

        if ($angka < 2000) {
            return 'seribu' . ($angka % 1000 ? ' ' . self::keHuruf($angka % 1000) : '');
        }

        $ribu = intdiv($angka, 1000);
        $sisa = $angka % 1000;

        return self::keHuruf($ribu) . ' ribu' . ($sisa ? ' ' . self::keHuruf($sisa) : '');
    }

    /**
     * Bagian-bagian tanggal siap dicetak, atau null kalau tanggalnya kosong.
     *
     * Sengaja mengembalikan bagian terpisah, bukan satu kalimat utuh: format
     * resminya menyelipkan kata "hari", "tanggal", "bulan", "tahun" di antara
     * nilainya, dan sebagian ditebalkan.
     */
    public static function bagianTanggal($tanggal): ?array
    {
        if (!$tanggal) {
            return null;
        }

        $carbon = $tanggal instanceof Carbon ? $tanggal : Carbon::parse($tanggal);

        return [
            'hari' => $carbon->locale('id')->translatedFormat('l'),
            'tanggal' => self::keHuruf((int) $carbon->day),
            'bulan' => $carbon->locale('id')->translatedFormat('F'),
            'tahun' => self::keHuruf((int) $carbon->year),
            'angka' => $carbon->format('d/m/Y'),
        ];
    }
}
