<?php

namespace App\Models\Concerns;

use App\Helpers\SatuanBahanHelper;

/**
 * Angka qty baris transaksi bahan, siap ditampilkan.
 *
 * Untuk bahan batangan kolom `qty` menyimpan panjang total dalam cm. Angka
 * mentahnya (3.000) mudah terbaca sebagai jumlah barang, dan di dokumen cetak
 * kesalahan baca itu tidak ada yang mengoreksi. Trait ini dipakai bersama oleh
 * semua tabel detail transaksi bahan supaya satu baris Blade cukup ditulis
 * sekali bentuknya.
 *
 * Baris yang bukan bahan — produk setengah jadi, produk jadi, atau bahan yang
 * datanya sudah terhapus — jatuh ke angka apa adanya, persis seperti sebelum
 * fitur dwi-satuan ada.
 *
 * Modelnya wajib punya relasi `dataBahan` ke Bahan.
 */
trait MenampilkanQtyBahan
{
    /**
     * Qty baris ini sebagai teks, mis. "5 Batang" atau "6 Batang + 40 cm".
     *
     * Argumennya boleh diisi kalau yang ingin ditampilkan bukan `qty` baris ini
     * melainkan angka lain dalam satuan yang sama — misalnya sisa, atau qty
     * satu potongan di dalam `details`.
     */
    public function qtyTampil($qtyDasar = null): string
    {
        $qty = $qtyDasar ?? $this->qty;
        $bahan = $this->dataBahan;

        if ($bahan === null || ! SatuanBahanHelper::dwiSatuan($bahan)) {
            return SatuanBahanHelper::format($qty, null);
        }

        return $bahan->formatQty($qty);
    }

    /**
     * Satuan yang dipakai orangnya saat mengisi baris ini: "Batang" atau "cm".
     *
     * qtyTampil() menjawab pertanyaan lain - berapa panjangnya, dinyatakan
     * dalam batang. Yang ini menjawab bagaimana angkanya ditulis: retur 1
     * batang utuh dan retur 600 cm potongan menghasilkan qty yang sama persis
     * di ledger, tapi artinya di gudang berbeda.
     *
     * Null untuk bahan biasa, dan untuk baris yang dibuat sebelum kolom
     * `satuan_input` ada - di sana memang tidak ada jejaknya untuk ditebak.
     */
    public function satuanInputTampil(): ?string
    {
        $bahan = $this->dataBahan;

        if ($bahan === null || ! SatuanBahanHelper::dwiSatuan($bahan)) {
            return null;
        }

        $satuanInput = $this->satuan_input ?? null;

        if ($satuanInput === null || $satuanInput === '') {
            return null;
        }

        if (SatuanBahanHelper::normalkanSatuan($satuanInput) !== SatuanBahanHelper::SATUAN_BATANG) {
            return SatuanBahanHelper::SATUAN_DASAR;
        }

        return $bahan->dataUnit->nama ?? 'Batang';
    }

    /**
     * Angka apa adanya yang diketik, lengkap dengan satuannya: "1 Batang".
     *
     * Null kalau tidak ada jejaknya - lihat satuanInputTampil(). Pemanggil yang
     * ingin selalu punya sesuatu untuk ditampilkan bisa jatuh ke qtyTampil().
     */
    public function qtyInputTampil(): ?string
    {
        $satuan = $this->satuanInputTampil();
        $qtyInput = $this->qtyInputAngka();

        if ($satuan === null || $qtyInput === null) {
            return null;
        }

        return trim($qtyInput . ' ' . $satuan);
    }

    /**
     * Angka yang diketik tanpa satuannya, mis. "1" atau "50".
     *
     * Untuk tabel yang sudah punya kolom satuan sendiri - dokumen cetak retur,
     * misalnya - supaya satuannya tidak tertulis dua kali dalam satu baris.
     */
    public function qtyInputAngka(): ?string
    {
        $qtyInput = $this->qty_input ?? null;

        if ($this->satuanInputTampil() === null || $qtyInput === null || $qtyInput === '') {
            return null;
        }

        return SatuanBahanHelper::format($qtyInput, null);
    }
}
