<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class JenisBahanTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jenisBahan = [
            'BATERAI','REGULATOR','CAPASITOR','CAPIT',
            'JACK','ESP 32','DIODA','FUSE','FERRITE','FLUX',
            'MOSFET','INDUCTOR','RESISTOR',
            'DB9','CONNECTOR','KABEL','LED','LCD','MINI USB','BOR','MAGNET','ARDUINO','MODUL SIM','PUSH BUTTON','PCB','PIN DERET',
            'PIN JUMPER','PTC','PIN HEADER','RTC (Real Time Clock)',
            'OSKILATOR','KABEL','IC (INTERGRATED CIRCUIT)','SOLAR CHARGER',
            'RESISTOR','RELAY','SD CARD','USB','BAUT','LAMP','PHOENIX','WAGO','SOLDER','ROUTER','TAKACHI',
            'TAPE','SENSOR','KONEKTOR','TRANSISTOR','PORT','TENOL','Kabel','INDIKATOR','SKUN','Terminal Block','MODUL','ETERNET',
            'LAIN LAIN','BAUT','SKRUP','RAK D.4','MUR','CONVERTER','OSILATOR','SWITCH',
            'ANTENA','MOUSE','WAGO TUTUP','AWGC','BRACKET','ALAT UKUR','MCB',
            'PCB','besi','ATK','Modul','Dioda','BAHAN INSTALASI','Powe Supply','Modem','SEKRUP','SAFETY','TACT SWITCH',
        ];

        foreach ($jenisBahan as $nama) {
            DB::table('jenis_bahan')->insert([
                'nama' => $nama,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
