<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bukti foto pengembalian dipindah dari satu kolom teks ke tabel tersendiri.
 *
 * Satu kali serah terima bisa melibatkan banyak aset, dan satu foto sering tidak
 * cukup untuk mendokumentasikan semuanya. Kolom string lama hanya sanggup
 * menampung satu path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman_aset_bukti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peminjaman_aset_detail_id')
                ->constrained('peminjaman_aset_details')
                ->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
        });

        // Pindahkan foto yang sudah tercatat supaya tidak ada bukti yang hilang.
        $lama = DB::table('peminjaman_aset_details')
            ->whereNotNull('bukti_foto')
            ->where('bukti_foto', '!=', '')
            ->get(['id', 'bukti_foto']);

        foreach ($lama as $baris) {
            DB::table('peminjaman_aset_bukti')->insert([
                'peminjaman_aset_detail_id' => $baris->id,
                'path' => $baris->bukti_foto,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('peminjaman_aset_details', function (Blueprint $table) {
            $table->dropColumn('bukti_foto');
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman_aset_details', function (Blueprint $table) {
            $table->string('bukti_foto')->nullable()->after('kondisi_kembali');
        });

        // Hanya foto pertama tiap detail yang bisa dikembalikan — kolom lama
        // memang tidak sanggup menampung lebih dari satu.
        $pertama = DB::table('peminjaman_aset_bukti')
            ->select('peminjaman_aset_detail_id', DB::raw('MIN(path) as path'))
            ->groupBy('peminjaman_aset_detail_id')
            ->get();

        foreach ($pertama as $baris) {
            DB::table('peminjaman_aset_details')
                ->where('id', $baris->peminjaman_aset_detail_id)
                ->update(['bukti_foto' => $baris->path]);
        }

        Schema::dropIfExists('peminjaman_aset_bukti');
    }
};
