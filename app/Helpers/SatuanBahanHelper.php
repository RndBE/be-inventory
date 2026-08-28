<?php

namespace App\Helpers;

use App\Models\Bahan;

/**
 * Konversi satuan untuk bahan batangan (pipa, besi hollow, dan sejenisnya).
 *
 * Bahan seperti pipa dibeli per batang tapi dipakai per potongan, jadi satu
 * satuan saja tidak cukup. Yang dipilih di sini: ledger stok selalu memakai
 * satuan terkecil (cm), dan "batang" cuma satuan input di form. Dengan begitu
 * `purchase_details.sisa` tetap satu angka yang bisa dijumlahkan seperti bahan
 * lain, dan tidak ada tempat di aplikasi yang perlu menjumlahkan dua dimensi.
 *
 * Penanda bahan batangan adalah `bahan.panjang_standar` yang tidak null. Kalau
 * null, semua fungsi di sini meneruskan nilainya apa adanya — jadi aman
 * dipanggil untuk bahan apa pun tanpa perlu percabangan di sisi pemanggil.
 *
 * Catatan harga: harga yang tersimpan di ledger mengikuti satuan ledger, jadi
 * untuk bahan batangan `unit_price` berarti harga per cm — pipa Rp 175.000 per
 * batang 600 cm tersimpan 291,6667. Ini bukan pilihan estetika: `qty *
 * unit_price` dipakai di puluhan titik di seluruh aplikasi, dan semuanya akan
 * salah 600 kali kalau harganya per batang sedangkan qty-nya cm. Dengan harga
 * per cm, perhitungan lama tetap benar tanpa satu pun perlu diubah.
 *
 * Subtotal tidak boleh dihitung ulang dari harga per cm. Hitung dari angka yang
 * diketik user (5 batang x Rp 175.000) lewat subTotal() supaya hasilnya eksak;
 * harga per cm hanya untuk menilai potongan yang diambil sebagian.
 */
class SatuanBahanHelper
{
    public const SATUAN_BATANG = 'batang';

    public const SATUAN_DASAR = 'cm';

    /**
     * Toleransi perbandingan panjang, dalam cm.
     *
     * Nilai cm sebetulnya bilangan bulat, tapi kolom qty di beberapa tabel
     * bertipe decimal dan sudah menyimpan pecahan, jadi perbandingan
     * "pas satu batang" tidak boleh memakai == mentah.
     */
    private const TOLERANSI = 0.0001;

    /**
     * Panjang standar bahan, atau null kalau bahannya bukan batangan.
     *
     * Menerima model Bahan, id bahan, atau angka panjang langsung supaya
     * pemanggil tidak perlu menyeragamkan bentuk argumennya lebih dulu.
     */
    public static function panjangStandar($bahan): ?int
    {
        if ($bahan === null || $bahan === '') {
            return null;
        }

        if ($bahan instanceof Bahan) {
            $panjang = $bahan->panjang_standar;
        } elseif (is_object($bahan)) {
            $panjang = $bahan->panjang_standar ?? null;
        } elseif (is_array($bahan)) {
            $panjang = $bahan['panjang_standar'] ?? null;
        } else {
            $panjang = $bahan;
        }

        $panjang = (int) $panjang;

        return $panjang > 0 ? $panjang : null;
    }

    /**
     * Apakah bahan ini boleh diinput dengan dua satuan (batang atau cm).
     */
    public static function dwiSatuan($bahan): bool
    {
        return self::panjangStandar($bahan) !== null;
    }

    /**
     * Daftar satuan input yang valid, untuk mengisi dropdown di form.
     *
     * Bahan non-batangan mengembalikan array kosong supaya dropdown-nya tidak
     * usah dirender sama sekali.
     */
    public static function pilihanSatuan($bahan, ?string $namaUnit = null): array
    {
        if (! self::dwiSatuan($bahan)) {
            return [];
        }

        return [
            self::SATUAN_BATANG => $namaUnit ?: 'Batang',
            self::SATUAN_DASAR => 'cm',
        ];
    }

    /**
     * Angka yang diketik user jadi angka satuan dasar untuk disimpan ke ledger.
     *
     * Satuan yang tidak dikenal diperlakukan sebagai satuan dasar, bukan
     * dilemparkan sebagai error: alur lama tidak mengirim `satuan_input` sama
     * sekali, dan angkanya di sana memang sudah dalam satuan dasar.
     */
    public static function keSatuanDasar($qtyInput, ?string $satuanInput, $bahan): float
    {
        $qty = (float) $qtyInput;
        $panjangStandar = self::panjangStandar($bahan);

        if ($panjangStandar === null) {
            return $qty;
        }

        if (self::normalkanSatuan($satuanInput) === self::SATUAN_BATANG) {
            return $qty * $panjangStandar;
        }

        return $qty;
    }

    /**
     * Angka satuan dasar dikembalikan ke satuan input, untuk mengisi form edit.
     */
    public static function dariSatuanDasar($qtyDasar, ?string $satuanInput, $bahan): float
    {
        $qty = (float) $qtyDasar;
        $panjangStandar = self::panjangStandar($bahan);

        if ($panjangStandar === null) {
            return $qty;
        }

        if (self::normalkanSatuan($satuanInput) === self::SATUAN_BATANG) {
            return $qty / $panjangStandar;
        }

        return $qty;
    }

    /**
     * Total panjang dinyatakan sebagai jumlah batang, boleh pecahan.
     */
    public static function keBatang($qtyDasar, $bahan): float
    {
        $panjangStandar = self::panjangStandar($bahan);

        if ($panjangStandar === null) {
            return (float) $qtyDasar;
        }

        return (float) $qtyDasar / $panjangStandar;
    }

    /**
     * Total panjang dipecah jadi jumlah batang utuh dan sisa cm.
     *
     * Ini murni cara menampilkan angka, bukan pelacakan fisik: sistem tidak
     * menyimpan potongan satu per satu, jadi 1200 cm akan tampil "2 batang"
     * baik fisiknya dua batang utuh maupun empat potongan @300 cm.
     */
    public static function pecah($qtyDasar, $bahan): array
    {
        $panjangStandar = self::panjangStandar($bahan);
        $qty = (float) $qtyDasar;

        if ($panjangStandar === null) {
            return ['batang' => 0, 'sisa' => $qty];
        }

        $batang = (int) floor(($qty + self::TOLERANSI) / $panjangStandar);

        return [
            'batang' => $batang,
            'sisa' => round($qty - ($batang * $panjangStandar), 2),
        ];
    }

    /**
     * Angka stok siap ditampilkan.
     *
     * Bahan non-batangan tampil seperti sebelumnya, mis. "12 Pcs". Bahan
     * batangan tampil sebagai gabungan, mis. "6 Batang + 40 cm", supaya angka
     * cm mentah (3640) tidak dibaca sebagai jumlah barang.
     */
    public static function format($qtyDasar, $bahan, ?string $namaUnit = null): string
    {
        $qty = (float) $qtyDasar;
        $panjangStandar = self::panjangStandar($bahan);

        if ($panjangStandar === null) {
            return trim(self::angka($qty) . ' ' . ($namaUnit ?? ''));
        }

        $pecahan = self::pecah($qty, $panjangStandar);
        $labelBatang = $namaUnit ?: 'Batang';

        if ($pecahan['batang'] > 0 && abs($pecahan['sisa']) >= self::TOLERANSI) {
            return $pecahan['batang'] . ' ' . $labelBatang . ' + ' . self::angka($pecahan['sisa']) . ' cm';
        }

        if ($pecahan['batang'] > 0) {
            return $pecahan['batang'] . ' ' . $labelBatang;
        }

        return self::angka($pecahan['sisa']) . ' cm';
    }

    /**
     * Harga yang diketik user jadi harga per satuan dasar untuk disimpan.
     *
     * Harga per batang Rp 175.000 pada batang 600 cm jadi 291,6667 per cm.
     * Empat desimal, sama dengan lebar kolom `purchase_details.unit_price`.
     */
    public static function keHargaSatuanDasar($unitPriceInput, ?string $satuanInput, $bahan): float
    {
        $harga = (float) $unitPriceInput;
        $panjangStandar = self::panjangStandar($bahan);

        if ($panjangStandar === null) {
            return $harga;
        }

        if (self::normalkanSatuan($satuanInput) === self::SATUAN_BATANG) {
            return round($harga / $panjangStandar, 4);
        }

        return $harga;
    }

    /**
     * Harga per satuan dasar dikembalikan ke satuan input, untuk form edit.
     */
    public static function dariHargaSatuanDasar($unitPriceDasar, ?string $satuanInput, $bahan): float
    {
        $harga = (float) $unitPriceDasar;
        $panjangStandar = self::panjangStandar($bahan);

        if ($panjangStandar === null) {
            return $harga;
        }

        if (self::normalkanSatuan($satuanInput) === self::SATUAN_BATANG) {
            return round($harga * $panjangStandar, 2);
        }

        return $harga;
    }

    /**
     * Subtotal dihitung dalam satuan yang diketik user, bukan satuan dasar.
     *
     * 5 batang x Rp 175.000 = Rp 875.000, eksak. Kalau dihitung dari harga per
     * cm (3000 cm x 291,6667) hasilnya Rp 875.000,10 — galat pembulatan yang
     * tidak perlu ada, karena angka aslinya bulat.
     */
    public static function subTotal($qtyInput, $unitPriceInput): float
    {
        return round((float) $qtyInput * (float) $unitPriceInput, 2);
    }

    /**
     * Nilai rupiah dari sejumlah satuan dasar pada harga satuan dasar.
     *
     * Untuk pengambilan sebagian, mis. 40 cm x 291,6667 = Rp 11.666,67. Di sini
     * pembulatan memang tidak terhindarkan — tapi hanya nilai rupiahnya yang
     * dibulatkan, stok cm-nya tetap utuh.
     */
    public static function nilaiSatuanDasar($qtyDasar, $unitPriceDasar): float
    {
        return round((float) $qtyDasar * (float) $unitPriceDasar, 2);
    }

    /**
     * Apakah panjang ini pas sejumlah batang utuh.
     *
     * Dipakai untuk retur: barang yang dikembalikan ke supplier harus batang
     * utuh, bukan potongan. Pemeriksaannya cuma soal angka — sistem tidak
     * melacak potongan, jadi ini tidak bisa menjamin batangnya belum terpotong.
     */
    public static function kelipatanBatang($qtyDasar, $bahan): bool
    {
        $panjangStandar = self::panjangStandar($bahan);

        if ($panjangStandar === null) {
            return true;
        }

        $sisa = fmod((float) $qtyDasar, $panjangStandar);

        return $sisa < self::TOLERANSI || ($panjangStandar - $sisa) < self::TOLERANSI;
    }

    /**
     * Satuan input yang tersimpan diseragamkan jadi salah satu konstanta.
     */
    public static function normalkanSatuan(?string $satuanInput): string
    {
        $satuan = strtolower(trim((string) $satuanInput));

        return $satuan === self::SATUAN_BATANG ? self::SATUAN_BATANG : self::SATUAN_DASAR;
    }

    /**
     * Angka tanpa desimal nol yang tidak perlu, mis. 40 bukan 40,00.
     */
    private static function angka(float $nilai): string
    {
        if (abs($nilai - round($nilai)) < self::TOLERANSI) {
            return number_format(round($nilai), 0, ',', '.');
        }

        return rtrim(rtrim(number_format($nilai, 2, ',', '.'), '0'), ',');
    }
}
