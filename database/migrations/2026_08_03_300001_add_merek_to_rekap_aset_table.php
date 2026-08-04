<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Merek/tipe aset, untuk kolom "Merek/Tipe" pada Berita Acara Serah Terima.
 *
 * Ditaruh di rekap_aset, bukan di barang_aset: satu nama barang di katalog bisa
 * berisi unit dari merek yang berbeda-beda — "Kursi Direktur" yang dibeli tahun
 * lalu belum tentu merek yang sama dengan yang dibeli sekarang. Tempatnya jadi
 * berdampingan dengan serial_number, yang juga sifat per unit.
 *
 * Nullable: 43 aset yang sudah tercatat belum punya isinya, dan dokumen mencetak
 * '-' sampai diisi. Memaksanya wajib akan mengunci form edit untuk semua aset lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekap_aset', function (Blueprint $table) {
            $table->string('merek')->nullable()->after('serial_number');
        });
    }

    public function down(): void
    {
        Schema::table('rekap_aset', function (Blueprint $table) {
            $table->dropColumn('merek');
        });
    }
};
