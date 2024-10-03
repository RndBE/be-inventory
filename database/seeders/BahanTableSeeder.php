<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BahanTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['kode_bahan' => 'BHN001', 'nama_bahan' => 'Baterai Alkaline A2', 'jenis_bahan_id' => 4, 'stok_awal' => 100, 'unit_id' => 1, 'kondisi' => 'Baik', 'penempatan' => 'Rak 1', 'gambar' => null],
            ['kode_bahan' => 'BHN002', 'nama_bahan' => 'Baterai Alkaline A3', 'jenis_bahan_id' => 4, 'stok_awal' => 150, 'unit_id' => 1, 'kondisi' => 'Baik', 'penempatan' => 'Rak 1', 'gambar' => null],
            ['kode_bahan' => 'BHN003', 'nama_bahan' => 'Baterai A9V', 'jenis_bahan_id' => 4, 'stok_awal' => 200, 'unit_id' => 1, 'kondisi' => 'Baik', 'penempatan' => 'Rak 2', 'gambar' => null],
            ['kode_bahan' => 'BHN004', 'nama_bahan' => 'Baterai Holder SMD', 'jenis_bahan_id' => 4, 'stok_awal' => 50, 'unit_id' => 1, 'kondisi' => 'Baik', 'penempatan' => 'Rak 2', 'gambar' => null],
            ['kode_bahan' => 'BHN005', 'nama_bahan' => 'Baterai LR44 A76', 'jenis_bahan_id' => 4, 'stok_awal' => 80, 'unit_id' => 1, 'kondisi' => 'Baik', 'penempatan' => 'Rak 3', 'gambar' => null],
            ['kode_bahan' => 'BHN006', 'nama_bahan' => 'Baterai CR 2032', 'jenis_bahan_id' => 4, 'stok_awal' => 120, 'unit_id' => 1, 'kondisi' => 'Baik', 'penempatan' => 'Rak 3', 'gambar' => null],
            ['kode_bahan' => 'BHN007', 'nama_bahan' => 'IC Regulator 78M05', 'jenis_bahan_id' => 5, 'stok_awal' => 200, 'unit_id' => 1, 'kondisi' => 'Baik', 'penempatan' => 'Rak 4', 'gambar' => null],
            ['kode_bahan' => 'BHN008', 'nama_bahan' => 'KIA 78D33FTO 252 B067', 'jenis_bahan_id' => 5, 'stok_awal' => 150, 'unit_id' => 1, 'kondisi' => 'Baik', 'penempatan' => 'Rak 4', 'gambar' => null],
            ['kode_bahan' => 'BHN009', 'nama_bahan' => 'Capasitor 30PF 1206', 'jenis_bahan_id' => 6, 'stok_awal' => 300, 'unit_id' => 1, 'kondisi' => 'Baik', 'penempatan' => 'Rak 5', 'gambar' => null],
            ['kode_bahan' => 'BHN010', 'nama_bahan' => 'Capasitor 10UF/1KV SMD', 'jenis_bahan_id' => 6, 'stok_awal' => 250, 'unit_id' => 1, 'kondisi' => 'Baik', 'penempatan' => 'Rak 5', 'gambar' => null],
            ['kode_bahan' => 'BHN011', 'nama_bahan' => 'Capasitor 470UF/35V', 'jenis_bahan_id' => 6, 'stok_awal' => 200, 'unit_id' => 1, 'kondisi' => 'Baik', 'penempatan' => 'Rak 5', 'gambar' => null],
            ['kode_bahan' => 'BHN012', 'nama_bahan' => 'Capasitor 1000UF/25V', 'jenis_bahan_id' => 6, 'stok_awal' => 180, 'unit_id' => 1, 'kondisi' => 'Baik', 'penempatan' => 'Rak 5', 'gambar' => null],
            ['kode_bahan' => 'BHN013', 'nama_bahan' => 'Capit Buaya Merah Hitam', 'jenis_bahan_id' => 7, 'stok_awal' => 150, 'unit_id' => 1, 'kondisi' => 'Baik', 'penempatan' => 'Rak 6', 'gambar' => null],
            ['kode_bahan' => 'BHN014', 'nama_bahan' => 'Jack DC Male', 'jenis_bahan_id' => 8, 'stok_awal' => 100, 'unit_id' => 1, 'kondisi' => 'Baik', 'penempatan' => 'Rak 7', 'gambar' => null],
            ['kode_bahan' => 'BHN015', 'nama_bahan' => 'ESP 32 Camera Duel Core 2', 'jenis_bahan_id' => 36, 'stok_awal' => 50, 'unit_id' => 1, 'kondisi' => 'Baik', 'penempatan' => 'Rak 8', 'gambar' => null],
        ];

        DB::table('bahan')->insert($data);
    }
}
