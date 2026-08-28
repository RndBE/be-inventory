<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ledger stok siap menyimpan bahan batangan dalam cm.
 *
 * Dua perubahan, dan keduanya saling bergantung:
 *
 * `panjang_standar` — salinan panjang satu batang saat lot dibuat. Dipakai
 * untuk menerjemahkan angka cm kembali jadi batang di tampilan. Nilainya
 * dibekukan di baris lot, bukan dibaca dari tabel `bahan`, supaya satu kali
 * orang mengedit panjang standar (mis. pipa 6 m diganti 4 m) tidak mengubah
 * arti seluruh lot lama sekaligus.
 *
 * `unit_price` diperlebar jadi empat angka desimal. Untuk bahan batangan yang
 * qty-nya cm, harga yang tersimpan adalah harga per cm, jadi pipa Rp 175.000
 * per batang 600 cm tercatat 291,6667. Alternatifnya menyimpan harga per batang
 * dan memprorata di tiap tempat pemakaian, tapi `qty * unit_price` tersebar di
 * puluhan titik (BahanKeluarController saja belasan) dan semuanya akan salah
 * 600 kali kalau harganya per batang sedangkan qty-nya cm. Dengan harga per cm,
 * seluruh perhitungan lama tetap benar tanpa disentuh.
 *
 * Empat angka desimal dipilih supaya galat pembulatannya tinggal sekitar
 * Rp 0,02 per batang. Subtotal pembelian sendiri tidak dihitung dari harga per
 * cm melainkan dari jumlah batang dikali harga per batang, jadi angka yang
 * masuk pembukuan tetap eksak.
 *
 * Catatan tipe asal: migration pembuatan tabel menulis `integer`, tapi di
 * database produksi kolomnya sudah decimal(20,2) — pernah diubah langsung, di
 * luar migration. Jadi perubahan ini menukar dua digit bulat (18 jadi 11)
 * dengan dua digit desimal. Harga satuan tertinggi yang tercatat masih delapan
 * digit, jauh di bawah batas baru, sehingga tidak ada baris yang terpotong.
 * Lihat down() untuk konsekuensinya saat rollback.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_details', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_details', 'panjang_standar')) {
                $table->integer('panjang_standar')->nullable()->after('bahan_id');
            }
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 4)->change();
        });
    }

    /**
     * Dikembalikan ke decimal(20,2), bukan ke integer seperti tertulis di
     * migration pembuatan tabelnya.
     *
     * Tipe di database produksi sudah decimal(20,2) — kolomnya pernah diubah
     * langsung, di luar migration — jadi `integer` di sini bukan mengembalikan
     * keadaan semula melainkan memotong desimal setiap baris, termasuk nilai
     * sen yang sudah tercatat jauh sebelum fitur ini ada.
     *
     * Dua angka desimal yang tersisa tetap hilang untuk bahan batangan, karena
     * harga per cm memang butuh empat. Rollback ini hanya aman kalau belum ada
     * lot batangan yang tercatat; kalau sudah, kembalikan dari backup.
     */
    public function down(): void
    {
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->decimal('unit_price', 20, 2)->change();
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropColumn('panjang_standar');
        });
    }
};
