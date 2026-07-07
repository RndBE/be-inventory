<?php

namespace App\Services;

use App\Models\BahanKeluar;
use App\Models\BahanKeluarDetails;
use App\Models\BahanSetengahjadiDetails;
use App\Models\ProdukJadiDetails;
use App\Models\ProdukSampleDetails;
use App\Models\ProjekRndDetails;

class ProductFlowService
{
    public function forBahanKeluarDetail(BahanKeluarDetails $detail): array
    {
        $source = $this->sourceFromOutgoingDetail($detail);
        $bahanKeluar = $this->relation($detail, 'bahanKeluar');
        $destination = $this->destinationFromBahanKeluar($bahanKeluar);

        return $this->flow([
            'jenis_item' => $source['jenis_item'],
            'kode_sumber' => $source['kode_sumber'],
            'asal_flow' => $source['asal_flow'],
            'serial_number_flow' => $source['serial_number_flow'],
            'tujuan_flow' => $destination['tujuan_flow'],
            'kode_tujuan' => $destination['kode_tujuan'],
            'status_flow' => $bahanKeluar->status ?? '-',
        ]);
    }

    public function forProjekRndDetail(ProjekRndDetails $detail): array
    {
        $source = $this->sourceFromProjectLikeDetail($detail);

        return $this->flow([
            'jenis_item' => $source['jenis_item'],
            'kode_sumber' => $source['kode_sumber'],
            'asal_flow' => $source['asal_flow'],
            'serial_number_flow' => $source['serial_number_flow'],
            'tujuan_flow' => 'Proyek RnD',
            'kode_tujuan' => $this->relation($detail, 'projekRnd')->kode_projek_rnd ?? '-',
            'status_flow' => $this->relation($detail, 'projekRnd')->status ?? '-',
        ]);
    }

    public function forBahanSetengahjadiDetail(BahanSetengahjadiDetails $detail): array
    {
        $header = $this->relation($detail, 'bahanSetengahjadi');
        $lastOutgoing = $this->latestOutgoingForSetengahJadi($detail);
        $destination = $lastOutgoing ? $this->destinationFromBahanKeluar($lastOutgoing->bahanKeluar) : null;

        return $this->flow([
            'jenis_item' => 'Produk Setengah Jadi',
            'kode_sumber' => $header->kode_transaksi ?? '-',
            'asal_flow' => $this->originFromSetengahJadiHeader($header),
            'serial_number_flow' => $detail->serial_number ?: '-',
            'tujuan_flow' => $destination['tujuan_flow'] ?? '-',
            'kode_tujuan' => $destination['kode_tujuan'] ?? '-',
            'status_flow' => $lastOutgoing->bahanKeluar->status ?? ($header->status ?? '-'),
        ]);
    }

    public function forProdukJadiDetail(ProdukJadiDetails $detail): array
    {
        $header = $this->relation($detail, 'ProdukJadis');
        $lastOutgoing = $this->latestOutgoingForProdukJadi($detail);
        $destination = $lastOutgoing ? $this->destinationFromBahanKeluar($lastOutgoing->bahanKeluar) : null;

        return $this->flow([
            'jenis_item' => 'Produk Jadi',
            'kode_sumber' => $header->kode_transaksi ?? '-',
            'asal_flow' => $this->originFromProdukJadiHeader($header),
            'serial_number_flow' => $detail->serial_number ?: '-',
            'tujuan_flow' => $destination['tujuan_flow'] ?? '-',
            'kode_tujuan' => $destination['kode_tujuan'] ?? '-',
            'status_flow' => $lastOutgoing->bahanKeluar->status ?? ($header->status ?? '-'),
        ]);
    }

    public function forProdukSampleDetail(ProdukSampleDetails $detail): array
    {
        $source = $this->sourceFromSampleDetail($detail);

        return $this->flow([
            'jenis_item' => $source['jenis_item'],
            'kode_sumber' => $source['kode_sumber'],
            'asal_flow' => $source['asal_flow'],
            'serial_number_flow' => $source['serial_number_flow'],
            'tujuan_flow' => 'Produk Sample',
            'kode_tujuan' => $this->relation($detail, 'produkSample')->kode_produk_sample ?? '-',
            'status_flow' => $this->relation($detail, 'produkSample')->status ?? '-',
        ]);
    }

    public function values(array $flow): array
    {
        return [
            $flow['jenis_item'] ?? 'Tidak diketahui',
            $flow['kode_sumber'] ?? '-',
            $flow['asal_flow'] ?? 'Tidak diketahui',
            $flow['serial_number_flow'] ?? '-',
            $flow['tujuan_flow'] ?? '-',
            $flow['kode_tujuan'] ?? '-',
            $flow['status_flow'] ?? '-',
        ];
    }

    public function compactValues(array $flow): array
    {
        return [
            $flow['asal_flow'] ?? 'Tidak diketahui',
            $flow['tujuan_flow'] ?? '-',
            $flow['kode_tujuan'] ?? '-',
            $flow['status_flow'] ?? '-',
        ];
    }

    private function sourceFromOutgoingDetail(BahanKeluarDetails $detail): array
    {
        $produkSetengahJadi = $this->relation($detail, 'dataProduk');
        $produkJadi = $this->relation($detail, 'dataProdukJadi');
        $bahan = $this->relation($detail, 'dataBahan');

        if ($produkSetengahJadi || $detail->produk_id) {
            $produk = $produkSetengahJadi;

            return [
                'jenis_item' => 'Produk Setengah Jadi',
                'kode_sumber' => $this->relation($produk, 'bahanSetengahjadi')->kode_transaksi ?? '-',
                'asal_flow' => 'Stok Produk Setengah Jadi',
                'serial_number_flow' => $detail->serial_number ?: ($produk?->serial_number ?? '-'),
            ];
        }

        if ($produkJadi || $detail->produk_jadis_id) {
            $produk = $produkJadi;

            return [
                'jenis_item' => 'Produk Jadi',
                'kode_sumber' => $this->relation($produk, 'ProdukJadis')->kode_transaksi ?? '-',
                'asal_flow' => 'Stok Produk Jadi',
                'serial_number_flow' => $detail->serial_number ?: ($produk?->serial_number ?? '-'),
            ];
        }

        if ($bahan || $detail->bahan_id) {
            return [
                'jenis_item' => 'Bahan',
                'kode_sumber' => $bahan->kode_bahan ?? $detail->kode_transaksi ?? '-',
                'asal_flow' => 'Stok Bahan',
                'serial_number_flow' => $detail->serial_number ?: '-',
            ];
        }

        return $this->unknownSource();
    }

    private function sourceFromProjectLikeDetail(ProjekRndDetails $detail): array
    {
        $produkSetengahJadi = $this->relation($detail, 'dataProduk');
        $bahan = $this->relation($detail, 'dataBahan');

        if ($produkSetengahJadi || $detail->produk_id) {
            $produk = $produkSetengahJadi;

            return [
                'jenis_item' => 'Produk Setengah Jadi',
                'kode_sumber' => $this->relation($produk, 'bahanSetengahjadi')->kode_transaksi ?? '-',
                'asal_flow' => 'Stok Produk Setengah Jadi',
                'serial_number_flow' => $detail->serial_number ?: ($produk?->serial_number ?? '-'),
            ];
        }

        if ($bahan || $detail->bahan_id) {
            return [
                'jenis_item' => 'Bahan',
                'kode_sumber' => $bahan->kode_bahan ?? '-',
                'asal_flow' => 'Stok Bahan',
                'serial_number_flow' => $detail->serial_number ?: '-',
            ];
        }

        return $this->unknownSource();
    }

    private function sourceFromSampleDetail(ProdukSampleDetails $detail): array
    {
        $produkSetengahJadi = $this->relation($detail, 'dataProduk');
        $produkJadi = $this->relation($detail, 'dataProdukJadi');
        $bahan = $this->relation($detail, 'dataBahan');

        if ($produkSetengahJadi || $detail->produk_id) {
            $produk = $produkSetengahJadi;

            return [
                'jenis_item' => 'Produk Setengah Jadi',
                'kode_sumber' => $this->relation($produk, 'bahanSetengahjadi')->kode_transaksi ?? '-',
                'asal_flow' => 'Stok Produk Setengah Jadi',
                'serial_number_flow' => $detail->serial_number ?: ($produk?->serial_number ?? '-'),
            ];
        }

        if ($produkJadi || $detail->produk_jadis_id) {
            $produk = $produkJadi;

            return [
                'jenis_item' => 'Produk Jadi',
                'kode_sumber' => $this->relation($produk, 'ProdukJadis')->kode_transaksi ?? '-',
                'asal_flow' => 'Stok Produk Jadi',
                'serial_number_flow' => $detail->serial_number ?: ($produk?->serial_number ?? '-'),
            ];
        }

        if ($bahan || $detail->bahan_id) {
            return [
                'jenis_item' => 'Bahan',
                'kode_sumber' => $bahan->kode_bahan ?? '-',
                'asal_flow' => 'Stok Bahan',
                'serial_number_flow' => $detail->serial_number ?: '-',
            ];
        }

        return $this->unknownSource();
    }

    private function destinationFromBahanKeluar(?BahanKeluar $bahanKeluar): array
    {
        if (!$bahanKeluar) {
            return ['tujuan_flow' => '-', 'kode_tujuan' => '-'];
        }

        $tujuan = match (true) {
            (bool) $bahanKeluar->produksi_id => 'Produksi',
            (bool) $bahanKeluar->produksi_produk_jadi_id => 'Produksi Produk Jadi',
            (bool) $bahanKeluar->projek_id => 'Proyek',
            (bool) $bahanKeluar->garansi_projek_id => 'Garansi Proyek',
            (bool) $bahanKeluar->projek_rnd_id => 'Proyek RnD',
            (bool) $bahanKeluar->produk_sample_id => 'Produk Sample',
            (bool) $bahanKeluar->pengambilan_bahan_id => 'Pengambilan Bahan',
            default => $bahanKeluar->keterangan ?: '-',
        };

        return [
            'tujuan_flow' => $tujuan,
            'kode_tujuan' => $bahanKeluar->kode_transaksi ?? '-',
        ];
    }

    private function originFromSetengahJadiHeader($header): string
    {
        return match (true) {
            !$header => 'Tidak diketahui',
            (bool) $header->produk_sample_id => 'Produk Sample',
            (bool) $header->projek_rnd_id => 'Proyek RnD',
            (bool) $header->produksi_id => 'Produksi',
            (bool) $header->id_qc_produk_setengahjadi => 'QC Produk Setengah Jadi',
            default => 'Stok Produk Setengah Jadi',
        };
    }

    private function originFromProdukJadiHeader($header): string
    {
        return match (true) {
            !$header => 'Tidak diketahui',
            (bool) $header->produk_sample_id => 'Produk Sample',
            (bool) $header->produksi_produk_jadi_id => 'Produksi Produk Jadi',
            (bool) $header->id_qc_produk_jadi => 'QC Produk Jadi',
            default => 'Stok Produk Jadi',
        };
    }

    private function latestOutgoingForSetengahJadi(BahanSetengahjadiDetails $detail): ?BahanKeluarDetails
    {
        if (!$detail->exists) {
            return null;
        }

        return BahanKeluarDetails::with('bahanKeluar')
            ->where('produk_id', $detail->id)
            ->latest('id')
            ->first();
    }

    private function latestOutgoingForProdukJadi(ProdukJadiDetails $detail): ?BahanKeluarDetails
    {
        if (!$detail->exists) {
            return null;
        }

        return BahanKeluarDetails::with('bahanKeluar')
            ->where('produk_jadis_id', $detail->id)
            ->latest('id')
            ->first();
    }

    private function unknownSource(): array
    {
        return [
            'jenis_item' => 'Tidak diketahui',
            'kode_sumber' => '-',
            'asal_flow' => 'Tidak diketahui',
            'serial_number_flow' => '-',
        ];
    }

    private function flow(array $flow): array
    {
        return array_merge([
            'jenis_item' => 'Tidak diketahui',
            'kode_sumber' => '-',
            'asal_flow' => 'Tidak diketahui',
            'serial_number_flow' => '-',
            'tujuan_flow' => '-',
            'kode_tujuan' => '-',
            'status_flow' => '-',
        ], $flow);
    }

    private function relation($model, string $relation)
    {
        if (!$model || !method_exists($model, 'relationLoaded') || !$model->relationLoaded($relation)) {
            return null;
        }

        return $model->getRelation($relation);
    }
}
