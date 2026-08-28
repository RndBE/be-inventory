<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Menambah stok 500 untuk setiap bahan lewat satu lot pembelian khusus.
 *
 * Dipakai untuk mengisi stok awal semua bahan sekaligus. Semua baris dibuat di
 * bawah satu `purchases` dengan kode SEED-STOK-500 supaya gampang dibatalkan:
 * hapus satu baris purchase itu dan seluruh lot ikut terhapus lewat cascade
 * `purchase_details.purchase_id`. Lot lama tidak disentuh sama sekali, jadi
 * stok yang sudah ada tetap utuh dan riwayatnya tidak berubah.
 *
 * Untuk bahan batangan (punya `panjang_standar`), 500 berarti 500 batang.
 * Ledger stok selalu dalam satuan dasar cm, jadi angka yang disimpan adalah
 * 500 x panjang_standar. Lihat App\Helpers\SatuanBahanHelper.
 *
 * Harga mengikuti lot terakhir bahan yang bersangkutan supaya laporan biaya
 * proyek tetap masuk akal; bahan yang belum pernah dibeli dapat harga 0.
 */
class StokAwal500Seeder extends Seeder
{
    private const KODE_TRANSAKSI = 'SEED-STOK-500';

    private const QTY_PER_BAHAN = 500;

    public function run(): void
    {
        if (DB::table('purchases')->where('kode_transaksi', self::KODE_TRANSAKSI)->exists()) {
            $this->command->warn('Seeder dilewati: purchase '.self::KODE_TRANSAKSI.' sudah ada. Hapus dulu kalau mau mengulang.');

            return;
        }

        // Harga terakhir per bahan diambil sekali di depan; kalau dicari per
        // baris, 2000-an bahan berarti 2000-an query.
        $hargaTerakhir = DB::table('purchase_details as pd')
            ->select('pd.bahan_id', 'pd.unit_price')
            ->whereIn('pd.id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('purchase_details')
                    ->groupBy('bahan_id');
            })
            ->pluck('unit_price', 'bahan_id');

        $sekarang = now();

        DB::transaction(function () use ($hargaTerakhir, $sekarang) {
            $purchaseId = DB::table('purchases')->insertGetId([
                'tgl_masuk' => $sekarang,
                'kode_transaksi' => self::KODE_TRANSAKSI,
                'no_invoice' => null,
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ]);

            $jumlahBahan = 0;

            DB::table('bahan')
                ->select('id', 'panjang_standar')
                ->orderBy('id')
                ->chunk(500, function ($bahans) use ($purchaseId, $hargaTerakhir, $sekarang, &$jumlahBahan) {
                    $baris = [];

                    foreach ($bahans as $bahan) {
                        $panjangStandar = (int) $bahan->panjang_standar > 0 ? (int) $bahan->panjang_standar : null;
                        $qty = $panjangStandar ? self::QTY_PER_BAHAN * $panjangStandar : self::QTY_PER_BAHAN;
                        $unitPrice = (float) ($hargaTerakhir[$bahan->id] ?? 0);

                        $baris[] = [
                            'purchase_id' => $purchaseId,
                            'bahan_id' => $bahan->id,
                            'panjang_standar' => $panjangStandar,
                            'qty' => $qty,
                            'sisa' => $qty,
                            'unit_price' => $unitPrice,
                            'sub_total' => round($qty * $unitPrice, 2),
                            'created_at' => $sekarang,
                            'updated_at' => $sekarang,
                        ];
                    }

                    DB::table('purchase_details')->insert($baris);
                    $jumlahBahan += count($baris);
                });

            $this->command->info("Lot 500 dibuat untuk {$jumlahBahan} bahan (purchase id {$purchaseId}, kode ".self::KODE_TRANSAKSI.').');
        });
    }
}
