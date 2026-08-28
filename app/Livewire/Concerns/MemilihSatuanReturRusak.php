<?php

namespace App\Livewire\Concerns;

use App\Helpers\SatuanBahanHelper;
use App\Models\Bahan;

/**
 * Pilihan satuan batang/cm untuk baris retur dan bahan rusak.
 *
 * Berbeda dari MemilihSatuanBahan yang melayani keranjang: baris retur dan
 * rusak tidak punya id item sendiri. Keduanya array berindeks angka yang
 * ditambah lewat tombol per harga lot, dan Blade mengalamatinya dengan indeks
 * itu (`bahanRetur.3.qty`). Karena itu satuan pilihannya disimpan di dalam
 * barisnya sendiri sebagai `satuan`, bukan di array terpisah — tidak ada kunci
 * stabil lain yang bisa dipakai, dan indeks bisa bergeser saat baris dihapus.
 *
 * Yang dikembalikan dari proyek biasanya potongan, jadi default-nya cm — beda
 * dengan keranjang pengambilan yang default-nya batang. Opsi batang tetap
 * disediakan untuk kasus batang utuh yang sama sekali tidak terpakai, karena
 * mengetik "600" untuk satu batang mudah salah hitung.
 *
 * Kesepakatan yang dipegang semua pemakainya:
 * - `$bahanRetur[$i]['qty']` menyimpan angka apa adanya yang diketik user,
 *   dalam satuan yang dia pilih di baris itu.
 * - Konversi ke satuan dasar lewat qtyDasarRetur()/qtyDasarRusak() — dipakai
 *   saat membandingkan dengan batas pengambilan dan saat mengirim ke controller.
 *
 * Pemakainya wajib punya properti `$bahanRetur` dan `$bahanRusak` berisi array
 * baris dengan salah satu dari `bahan_id`, `id`, atau `produk_id`.
 */
trait MemilihSatuanReturRusak
{
    /**
     * Panjang standar per bahan_id, diingat selama satu request.
     *
     * Properti protected sengaja dipilih: nilainya cuma cache, tidak perlu ikut
     * dikirim bolak-balik ke browser oleh Livewire.
     */
    protected $memoPanjangStandarBahan = [];

    /**
     * Id bahan pada satu baris retur/rusak, atau null kalau barisnya produk.
     *
     * Nama kuncinya tidak seragam antar keranjang — sebagian memakai `bahan_id`,
     * sebagian `id`. Baris produk setengah jadi dan produk jadi tidak punya
     * konsep batang, jadi dikembalikan null supaya pilihan satuannya tidak
     * dirender sama sekali.
     */
    protected function bahanIdBaris($baris): ?int
    {
        if (! is_array($baris)) {
            return null;
        }

        if (! empty($baris['produk_id']) || ! empty($baris['produk_jadis_id'])) {
            return null;
        }

        $bahanId = $baris['bahan_id'] ?? $baris['id'] ?? null;

        return $bahanId === null ? null : (int) $bahanId;
    }

    /**
     * Panjang standar bahan, atau null kalau bukan bahan batangan.
     */
    protected function panjangStandarBahan($bahanId): ?int
    {
        if ($bahanId === null) {
            return null;
        }

        if (! array_key_exists($bahanId, $this->memoPanjangStandarBahan)) {
            $this->memoPanjangStandarBahan[$bahanId] = Bahan::where('id', $bahanId)->value('panjang_standar');
        }

        return SatuanBahanHelper::panjangStandar($this->memoPanjangStandarBahan[$bahanId]);
    }

    /**
     * Nama unit bahan untuk label satuan, mis. "Batang".
     */
    protected function namaUnitBahan($bahanId): ?string
    {
        if ($bahanId === null) {
            return null;
        }

        return Bahan::with('dataUnit')->find($bahanId)?->dataUnit?->nama;
    }

    /**
     * Nama unit bahan untuk mengisi opsi "batang" di dropdown.
     *
     * Dipakai Blade langsung, jadi harus public. Jatuh ke "Batang" kalau master
     * bahannya tidak punya unit — labelnya tetap terbaca, bukan kosong.
     */
    public function namaUnitTampil($bahanId): string
    {
        return $this->namaUnitBahan($bahanId) ?: 'Batang';
    }

    /**
     * Baris retur/rusak pada satu indeks, atau null kalau indeksnya tidak ada.
     */
    protected function barisRetur($index)
    {
        return $this->bahanRetur[$index] ?? null;
    }

    protected function barisRusak($index)
    {
        return $this->bahanRusak[$index] ?? null;
    }

    /**
     * Indeks baris retur/rusak dari pasangan id dan harga satuan.
     *
     * Method updateReturQty/updateRusakQty yang sudah ada dipanggil Blade
     * dengan id dan unit_price, bukan indeks — itu pasangan yang mengidentifikasi
     * satu baris, karena satu bahan bisa muncul beberapa kali dengan harga lot
     * berbeda. Helper ini menerjemahkannya ke indeks supaya tanda tangan method
     * lamanya tidak perlu berubah.
     */
    protected function indexRetur($id, $unitPrice = null): ?int
    {
        return $this->cariIndexBaris($this->bahanRetur ?? [], $id, $unitPrice);
    }

    protected function indexRusak($id, $unitPrice = null): ?int
    {
        return $this->cariIndexBaris($this->bahanRusak ?? [], $id, $unitPrice);
    }

    private function cariIndexBaris($daftar, $id, $unitPrice): ?int
    {
        $cadangan = null;

        foreach ($daftar as $i => $baris) {
            $cocokId = ($baris['bahan_id'] ?? null) == $id
                || ($baris['id'] ?? null) == $id
                || ($baris['produk_id'] ?? null) == $id
                || ($baris['produk_jadis_id'] ?? null) == $id;

            if (! $cocokId) {
                continue;
            }

            if ($unitPrice === null || ($baris['unit_price'] ?? null) == $unitPrice) {
                return $i;
            }

            // Harga bisa berbeda format (string vs float) setelah bolak-balik
            // lewat Livewire; baris pertama yang id-nya cocok dipakai sebagai
            // cadangan supaya pilihan satuannya tidak hilang begitu saja.
            if ($cadangan === null) {
                $cadangan = $i;
            }
        }

        return $cadangan;
    }

    /**
     * Panjang standar baris retur, atau null kalau bukan bahan batangan.
     *
     * Dipakai Blade untuk memutuskan perlu tidaknya merender pilihan satuan.
     */
    public function panjangStandarBarisRetur($index): ?int
    {
        return $this->panjangStandarBahan($this->bahanIdBaris($this->barisRetur($index)));
    }

    public function panjangStandarBarisRusak($index): ?int
    {
        return $this->panjangStandarBahan($this->bahanIdBaris($this->barisRusak($index)));
    }

    /**
     * Satuan yang sedang aktif pada satu baris.
     *
     * Baris lama yang belum punya kunci `satuan` jatuh ke cm — itu perilaku
     * sebelum pilihan satuan ada, dan angkanya di sana memang sudah cm.
     */
    public function satuanBarisRetur($index): string
    {
        return SatuanBahanHelper::normalkanSatuan($this->barisRetur($index)['satuan'] ?? null);
    }

    public function satuanBarisRusak($index): string
    {
        return SatuanBahanHelper::normalkanSatuan($this->barisRusak($index)['satuan'] ?? null);
    }

    /**
     * Label satuan untuk ditampilkan, mis. "Batang" atau "cm".
     */
    public function labelSatuanBarisRetur($index): string
    {
        if ($this->satuanBarisRetur($index) !== SatuanBahanHelper::SATUAN_BATANG) {
            return 'cm';
        }

        return $this->namaUnitBahan($this->bahanIdBaris($this->barisRetur($index))) ?: 'Batang';
    }

    public function labelSatuanBarisRusak($index): string
    {
        if ($this->satuanBarisRusak($index) !== SatuanBahanHelper::SATUAN_BATANG) {
            return 'cm';
        }

        return $this->namaUnitBahan($this->bahanIdBaris($this->barisRusak($index))) ?: 'Batang';
    }

    /**
     * Angka yang diketik pada satu baris, dikonversi ke satuan dasar.
     *
     * Ini angka yang boleh dibandingkan dengan batas pengambilan dan dikirim ke
     * controller — bukan angka mentah di `qty`.
     */
    public function qtyDasarBarisRetur($index, $qtyInput = null): float
    {
        $baris = $this->barisRetur($index);

        return SatuanBahanHelper::keSatuanDasar(
            $qtyInput ?? ($baris['qty'] ?? 0),
            $this->satuanBarisRetur($index),
            $this->panjangStandarBarisRetur($index)
        );
    }

    public function qtyDasarBarisRusak($index, $qtyInput = null): float
    {
        $baris = $this->barisRusak($index);

        return SatuanBahanHelper::keSatuanDasar(
            $qtyInput ?? ($baris['qty'] ?? 0),
            $this->satuanBarisRusak($index),
            $this->panjangStandarBarisRusak($index)
        );
    }

    /**
     * Satuan yang layak disimpan ke database untuk satu baris.
     *
     * Bahan non-batangan mengembalikan null supaya kolom jejaknya tetap kosong
     * seperti sebelum fitur ini ada.
     */
    protected function satuanTersimpanRetur($index): ?string
    {
        return $this->panjangStandarBarisRetur($index) ? $this->satuanBarisRetur($index) : null;
    }

    protected function satuanTersimpanRusak($index): ?string
    {
        return $this->panjangStandarBarisRusak($index) ? $this->satuanBarisRusak($index) : null;
    }

    /**
     * Batas atas angka yang boleh diketik, dalam satuan input yang aktif.
     *
     * Untuk satuan batang hasilnya dibulatkan ke bawah: batas 2040 cm pada
     * batang 600 cm cuma bisa diretur 3 batang, dan 240 cm sisanya harus
     * diretur dengan memilih satuan cm.
     */
    protected function maksInputBaris($panjangStandar, string $satuan, $maksDasar): float
    {
        $maks = SatuanBahanHelper::dariSatuanDasar($maksDasar, $satuan, $panjangStandar);

        if ($panjangStandar && $satuan === SatuanBahanHelper::SATUAN_BATANG) {
            return floor($maks);
        }

        return $maks;
    }

    /**
     * Batas atas satu baris dalam satuan input yang aktif, untuk atribut `max`.
     *
     * Blade menghitung batasnya dari alokasi lot yang tersimpan dalam cm.
     * Tanpa dikonversi, atribut `max` di input akan membandingkan angka batang
     * dengan angka cm — batasnya jadi jauh lebih longgar dari stok sebenarnya.
     */
    public function maksInputRetur($index, $maksDasar): float
    {
        return $this->maksInputBaris(
            $this->panjangStandarBarisRetur($index),
            $this->satuanBarisRetur($index),
            $maksDasar
        );
    }

    public function maksInputRusak($index, $maksDasar): float
    {
        return $this->maksInputBaris(
            $this->panjangStandarBarisRusak($index),
            $this->satuanBarisRusak($index),
            $maksDasar
        );
    }

    /**
     * Angka retur yang diketik, dibatasi jumlah pengambilan.
     *
     * Perbandingannya di satuan dasar karena batasnya berasal dari alokasi lot
     * yang tersimpan dalam cm, sedangkan yang diketik bisa jadi jumlah batang.
     * Yang dikembalikan tetap dalam satuan input.
     */
    protected function batasiQtyRetur($index, $qtyInput, $maksDasar)
    {
        if ($this->qtyDasarBarisRetur($index, $qtyInput) <= $maksDasar) {
            return $qtyInput;
        }

        return $this->maksInputBaris(
            $this->panjangStandarBarisRetur($index),
            $this->satuanBarisRetur($index),
            $maksDasar
        );
    }

    protected function batasiQtyRusak($index, $qtyInput, $maksDasar)
    {
        if ($this->qtyDasarBarisRusak($index, $qtyInput) <= $maksDasar) {
            return $qtyInput;
        }

        return $this->maksInputBaris(
            $this->panjangStandarBarisRusak($index),
            $this->satuanBarisRusak($index),
            $maksDasar
        );
    }

    /**
     * Satuan awal untuk baris retur/rusak yang baru ditambahkan.
     *
     * cm, bukan batang: yang dikembalikan dari proyek umumnya potongan sisa.
     * Bahan non-batangan juga cm, dan di sana konversinya selalu identitas.
     */
    protected function satuanAwalBaris($bahanId): string
    {
        return SatuanBahanHelper::SATUAN_DASAR;
    }

    /**
     * Ganti satuan satu baris retur, lalu kosongkan angkanya.
     *
     * Angka lama tidak dipertahankan karena artinya berubah: "1" yang tadinya
     * 1 batang tidak boleh diam-diam terbaca 1 cm.
     */
    public function updateSatuanRetur($index)
    {
        if (isset($this->bahanRetur[$index])) {
            $this->bahanRetur[$index]['qty'] = null;
        }

        if (method_exists($this, 'calculateTotalHarga')) {
            $this->calculateTotalHarga();
        }
    }

    public function updateSatuanRusak($index)
    {
        if (isset($this->bahanRusak[$index])) {
            $this->bahanRusak[$index]['qty'] = null;
        }

        if (method_exists($this, 'calculateTotalHarga')) {
            $this->calculateTotalHarga();
        }
    }
}
