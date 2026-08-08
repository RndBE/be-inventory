<?php

namespace App\Services;

use App\Helpers\GoogleDriveHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Harga modal (HPP) per unit produk setengah jadi dan produk jadi.
 *
 * Angkanya TIDAK dihitung ulang di sini. Yang dipakai adalah `unit_price` yang
 * sudah dibekukan saat unit lolos QC dan masuk gudang — nilai yang sama persis
 * dengan yang tampil di halaman Produk Jadi dan Produk Setengah Jadi. Menghitung
 * ulang dari bahan keluar akan menghasilkan angka kedua yang berbeda untuk unit
 * yang sama, dan perbedaan itu mustahil dijelaskan ke pengguna.
 *
 * Kode produksi dan asal produk sample diambil dengan COALESCE dari tabel stok
 * lebih dulu, baru dari daftar QC. Bukan karena rapi, tapi karena datanya memang
 * begitu: `bahan_setengahjadis.produksi_id` hanya terisi 20 dari 271 baris dan
 * `produk_jadis.produksi_produk_jadi_id` 27 dari 50, sedangkan daftar QC terisi
 * penuh. Kalau hanya mengandalkan FK di tabel stok, sebagian besar baris akan
 * tampil tanpa kode produksi di CRM.
 *
 * Kenapa bukan "bahan yang pertama kali keluar":
 * satu kode produksi bisa punya banyak pengeluaran bahan susulan. Pada produksi
 * BE - CST - 110-00024 misalnya, ada 26 pengeluaran; yang pertama hanya
 * Rp 40,9 juta dari total Rp 88,5 juta. Memakai pengeluaran pertama sebagai
 * harga modal berarti menampilkan Rp 5,8 juta/unit untuk barang yang biaya
 * sebenarnya Rp 12,5 juta/unit — kurang dari separuh, dan berbahaya kalau
 * dipakai sebagai dasar harga jual.
 */
class HargaModalService
{
    /**
     * Produk jadi per unit, terbaru dulu.
     *
     * @param bool $hanyaTersedia batasi ke unit yang stoknya masih ada
     */
    public function produkJadi(bool $hanyaTersedia = false): Collection
    {
        $query = DB::table('produk_jadi_details as d')
            ->join('produk_jadis as s', 's.id', '=', 'd.produk_jadis_id')
            ->leftJoin('produk_jadi as m', 'm.id', '=', 'd.produk_id')
            ->leftJoin('qc_produk_jadi_list as q', 'q.id', '=', 's.id_qc_produk_jadi')
            ->leftJoin('produksi_produk_jadi as p', 'p.id', '=', 's.produksi_produk_jadi_id')
            ->leftJoin('produksi_produk_jadi as pq', 'pq.id', '=', 'q.produksi_produk_jadi_id')
            ->select([
                'd.id',
                'd.nama_produk',
                'd.serial_number',
                'd.qty',
                'd.sisa',
                'd.unit_price',
                's.kode_transaksi as kode_unit',
                's.tgl_masuk',
                's.link_gambar',
                DB::raw('COALESCE(s.produk_sample_id, q.produk_sample_id) as produk_sample_id'),
                DB::raw('COALESCE(p.kode_produksi, pq.kode_produksi) as kode_produksi'),
                DB::raw('COALESCE(s.produksi_produk_jadi_id, q.produksi_produk_jadi_id) as produksi_id'),
                'm.nama_produk as nama_master',
                'm.sub_solusi',
                'm.kode_bahan',
            ]);

        if ($hanyaTersedia) {
            $query->where('d.sisa', '>', 0);
        }

        return $query->orderByDesc('s.tgl_masuk')->orderByDesc('d.id')->get()
            ->map(fn ($row) => $this->baris($row, $row->nama_master ?: $row->nama_produk));
    }

    /**
     * Produk setengah jadi per unit, terbaru dulu.
     */
    public function produkSetengahJadi(bool $hanyaTersedia = false): Collection
    {
        $query = DB::table('bahan_setengahjadi_details as d')
            ->join('bahan_setengahjadis as s', 's.id', '=', 'd.bahan_setengahjadi_id')
            ->leftJoin('bahan as b', 'b.id', '=', 'd.bahan_id')
            ->leftJoin('qc_produk_setengah_jadi_list as q', 'q.id', '=', 's.id_qc_produk_setengahjadi')
            ->leftJoin('produksis as p', 'p.id', '=', 's.produksi_id')
            ->leftJoin('produksis as pq', 'pq.id', '=', 'q.produksi_id')
            ->select([
                'd.id',
                'd.nama_bahan as nama_produk',
                'd.serial_number',
                'd.qty',
                'd.sisa',
                'd.unit_price',
                's.kode_transaksi as kode_unit',
                's.tgl_masuk',
                's.link_gambar',
                DB::raw('COALESCE(s.produk_sample_id, q.produk_sample_id) as produk_sample_id'),
                DB::raw('COALESCE(p.kode_produksi, pq.kode_produksi, q.kode_produksi) as kode_produksi'),
                DB::raw('COALESCE(s.produksi_id, q.produksi_id) as produksi_id'),
                'b.nama_bahan as nama_master',
                'b.kode_bahan',
            ]);

        if ($hanyaTersedia) {
            $query->where('d.sisa', '>', 0);
        }

        return $query->orderByDesc('s.tgl_masuk')->orderByDesc('d.id')->get()
            ->map(fn ($row) => $this->baris($row, $row->nama_master ?: $row->nama_produk));
    }

    /**
     * Bahan (mentah) per bahan, bukan per unit.
     *
     * Bahan tidak punya HPP yang dibekukan seperti produk hasil QC — yang ada
     * hanya harga beli per batch pembelian. Jadi angkanya dibentuk dari
     * `purchase_details`, mengikuti aturan yang sudah dipakai halaman Kalkulasi
     * Restock: stok = SUM(sisa), harga = harga beli terakhir. Memakai aturan yang
     * sama penting supaya dua halaman tidak memberi angka berbeda untuk bahan
     * yang sama.
     *
     * Dilaporkan tiga angka sekaligus, karena "harga modal bahan" tidak punya satu
     * jawaban yang benar untuk semua keperluan:
     *   harga_modal_satuan  - harga beli terakhir, paling mencerminkan biaya restock
     *   harga_modal_rata2   - rata-rata tertimbang dari stok yang benar-benar ada
     *   nilai_persediaan    - SUM(sisa x harga batch), nilai uang yang menganggur
     *
     * Bahan yang belum pernah dibeli tidak muncul: harga modalnya memang tidak ada,
     * dan menampilkannya sebagai Rp 0 akan menyesatkan.
     */
    public function bahan(bool $hanyaTersedia = false): Collection
    {
        $hargaTerakhir = DB::table('purchase_details as pd2')
            ->joinSub(
                DB::table('purchase_details')->selectRaw('bahan_id, MAX(id) as max_id')->groupBy('bahan_id'),
                't',
                function ($join) {
                    $join->on('t.bahan_id', '=', 'pd2.bahan_id')->on('t.max_id', '=', 'pd2.id');
                }
            )
            ->leftJoin('purchases as p2', 'p2.id', '=', 'pd2.purchase_id')
            ->select([
                'pd2.bahan_id',
                'pd2.unit_price as harga_terakhir',
                'p2.tgl_masuk as tgl_beli_terakhir',
            ]);

        $query = DB::table('bahan as b')
            ->join('purchase_details as pd', 'pd.bahan_id', '=', 'b.id')
            ->joinSub($hargaTerakhir, 'lp', 'lp.bahan_id', '=', 'b.id')
            ->leftJoin('unit as u', 'u.id', '=', 'b.unit_id')
            ->leftJoin('jenis_bahan as j', 'j.id', '=', 'b.jenis_bahan_id')
            // ONLY_FULL_GROUP_BY aktif di server ini, jadi semua kolom non-agregat
            // wajib ikut di GROUP BY.
            ->groupBy('b.id', 'b.kode_bahan', 'b.nama_bahan', 'b.gambar', 'u.nama', 'j.nama', 'lp.harga_terakhir', 'lp.tgl_beli_terakhir')
            ->select([
                'b.kode_bahan',
                'b.nama_bahan',
                'b.gambar',
                'u.nama as unit',
                'j.nama as jenis_bahan',
                'lp.harga_terakhir',
                'lp.tgl_beli_terakhir',
                DB::raw('SUM(pd.sisa) as stok_sisa'),
                DB::raw('SUM(pd.sisa * pd.unit_price) as nilai_persediaan'),
            ]);

        if ($hanyaTersedia) {
            $query->havingRaw('SUM(pd.sisa) > 0');
        }

        return $query->orderBy('b.nama_bahan')->get()->map(function ($row) {
            $stok = (float) $row->stok_sisa;
            $nilai = (float) $row->nilai_persediaan;

            return [
                'nama_produk' => $row->nama_bahan,
                'kode_produk' => $row->kode_bahan,
                'jenis_bahan' => $row->jenis_bahan,
                'unit' => $row->unit,
                'gambar_path' => $row->gambar,
                'gambar_url' => $this->urlGambar($row->gambar),
                'stok_sisa' => $stok,
                'harga_modal_satuan' => (float) $row->harga_terakhir,
                'harga_modal_rata2' => $stok > 0 ? round($nilai / $stok, 2) : 0.0,
                'nilai_persediaan' => $nilai,
                'tgl_masuk' => $row->tgl_beli_terakhir,
                'sumber' => 'Pembelian',
            ];
        });
    }

    /**
     * Rincian bahan yang dipakai satu kode produksi — isi tombol detail di CRM.
     *
     * Sumbernya `produksi_details` / `produksi_produk_jadi_details`, yaitu daftar
     * yang sama yang dipakai QC untuk menghitung harga modal. Jadi total di sini
     * dibagi jumlah produksi selalu sama dengan `harga_modal_satuan` di tab —
     * marketing bisa menelusuri angkanya sampai ke bahan tanpa menemukan selisih.
     *
     * Untuk produk jadi, komponennya bisa berupa bahan mentah maupun produk
     * setengah jadi (10 dari 74 baris pada produksi BE - CST - 110-00024),
     * dibedakan lewat field `jenis`.
     *
     * @return array<string, mixed>|null null bila kode produksinya tidak ada
     */
    public function rincianBahan(string $tipe, int $produksiId): ?array
    {
        if ($tipe === 'produk-jadi') {
            $induk = DB::table('produksi_produk_jadi')->where('id', $produksiId)
                ->first(['id', 'kode_produksi', 'jml_produksi', 'keterangan', 'mulai_produksi', 'status']);

            if (!$induk) {
                return null;
            }

            $rows = DB::table('produksi_produk_jadi_details as d')
                ->leftJoin('bahan as b', 'b.id', '=', 'd.bahan_id')
                ->leftJoin('bahan_setengahjadi_details as sd', 'sd.id', '=', 'd.produk_id')
                ->where('d.produksi_produk_jadi_id', $produksiId)
                ->select([
                    'd.bahan_id',
                    'd.produk_id',
                    'd.qty',
                    'd.details',
                    'd.sub_total',
                    'b.nama_bahan',
                    'b.kode_bahan',
                    'b.gambar',
                    'sd.nama_bahan as nama_setengah',
                    'sd.serial_number as serial_setengah',
                ])
                ->get();
        } elseif ($tipe === 'setengah-jadi') {
            $induk = DB::table('produksis')->where('id', $produksiId)
                ->first(['id', 'kode_produksi', 'jml_produksi', 'keterangan', 'mulai_produksi', 'status']);

            if (!$induk) {
                return null;
            }

            $rows = DB::table('produksi_details as d')
                ->leftJoin('bahan as b', 'b.id', '=', 'd.bahan_id')
                ->where('d.produksi_id', $produksiId)
                ->select([
                    'd.bahan_id',
                    'd.qty',
                    'd.details',
                    'd.sub_total',
                    'b.nama_bahan',
                    'b.kode_bahan',
                    'b.gambar',
                ])
                ->get();
        } else {
            return null;
        }

        $item = $rows->map(function ($row) {
            $qty = (float) $row->qty;
            $subTotal = (float) $row->sub_total;
            $dariSetengahJadi = empty($row->nama_bahan) && !empty($row->nama_setengah ?? null);

            return [
                'jenis' => $dariSetengahJadi ? 'Produk Setengah Jadi' : 'Bahan',
                'nama' => $dariSetengahJadi ? $row->nama_setengah : ($row->nama_bahan ?: '(tidak dikenal)'),
                'kode' => $dariSetengahJadi ? null : $row->kode_bahan,
                'serial_number' => $dariSetengahJadi ? ($row->serial_setengah ?? null) : null,
                'gambar_url' => $dariSetengahJadi ? null : $this->urlGambar($row->gambar),
                'qty' => $qty,
                // Rata-rata, karena satu baris bisa memakai beberapa batch harga.
                'harga_satuan' => $qty > 0 ? round($subTotal / $qty, 2) : 0.0,
                'sub_total' => $subTotal,
                'batch' => $this->batchHarga($row->details),
            ];
        })->sortBy([['jenis', 'asc'], ['nama', 'asc']])->values();

        // Dibulatkan ke rupiah-sen: menjumlah float desimal menghasilkan ekor
        // seperti 87711202.74000001, dan angka itu akan tampil apa adanya di CRM.
        $total = round((float) $item->sum('sub_total'), 2);
        $jml = (float) ($induk->jml_produksi ?: 0);

        return [
            'tipe' => $tipe,
            'produksi_id' => (int) $induk->id,
            'kode_produksi' => $induk->kode_produksi,
            'keterangan' => $induk->keterangan,
            'mulai_produksi' => $induk->mulai_produksi,
            'status' => $induk->status,
            'jml_produksi' => $jml,
            'jumlah_item' => $item->count(),
            'total_biaya_bahan' => $total,
            // Harus sama dengan harga_modal_satuan di tab. Kalau beda, ada yang salah.
            'harga_modal_satuan' => $jml > 0 ? round($total / $jml, 2) : 0.0,
            'data' => $item,
        ];
    }

    /**
     * Rincian per batch harga dari kolom `details`, mis. [{qty, unit_price}].
     *
     * Satu bahan bisa diambil dari beberapa batch pembelian dengan harga berbeda,
     * dan selisihnya kadang besar — pada produksi BE - CST - 110-00024 ada bahan
     * yang batch-nya Rp 3.369.589 dan Rp 4.354.629 sekaligus.
     *
     * @return array<int, array{qty: float, unit_price: float}>
     */
    private function batchHarga($details): array
    {
        if (is_string($details)) {
            $details = json_decode($details, true);
        }

        if (!is_array($details)) {
            return [];
        }

        $hasil = [];
        foreach ($details as $batch) {
            if (!is_array($batch) || !isset($batch['qty'], $batch['unit_price'])) {
                continue;
            }
            $hasil[] = [
                // Dibulatkan karena qty di JSON menyimpan ekor float mentah:
                // Din Rail tercatat 0.19999999999999996, dan angka itu akan
                // tampil apa adanya di CRM kalau dibiarkan.
                'qty' => round((float) $batch['qty'], 4),
                'unit_price' => round((float) $batch['unit_price'], 2),
            ];
        }

        return $hasil;
    }

    /**
     * URL gambar yang bisa dipakai dari luar inventory.
     *
     * Nama file gambar bahan banyak yang mengandung spasi, jadi tiap segmen path
     * di-encode. Basis URL-nya dari APP_URL — kalau APP_URL di server salah,
     * gambarnya tidak akan tampil di CRM, dan `gambar_path` jadi jalan keluarnya.
     */
    private function urlGambar(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $aman = implode('/', array_map('rawurlencode', explode('/', $path)));

        return rtrim((string) config('app.url'), '/') . '/storage/' . $aman;
    }

    /**
     * Ringkasan per nama produk, untuk baris teratas tabel di CRM.
     *
     * `harga_modal_terakhir` diambil dari unit yang paling baru masuk gudang —
     * itu angka yang paling mendekati biaya produksi saat ini. Min dan max ikut
     * dibawa supaya selisih antar batch kelihatan; kalau rentangnya lebar,
     * marketing perlu tahu sebelum memakai satu angka untuk semua.
     */
    public function ringkasan(Collection $unit): Collection
    {
        return $unit
            ->groupBy('nama_produk')
            ->map(function (Collection $rows, string $nama) {
                $harga = $rows->pluck('harga_modal_satuan')->filter(fn ($h) => $h > 0);

                return [
                    'nama_produk' => $nama,
                    'jumlah_unit' => $rows->count(),
                    'stok_tersedia' => (float) $rows->sum('stok_sisa'),
                    'harga_modal_terakhir' => (float) ($rows->first()['harga_modal_satuan'] ?? 0),
                    'harga_modal_terendah' => (float) ($harga->min() ?? 0),
                    'harga_modal_tertinggi' => (float) ($harga->max() ?? 0),
                ];
            })
            ->values();
    }

    private function baris(object $row, ?string $nama): array
    {
        return [
            'nama_produk' => $nama,
            'kode_produk' => $row->kode_bahan ?? null,
            'sub_solusi' => $row->sub_solusi ?? null,
            'kode_produksi' => $row->kode_produksi,
            // Dipakai tombol "rincian bahan" di CRM. Sengaja id, bukan kode:
            // 123 dari 321 unit setengah jadi kode produksinya null.
            'produksi_id' => $row->produksi_id ? (int) $row->produksi_id : null,
            'kode_unit' => $row->kode_unit,
            'serial_number' => $row->serial_number,
            // Foto unit yang masuk gudang. Tautan Drive diubah jadi URL thumbnail
            // yang bisa langsung dipasang di <img>; tautan lain dikirim apa adanya.
            'gambar_url' => GoogleDriveHelper::thumbnail($row->link_gambar, 400) ?: ($row->link_gambar ?: null),
            'link_gambar' => $row->link_gambar ?: null,
            'tgl_masuk' => $row->tgl_masuk,
            'qty' => (float) $row->qty,
            'stok_sisa' => (float) $row->sisa,
            'harga_modal_satuan' => (float) $row->unit_price,
            'sumber' => $row->produk_sample_id ? 'Produk Sample' : 'Produksi',
        ];
    }
}
