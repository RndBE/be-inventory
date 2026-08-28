<?php

namespace App\Livewire\Concerns;

use App\Helpers\SatuanBahanHelper;

/**
 * Pilihan satuan batang/cm untuk keranjang bahan.
 *
 * Stok bahan batangan tersimpan dalam cm, sedangkan orang menghitungnya per
 * batang. Tanpa pilihan satuan yang eksplisit, angka "2" akan terbaca 2 cm
 * padahal maksudnya 2 batang — bahannya jadi seolah tidak terpakai dan biaya
 * proyeknya jauh lebih kecil dari kenyataan.
 *
 * Trait ini dipakai bersama oleh banyak keranjang yang bentuknya nyaris sama
 * (bahan keluar, produksi, projek, projek RnD, garansi, komponen projek).
 * Logikanya dipusatkan di sini supaya tidak ada satu keranjang yang ketinggalan
 * saat aturannya berubah.
 *
 * Kesepakatan yang dipegang semua pemakainya:
 * - `$qty[$itemId]` menyimpan angka apa adanya yang diketik user, dalam satuan
 *   yang dia pilih. Jadi tampilan tidak perlu diubah.
 * - Konversi ke satuan dasar dilakukan lewat qtyDasar() — dipakai saat
 *   membandingkan dengan sisa stok, saat mengalokasikan lot, dan saat mengirim
 *   angka ke controller.
 *
 * Keranjang yang memakainya wajib menyediakan properti `$cart` berisi objek
 * dengan `id` (atau `bahan_id`) dan `panjang_standar`, plus properti `$qty`.
 */
trait MemilihSatuanBahan
{
    /**
     * Satuan input per item keranjang: 'batang' atau 'cm'.
     *
     * Bahan tanpa panjang standar tidak menampilkan pilihannya, dan nilainya
     * diabaikan karena konversinya selalu identitas.
     */
    public $satuan = [];

    /**
     * Objek item di keranjang, atau null kalau tidak ketemu.
     *
     * Kunci item tidak seragam antar keranjang: sebagian memakai `id`, sebagian
     * `bahan_id`, dan keranjang komponen projek memakai `cart_key` berupa string
     * gabungan supaya satu bahan bisa muncul beberapa baris dengan serial
     * number berbeda. Ketiganya diperiksa, `cart_key` lebih dulu karena paling
     * spesifik.
     */
    protected function itemKeranjang($itemId)
    {
        foreach ($this->cart as $item) {
            $kandidat = is_array($item)
                ? [$item['cart_key'] ?? null, $item['id'] ?? null, $item['bahan_id'] ?? null]
                : [$item->cart_key ?? null, $item->id ?? null, $item->bahan_id ?? null];

            foreach ($kandidat as $kunci) {
                if ($kunci !== null && $kunci == $itemId) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Panjang standar item, atau null kalau bukan bahan batangan.
     */
    public function panjangStandarUntuk($itemId): ?int
    {
        $item = $this->itemKeranjang($itemId);

        if ($item === null) {
            return null;
        }

        $panjang = is_array($item) ? ($item['panjang_standar'] ?? null) : ($item->panjang_standar ?? null);

        return SatuanBahanHelper::panjangStandar($panjang);
    }

    /**
     * Satuan input yang sedang aktif untuk satu item.
     */
    public function satuanUntuk($itemId): string
    {
        return SatuanBahanHelper::normalkanSatuan($this->satuan[$itemId] ?? null);
    }

    /**
     * Nama unit bahan, mis. "Batang", terlepas dari satuan yang sedang dipilih.
     *
     * Dipakai untuk menamai opsi batang di dropdown dan keterangan
     * "1 Batang = 600 cm". Keduanya tidak boleh memakai labelSatuanUntuk():
     * label itu ikut satuan yang sedang aktif, jadi begitu user memilih cm,
     * opsi batangnya ikut bernama "cm" dan tidak ada lagi jalan kembali.
     *
     * Baris yang tidak ada di `$cart` - di halaman edit itu baris yang sudah
     * tersimpan - jatuh ke "Batang". Nama generik itu tetap benar; yang hilang
     * cuma nama unit khusus kalau master bahannya memakai istilah lain.
     */
    public function namaUnitUntuk($itemId): string
    {
        $item = $this->itemKeranjang($itemId);
        $unit = is_array($item) ? ($item['unit'] ?? null) : ($item->unit ?? null);

        return $unit ?: 'Batang';
    }

    /**
     * Label satuan yang sedang aktif, mis. "Batang" atau "cm".
     *
     * Untuk menamai angka yang sedang tampil - "maks 5 Batang". Untuk menamai
     * opsi batang di dropdown, pakai namaUnitUntuk().
     */
    public function labelSatuanUntuk($itemId): string
    {
        if ($this->satuanUntuk($itemId) !== SatuanBahanHelper::SATUAN_BATANG) {
            return 'cm';
        }

        return $this->namaUnitUntuk($itemId);
    }

    /**
     * Angka yang diketik user, dikonversi ke satuan dasar.
     *
     * Ini angka yang boleh dibandingkan dengan sisa stok dan dipakai untuk
     * mengalokasikan lot — bukan angka mentah di `$qty`.
     */
    public function qtyDasar($itemId, $qtyInput = null): float
    {
        return SatuanBahanHelper::keSatuanDasar(
            $qtyInput ?? ($this->qty[$itemId] ?? 0),
            $this->satuanUntuk($itemId),
            $this->panjangStandarUntuk($itemId)
        );
    }

    /**
     * Batas atas angka yang boleh diketik, dalam satuan input yang aktif.
     *
     * Untuk satuan batang hasilnya dibulatkan ke bawah: sisa 2040 cm pada
     * batang 600 cm cuma bisa diambil 3 batang, dan 240 cm sisanya harus
     * diambil dengan memilih satuan cm.
     */
    public function maksInput($itemId, $stokDasar): float
    {
        $panjangStandar = $this->panjangStandarUntuk($itemId);
        $maks = SatuanBahanHelper::dariSatuanDasar($stokDasar, $this->satuanUntuk($itemId), $panjangStandar);

        if ($panjangStandar && $this->satuanUntuk($itemId) === SatuanBahanHelper::SATUAN_BATANG) {
            return floor($maks);
        }

        return $maks;
    }

    /**
     * Angka yang diketik dibatasi sisa stok, dalam satuan input.
     *
     * Perbandingannya dilakukan di satuan dasar karena sisa stok tersimpan
     * dalam cm sedangkan yang diketik bisa jadi jumlah batang.
     */
    protected function batasiQtyInput($itemId, $qtyInput, $stokDasar)
    {
        if ($qtyInput === null || $qtyInput === '' || (float) $qtyInput < 0) {
            return null;
        }

        if ($this->qtyDasar($itemId, $qtyInput) <= $stokDasar) {
            return $qtyInput;
        }

        return $this->maksInput($itemId, $stokDasar);
    }

    /**
     * Satuan awal saat item baru masuk keranjang.
     *
     * Batang untuk bahan batangan: pemakaian satu batang utuh jauh lebih sering
     * daripada potongan.
     */
    protected function setelSatuanAwal($itemId, ?int $panjangStandar): void
    {
        $this->satuan[$itemId] = $panjangStandar
            ? SatuanBahanHelper::SATUAN_BATANG
            : SatuanBahanHelper::SATUAN_DASAR;
    }

    /**
     * Ganti satuan input, lalu kosongkan angka yang sudah diketik.
     *
     * Angkanya tidak dipertahankan karena artinya berubah: "2" yang tadinya
     * 2 batang tidak boleh diam-diam jadi 2 cm.
     */
    public function updateSatuan($itemId)
    {
        $this->qty[$itemId] = null;

        if (property_exists($this, 'jml_bahan')) {
            $this->jml_bahan[$itemId] = null;
        }

        if (property_exists($this, 'subtotals')) {
            $this->subtotals[$itemId] = 0;
        }

        if (property_exists($this, 'details')) {
            $this->details[$itemId] = [];
        }

        if (method_exists($this, 'calculateTotalHarga')) {
            $this->calculateTotalHarga();
        }
    }
}
