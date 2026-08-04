<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RuanganTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ruangan = [
            ['kode_ruangan' => 'RG-DIR',    'nama_ruangan' => 'Ruang Direksi',                   'keterangan' => null],
            ['kode_ruangan' => 'RG-FAT',    'nama_ruangan' => 'Ruang FAT',                       'keterangan' => 'Finance, Accounting, Tax'],
            ['kode_ruangan' => 'RG-SW',     'nama_ruangan' => 'Ruang Software',                  'keterangan' => null],
            ['kode_ruangan' => 'RG-HRD',    'nama_ruangan' => 'Ruang HRD & Corporate Service',   'keterangan' => null],
            ['kode_ruangan' => 'RG-MKT',    'nama_ruangan' => 'Ruang Marketing',                 'keterangan' => null],
            ['kode_ruangan' => 'RG-MEET',   'nama_ruangan' => 'Ruang Meeting',                   'keterangan' => null],
            ['kode_ruangan' => 'RG-TAMU',   'nama_ruangan' => 'Ruang Tamu',                      'keterangan' => null],
            ['kode_ruangan' => 'RG-PROD',   'nama_ruangan' => 'Ruang Produksi',                  'keterangan' => null],
            ['kode_ruangan' => 'RG-RND',    'nama_ruangan' => 'Ruang RND',                       'keterangan' => null],
            ['kode_ruangan' => 'RG-GUDANG', 'nama_ruangan' => 'Ruang Supply Chain',              'keterangan' => null],
            ['kode_ruangan' => 'RG-MGRHW',  'nama_ruangan' => 'Ruang Manager Hardware',          'keterangan' => null],
        ];

        // updateOrInsert supaya seeder aman dijalankan berulang (kode_ruangan unique).
        foreach ($ruangan as $row) {
            DB::table('ruangan')->updateOrInsert(
                ['kode_ruangan' => $row['kode_ruangan']],
                [
                    'nama_ruangan' => $row['nama_ruangan'],
                    'keterangan' => $row['keterangan'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
