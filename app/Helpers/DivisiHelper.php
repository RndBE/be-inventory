<?php

namespace App\Helpers;

/**
 * Pemetaan role -> divisi yang boleh dilihat.
 *
 * Sistem tidak menyimpan divisi pada data user (hanya organization & job position,
 * dan keduanya tidak sepadan dengan daftar divisi yang dipakai di pengajuan).
 * Karena itu cakupan divisi diturunkan dari role, mengikuti aturan yang sudah
 * lebih dulu dipakai untuk menyaring bahan keluar & pembelian bahan.
 *
 * Ditaruh di satu tempat supaya aturannya tidak bercabang di banyak file.
 */
class DivisiHelper
{
    /**
     * Divisi yang tercakup oleh role seorang user.
     *
     * Mengembalikan null kalau role-nya tidak punya pembatasan divisi yang
     * dikenal. Pemanggil yang menentukan artinya: layar hitung sidebar
     * memperlakukannya sebagai "semua", layar approval memperlakukannya
     * sebagai "pakai hierarki atasan saja".
     */
    public static function divisiUntuk($user): ?array
    {
        if (!$user) {
            return null;
        }

        if ($user->hasRole(['superadmin', 'administrasi', 'purchasing'])) {
            return null; // tanpa batas divisi
        }

        if ($user->hasRole(['hardware manager'])) {
            return ['RnD', 'Purchasing', 'Helper', 'Teknisi', 'OP', 'Produksi'];
        }

        if ($user->hasRole(['rnd', 'rnd level 3'])) {
            return ['RnD'];
        }

        if ($user->hasRole(['purchasing level 3', 'helper'])) {
            return ['Purchasing', 'Helper'];
        }

        if ($user->hasRole(['teknisi level 3', 'teknisi', 'op', 'produksi'])) {
            return ['Teknisi', 'OP', 'Produksi'];
        }

        if ($user->hasRole(['marketing manager', 'marketing', 'marketing level 3', 'publikasi'])) {
            return ['Marketing', 'publikasi'];
        }

        if ($user->hasRole(['software manager', 'software'])) {
            return ['Software'];
        }

        if ($user->hasRole(['hse'])) {
            return ['HSE'];
        }

        if ($user->hasRole(['sekretaris'])) {
            return ['Sekretaris'];
        }

        // Catatan: HRD sengaja tidak dipetakan ke satu divisi. Di layar approval
        // peminjaman aset, HRD justru harus melihat semua divisi.
        return null;
    }
}
