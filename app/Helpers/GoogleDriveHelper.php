<?php

namespace App\Helpers;

/**
 * Pembacaan id berkas dari tautan Google Drive.
 *
 * Sebelum ini penguraiannya disalin di tiga tempat dengan cara yang berbeda:
 *
 *   - tabel rekap aset & pencarian aset : explode('/d/', $u)[1] lalu explode('/')[0]
 *   - modal gambar aset                 : explode('/d/', $u)[1] lalu explode('/view')[0]
 *
 * Versi yang memotong di '/view' hanya benar untuk tautan yang memang berakhiran
 * '/view'. Untuk '.../d/ABC123?usp=sharing' atau '.../d/ABC123/edit', ekornya
 * ikut terbawa sehingga URL preview-nya rusak — dan hasilnya modal gambarnya
 * kosong padahal thumbnail di tabel yang sama tampil normal.
 *
 * Bentuk tautan yang ditangani:
 *   https://drive.google.com/file/d/<ID>/view?usp=sharing
 *   https://drive.google.com/file/d/<ID>/edit
 *   https://drive.google.com/file/d/<ID>
 *   https://drive.google.com/open?id=<ID>
 *   https://drive.google.com/uc?export=view&id=<ID>
 *   <ID> (kalau yang tersimpan memang id-nya saja)
 */
class GoogleDriveHelper
{
    /**
     * Id berkas Drive dari sebuah tautan, atau null kalau tidak bisa dikenali.
     *
     * Null berarti "tampilkan apa adanya sebagai tautan biasa", bukan error:
     * kolom link_gambar bebas diisi tautan apa pun, tidak harus Google Drive.
     */
    public static function fileId(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        // Pola /d/<ID> — bentuk yang paling umum. Pembatasnya apa pun yang bukan
        // bagian id: '/', '?', '&', atau ujung teks.
        if (preg_match('#/d/([A-Za-z0-9_-]{10,})#', $url, $cocok)) {
            return $cocok[1];
        }

        // Pola ?id=<ID> atau &id=<ID>
        if (preg_match('#[?&]id=([A-Za-z0-9_-]{10,})#', $url, $cocok)) {
            return $cocok[1];
        }

        // Sudah berupa id polos, bukan tautan.
        if (preg_match('#^[A-Za-z0-9_-]{10,}$#', $url)) {
            return $url;
        }

        return null;
    }

    /**
     * Alamat thumbnail, untuk pratinjau kecil di tabel dan kartu aset.
     */
    public static function thumbnail(?string $url, int $lebar = 400): ?string
    {
        $id = static::fileId($url);

        return $id ? 'https://drive.google.com/thumbnail?id=' . $id . '&sz=w' . $lebar : null;
    }

    /**
     * Alamat preview, untuk ditanam di iframe.
     */
    public static function preview(?string $url): ?string
    {
        $id = static::fileId($url);

        return $id ? 'https://drive.google.com/file/d/' . $id . '/preview' : null;
    }
}
