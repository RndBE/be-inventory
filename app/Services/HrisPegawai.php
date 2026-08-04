<?php

namespace App\Services;

use App\Helpers\LogHelper;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Pembacaan identitas pegawai dari HRIS.
 *
 * HRIS pemilik sah data kepegawaian — nomor ID, jabatan, dan divisi. Salinan di
 * inventory sudah terbukti melenceng (divisi tercatat "Admin", sedangkan HRIS
 * menyebut "HRD & CORPORATE SERVICE"), jadi untuk dokumen resmi identitasnya
 * diambil dari sana.
 *
 * Dipanggil saat Berita Acara Serah Terima Aset DIBUAT, lalu hasilnya dibekukan
 * di dokumennya. Tidak pernah dipanggil saat mencetak, karena dua hal:
 *
 *   - dokumen yang sudah ditandatangani tidak boleh berubah isinya
 *   - pencetakan tidak boleh gagal hanya karena HRIS sedang mati
 *
 * Semua kegagalan mengembalikan null, tidak melempar exception. Pemanggil
 * memutuskan cadangannya; untuk BAST, cadangannya data user inventory.
 * Menahan proses keluar karyawan karena sistem lain bermasalah itu berlebihan.
 */
class HrisPegawai
{
    /**
     * Identitas pegawai berdasarkan email, atau null kalau tidak bisa didapat.
     *
     * Pencocokannya lewat email — sama seperti arah sebaliknya (HRIS membaca
     * aset karyawan dari inventory). Pegawai yang emailnya berbeda di kedua
     * sistem tidak akan ketemu, dan itu berakhir sebagai null, bukan error.
     *
     * @return array{name: ?string, nomor_id: ?string, jabatan: ?string, divisi: ?string}|null
     */
    public static function byEmail(?string $email): ?array
    {
        $email = trim((string) $email);
        $url = rtrim((string) config('services.hris.url'), '/');
        $key = config('services.hris.key');

        // Belum dikonfigurasi bukan keadaan darurat: sebelum HRIS tersambung,
        // BAST tetap harus bisa dibuat dengan data inventory.
        if ($email === '' || $url === '' || empty($key)) {
            return null;
        }

        try {
            $respons = Http::withHeaders(['X-API-KEY' => $key])
                ->acceptJson()
                ->timeout((int) config('services.hris.timeout', 5))
                ->get($url . '/api/pegawai/by-email', ['email' => $email]);
        } catch (Throwable $e) {
            // Umumnya HRIS mati atau jaringan putus. Dicatat supaya ketahuan
            // kalau dokumen mulai sering terisi dari cadangan.
            LogHelper::error('HRIS tidak bisa dihubungi saat mencari ' . $email . ': ' . $e->getMessage());
            return null;
        }

        // 404 berarti emailnya memang tidak terdaftar di HRIS — bukan gangguan,
        // jadi tidak perlu dicatat sebagai error.
        if ($respons->status() === 404) {
            return null;
        }

        if (!$respons->successful()) {
            LogHelper::error('HRIS membalas ' . $respons->status() . ' saat mencari ' . $email
                . ': ' . $respons->json('message', ''));
            return null;
        }

        $data = $respons->json();

        if (!is_array($data)) {
            return null;
        }

        // Nilai kosong disamakan dengan null supaya pemanggil cukup memeriksa
        // satu keadaan saja saat memutuskan memakai cadangan.
        return [
            'name' => self::teks($data['name'] ?? null),
            'nomor_id' => self::teks($data['nomor_id'] ?? null),
            'jabatan' => self::teks($data['jabatan'] ?? null),
            'divisi' => self::teks($data['divisi'] ?? null),
        ];
    }

    private static function teks($nilai): ?string
    {
        $nilai = trim((string) $nilai);

        return $nilai === '' ? null : $nilai;
    }
}
