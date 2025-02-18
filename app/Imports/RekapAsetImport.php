<?php

namespace App\Imports;

use App\Models\User;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Models\RekapAset;
use App\Models\BarangAset;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RekapAsetImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Cari barang_aset_id berdasarkan nama aset
        $barangAset = BarangAset::where('nama_barang', $row['nama_aset'])->first();

        // dd($row['nama_aset']);
        // Cari user_id berdasarkan nama penanggung jawab (tidak case sensitive)
        $user = User::whereRaw('LOWER(name) = ?', [strtolower($row['nama_penanggungjawab'])])->first();

        // Konversi tanggal dari Excel ke format yang sesuai
        $tgl_perolehan = null;
        if (!empty($row['tanggal_perolehan'])) {
            try {
                $tgl_perolehan = Date::excelToDateTimeObject($row['tanggal_perolehan'])->format('Y-m-d');
            } catch (\Exception $e) {
                $tgl_perolehan = null;
            }
        }

        // Konversi harga perolehan ke format numerik
        $harga_perolehan = null;
        if (!empty($row['harga_perolehan'])) {
            $harga_perolehan = floatval(str_replace([',', '.'], ['', '.'], $row['harga_perolehan']));
        }

        return new RekapAset([
            'nomor_aset'       => $row['nomor_aset'],
            'barang_aset_id'   => $barangAset ? $barangAset->id : null,
            'link_gambar'      => $row['link_gambar'] ?? null,
            'tgl_perolehan'    => $tgl_perolehan,
            'jumlah_aset'      => $row['jumlah_aset'] ?? 0,
            'harga_perolehan'  => $harga_perolehan,
            'kondisi'          => $row['kondisi_aset'],
            'keterangan'       => $row['keterangan'] ?? null,
            'user_id'          => $user ? $user->id : null,
        ]);
    }
}
