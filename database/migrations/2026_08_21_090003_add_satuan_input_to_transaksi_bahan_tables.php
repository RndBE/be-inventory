<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak satuan yang dipilih user saat menginput transaksi bahan.
 *
 * Kolom `qty` di tabel-tabel ini tetap satu-satunya sumber kebenaran dan
 * selalu dalam satuan dasar (cm untuk bahan batangan). Dua kolom di sini hanya
 * merekam apa yang diketik orangnya — "5 batang" atau "40 cm" — supaya
 * tampilan riwayat dan cetakan bisa menampilkan angka yang sama dengan yang
 * dimasukkan, bukan hasil konversinya. Tidak ada perhitungan stok yang boleh
 * mengambil angka dari sini.
 *
 * Keduanya nullable karena baris lama tidak punya nilainya, dan bahan
 * non-batangan tidak perlu mengisinya.
 */
return new class extends Migration
{
    /**
     * Tabel yang diubah, dan kolom acuan posisinya.
     *
     * Posisi kolom tidak memengaruhi apa pun secara fungsional, tapi
     * menempatkannya di sebelah kolom qty terkait membuat struktur tabelnya
     * lebih mudah dibaca saat ditelusuri langsung di database.
     */
    private const TABEL = [
        'pembelian_bahan_details' => 'qty',
        'bahan_keluar_details' => 'qty',
        'pengambilan_bahan_details' => 'qty',
        'bahan_retur_details' => 'qty',
        'bahan_rusak_details' => 'qty',
        'stock_opname_details' => 'tersedia_fisik',
        'qc_bahan_masuk_details' => 'fisik_baik',
    ];

    public function up(): void
    {
        foreach (self::TABEL as $tabel => $setelah) {
            if (! Schema::hasTable($tabel)) {
                continue;
            }

            Schema::table($tabel, function (Blueprint $table) use ($tabel, $setelah) {
                if (! Schema::hasColumn($tabel, 'qty_input')) {
                    $table->decimal('qty_input', 15, 2)->nullable()->after($setelah);
                }

                if (! Schema::hasColumn($tabel, 'satuan_input')) {
                    $table->string('satuan_input', 20)->nullable()->after($setelah);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABEL) as $tabel) {
            if (! Schema::hasTable($tabel)) {
                continue;
            }

            Schema::table($tabel, function (Blueprint $table) use ($tabel) {
                $kolom = array_values(array_filter(
                    ['qty_input', 'satuan_input'],
                    fn ($nama) => Schema::hasColumn($tabel, $nama)
                ));

                if ($kolom) {
                    $table->dropColumn($kolom);
                }
            });
        }
    }
};
