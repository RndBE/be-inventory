<?php

namespace App\Services;

use App\Models\BahanKeluarDetails;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Menelusuri satu baris bahan keluar kembali ke transaksi bahan masuknya.
 *
 * Kode bahan masuk tidak punya kolom sendiri di `bahan_keluar_details`; yang ada
 * hanya kolom `details` berisi JSON seperti
 * `[{"kode_transaksi":"KBM-...","qty":"6.00","unit_price":"492.25"}]`. JSON itu
 * dipakai saat approve untuk memotong lot di `purchase_details` secara FIFO
 * (lihat BahanKeluarController), jadi kode di dalamnya memang lot yang benar-benar
 * terpakai, bukan tebakan.
 *
 * Tidak semua baris punya kode itu. Pada semester pertama 2025, 396 dari 1.991
 * baris bahan ber-qty yang sudah disetujui tidak menyimpannya. Untuk baris itu
 * asalnya dicocokkan lewat harga satuan — aturan yang sama dipakai controller
 * saat mencari lot — dan hasilnya memulihkan sekitar separuhnya. Hasil pencocokan
 * sengaja diberi label berbeda dari hasil yang tercatat: pembaca laporan harus
 * bisa membedakan mana yang pasti dan mana yang disimpulkan.
 *
 * Baris yang `details`-nya kosong sama sekali ternyata semuanya ber-qty 0 —
 * penampung yang dibuat alur approve, bukan pengeluaran yang asalnya hilang.
 *
 * Satu baris keluar bisa memakan lebih dari satu lot, jadi yang dikembalikan
 * selalu array — pemanggil yang memutuskan mau ditampilkan sebagai berapa baris.
 */
class AsalBahanMasukService
{
    /** Kode bahan masuk tersimpan di `details`, asalnya pasti. */
    public const TERCATAT = 'Tercatat';

    /** Kode tidak tersimpan, tapi hanya satu pembelian yang harganya cocok. */
    public const COCOK_HARGA = 'Hasil pencocokan harga';

    /** Kode tidak tersimpan dan beberapa pembelian punya harga sama. */
    public const PERLU_DICEK = 'Perlu dicek';

    /** Kode tidak tersimpan dan tidak ada pembelian yang harganya cocok. */
    public const TIDAK_TERCATAT = 'Tidak tercatat';

    /** Barisnya produk setengah jadi / produk jadi, tidak berasal dari pembelian. */
    public const BUKAN_PEMBELIAN = 'Bukan bahan pembelian';

    /**
     * Barisnya tidak memindahkan bahan apa pun.
     *
     * Baris ber-qty 0 dibuat alur approve sebagai penampung, dan jumlahnya
     * banyak: pada semester pertama 2025 semua baris bahan yang `details`-nya
     * kosong ternyata ber-qty 0. Dilabeli sendiri supaya tidak tercampur dengan
     * pengeluaran nyata yang asalnya benar-benar hilang — dua hal itu butuh
     * tindak lanjut yang berbeda.
     */
    public const TANPA_QTY = 'Tidak ada qty keluar';

    /**
     * Banyaknya kode kandidat yang ditulis saat hasilnya ambigu. Sisanya
     * diringkas, karena satu sel Excel berisi dua puluh kode tidak menolong
     * siapa pun yang sedang menelusuri.
     */
    private const MAKS_KANDIDAT_DITULIS = 5;

    /** kode_transaksi => baris purchases|null */
    private array $cachePembelian = [];

    /** "bahan_id|harga" => daftar kandidat pembelian */
    private array $cacheKandidat = [];

    /**
     * @return array<int, array<string, mixed>> minimal satu baris
     */
    public function untukDetailBahanKeluar(BahanKeluarDetails $detail): array
    {
        if (!$detail->bahan_id) {
            return [$this->baris(['ketelusuran' => self::BUKAN_PEMBELIAN])];
        }

        $tercatat = $this->lotTercatat($detail);

        if ($tercatat) {
            return $tercatat;
        }

        return [$this->dariPencocokanHarga($detail)];
    }

    /**
     * Baris asal sebagai array datar, urut sesuai kolom export.
     */
    public function values(array $asal): array
    {
        return [
            $asal['kode_bahan_masuk'] ?? '-',
            $asal['tgl_masuk'] ?? '-',
            $asal['no_invoice'] ?? '-',
            $asal['qty_lot'] ?? '',
            $asal['ketelusuran'] ?? self::TIDAK_TERCATAT,
        ];
    }

    /**
     * Judul kolom yang dihasilkan values(), supaya export tidak perlu
     * menuliskannya ulang dan dua-duanya tidak bisa bergeser sendiri-sendiri.
     */
    public function headings(): array
    {
        return [
            'Kode Bahan Masuk',
            'Tgl Bahan Masuk',
            'No Invoice',
            'Qty dari Lot',
            'Ketelusuran Asal',
        ];
    }

    /**
     * Lot yang kodenya memang tersimpan di `details`.
     *
     * Entri tanpa `kode_transaksi` dilewati, bukan dijadikan baris kosong: yang
     * seperti itu tidak memberi informasi apa pun dan hanya menggandakan baris.
     */
    private function lotTercatat(BahanKeluarDetails $detail): array
    {
        $entri = json_decode((string) $detail->details, true);

        if (!is_array($entri)) {
            return [];
        }

        $hasil = [];

        foreach ($entri as $lot) {
            if (!is_array($lot) || empty($lot['kode_transaksi'])) {
                continue;
            }

            $kode = (string) $lot['kode_transaksi'];
            $pembelian = $this->pembelian($kode);

            $hasil[] = $this->baris([
                'kode_bahan_masuk' => $kode,
                'tgl_masuk' => $this->tanggal($pembelian->tgl_masuk ?? null),
                'no_invoice' => $pembelian->no_invoice ?? '-',
                'qty_lot' => isset($lot['qty']) ? (float) $lot['qty'] : '',
                'ketelusuran' => self::TERCATAT,
            ]);
        }

        return $hasil;
    }

    /**
     * Tebak asal dari harga satuannya.
     *
     * Harga dihitung dari `sub_total / qty` karena `bahan_keluar_details` tidak
     * punya kolom `unit_price` — yang ada di $fillable modelnya tidak pernah
     * benar-benar tersimpan.
     *
     * Pembandingannya dibulatkan ke dua desimal. `purchase_details.unit_price`
     * disimpan sebagai decimal(15,4) dan harga hasil bagi di atas hampir tidak
     * pernah sama persis sampai digit terakhir, jadi perbandingan eksak akan
     * menolak lot yang sebenarnya cocok.
     */
    private function dariPencocokanHarga(BahanKeluarDetails $detail): array
    {
        $qty = (float) $detail->qty;
        $subTotal = (float) $detail->sub_total;

        if ($qty <= 0 || $subTotal <= 0) {
            return $this->baris(['ketelusuran' => self::TANPA_QTY]);
        }

        $kandidat = $this->kandidat((int) $detail->bahan_id, $subTotal / $qty);

        if ($kandidat->isEmpty()) {
            return $this->baris(['ketelusuran' => self::TIDAK_TERCATAT]);
        }

        if ($kandidat->count() === 1) {
            $pembelian = $kandidat->first();

            return $this->baris([
                'kode_bahan_masuk' => $pembelian->kode_transaksi,
                'tgl_masuk' => $this->tanggal($pembelian->tgl_masuk),
                'no_invoice' => $pembelian->no_invoice ?? '-',
                'qty_lot' => $qty,
                'ketelusuran' => self::COCOK_HARGA,
            ]);
        }

        return $this->baris([
            'kode_bahan_masuk' => $this->ringkasKandidat($kandidat->pluck('kode_transaksi')->all()),
            'qty_lot' => $qty,
            'ketelusuran' => self::PERLU_DICEK,
        ]);
    }

    /**
     * Dibuat protected, bukan private, supaya unit test bisa menggantinya dan
     * menguji perakitan barisnya tanpa menyentuh database sungguhan.
     */
    protected function pembelian(string $kodeTransaksi)
    {
        if (!array_key_exists($kodeTransaksi, $this->cachePembelian)) {
            $this->cachePembelian[$kodeTransaksi] = DB::table('purchases')
                ->where('kode_transaksi', $kodeTransaksi)
                ->first(['kode_transaksi', 'tgl_masuk', 'no_invoice']);
        }

        return $this->cachePembelian[$kodeTransaksi];
    }

    /**
     * Pembelian bahan ini yang harga satuannya sama, satu baris per kode.
     */
    protected function kandidat(int $bahanId, float $harga)
    {
        $kunci = $bahanId . '|' . number_format($harga, 2, '.', '');

        if (!array_key_exists($kunci, $this->cacheKandidat)) {
            $this->cacheKandidat[$kunci] = DB::table('purchase_details as pd')
                ->join('purchases as p', 'p.id', '=', 'pd.purchase_id')
                ->where('pd.bahan_id', $bahanId)
                ->whereRaw('ROUND(pd.unit_price, 2) = ?', [round($harga, 2)])
                ->orderBy('p.tgl_masuk')
                ->get(['p.kode_transaksi', 'p.tgl_masuk', 'p.no_invoice'])
                ->unique('kode_transaksi')
                ->values();
        }

        return $this->cacheKandidat[$kunci];
    }

    private function ringkasKandidat(array $kode): string
    {
        $ditulis = array_slice($kode, 0, self::MAKS_KANDIDAT_DITULIS);
        $sisa = count($kode) - count($ditulis);

        return implode(', ', $ditulis) . ($sisa > 0 ? " (+{$sisa} lainnya)" : '');
    }

    private function tanggal($nilai): string
    {
        return $nilai ? Carbon::parse($nilai)->format('d/m/Y') : '-';
    }

    private function baris(array $baris): array
    {
        return array_merge([
            'kode_bahan_masuk' => '-',
            'tgl_masuk' => '-',
            'no_invoice' => '-',
            'qty_lot' => '',
            'ketelusuran' => self::TIDAK_TERCATAT,
        ], $baris);
    }
}
