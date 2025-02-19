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
    private $rowNumber = 1; // Untuk melacak nomor baris

    /**
    * @param array $row
    * @return \Illuminate\Database\Eloquent\Model|null
    * @throws \Exception
    */
    public function model(array $row)
    {
        $this->rowNumber++; // Increment nomor baris setiap kali model diproses

        // Validasi nomor aset duplikat dalam Excel
        static $nomorAsetCache = [];
        if (in_array($row['nomor_aset'], $nomorAsetCache)) {
            throw new \Exception("Error pada kolom 'nomor_aset' di baris {$this->rowNumber} Excel: Nomor aset duplikat pada file Excel.");
        }
        $nomorAsetCache[] = $row['nomor_aset'];

        // Validasi nomor aset duplikat di database
        $existingAset = RekapAset::where('nomor_aset', $row['nomor_aset'])->first();
        if ($existingAset) {
            throw new \Exception("Error pada kolom 'nomor_aset' di baris {$this->rowNumber} Excel: Nomor aset sudah ada di database.");
        }

        // Cari barang_aset_id berdasarkan nama aset
        $barangAset = BarangAset::where('nama_barang', $row['nama_aset'])->first();
        $user = User::whereRaw('LOWER(name) = ?', [strtolower($row['nama_penanggungjawab'])])->first();

        // Konversi tanggal dari Excel ke format yang sesuai
        $tgl_perolehan = null;
        if (!empty($row['tanggal_perolehan'])) {
            try {
                $tgl_perolehan = Date::excelToDateTimeObject($row['tanggal_perolehan'])->format('Y-m-d');
            } catch (\Exception $e) {
                throw new \Exception("Error pada kolom 'tanggal_perolehan' di baris {$this->rowNumber} Excel: Nilai tidak valid.");
            }
        }

        // Konversi harga perolehan ke format numerik
        $harga_perolehan = null;
        if (!empty($row['harga_perolehan'])) {
            $harga_perolehan = floatval(str_replace([',', '.'], ['', '.'], $row['harga_perolehan']));
        }

        // Validasi null untuk barang_aset_id
        if (!$barangAset) {
            throw new \Exception("Error pada kolom 'nama_aset' di baris {$this->rowNumber} Excel: Nilai tidak ditemukan dalam database.");
        }

        // Validasi null untuk user_id
        if (!$user) {
            throw new \Exception("Error pada kolom 'nama_penanggungjawab' di baris {$this->rowNumber} Excel: Nilai tidak ditemukan dalam database.");
        }

        return new RekapAset([
            'nomor_aset'       => $row['nomor_aset'],
            'barang_aset_id'   => $barangAset->id,
            'link_gambar'      => $row['link_gambar'] ?? null,
            'tgl_perolehan'    => $tgl_perolehan,
            'jumlah_aset'      => $row['jumlah_aset'] ?? 0,
            'harga_perolehan'  => $harga_perolehan,
            'kondisi'          => $row['kondisi_aset'],
            'keterangan'       => $row['keterangan'] ?? null,
            'user_id'          => $user->id,
        ]);
    }
}

