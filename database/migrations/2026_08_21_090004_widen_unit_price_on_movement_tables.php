<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Harga satuan di tabel pergerakan bahan diperlebar jadi decimal.
 *
 * Untuk bahan batangan, harga satuan ledger adalah harga per cm — pipa
 * Rp 175.000 per batang 600 cm menjadi 291,6667. Kolom integer akan
 * memotongnya jadi 291, dan pemotongan itu ikut terbawa: retur bahan dari
 * proyek membuat lot stok baru memakai harga dari barisnya sendiri, jadi
 * nilainya menyusut sekitar 0,23% setiap kali bahan berputar keluar-masuk.
 *
 * `bahan_retur_details.unit_price` tidak pernah dibuat lewat migration — kolom
 * itu ditambahkan langsung di database — sehingga tipenya tidak bisa
 * dipastikan dari berkas mana pun di repo ini. Karena itu setiap perubahan
 * dijaga dengan hasColumn, dan tabel yang kolomnya tidak ada dilewati saja.
 *
 * Kolomnya dijadikan nullable karena alasan yang sama: nullability aslinya
 * tidak diketahui, dan `change()` di Laravel 11 menulis ulang seluruh definisi
 * kolom — kalau di sini ditulis NOT NULL sedangkan aslinya nullable, baris lama
 * yang harganya kosong akan menggagalkan migration di tengah jalan. Melonggarkan
 * ke nullable tidak pernah merusak baris maupun insert yang sudah ada.
 */
return new class extends Migration
{
    private const TABEL = [
        'bahan_retur_details',
        'bahan_rusak_details',
    ];

    public function up(): void
    {
        foreach (self::TABEL as $tabel) {
            if (! Schema::hasTable($tabel) || ! Schema::hasColumn($tabel, 'unit_price')) {
                continue;
            }

            Schema::table($tabel, function (Blueprint $table) {
                $table->decimal('unit_price', 15, 4)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABEL as $tabel) {
            if (! Schema::hasTable($tabel) || ! Schema::hasColumn($tabel, 'unit_price')) {
                continue;
            }

            Schema::table($tabel, function (Blueprint $table) {
                $table->integer('unit_price')->nullable()->change();
            });
        }
    }
};
