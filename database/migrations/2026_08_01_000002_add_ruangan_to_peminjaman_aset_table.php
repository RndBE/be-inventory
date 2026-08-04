<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ruangan tujuan peminjaman aset.
     */
    public function up(): void
    {
        Schema::table('peminjaman_aset', function (Blueprint $table) {
            $table->unsignedBigInteger('ruangan_id')->nullable()->after('divisi');
            $table->foreign('ruangan_id')->references('id')->on('ruangan')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman_aset', function (Blueprint $table) {
            $table->dropForeign(['ruangan_id']);
            $table->dropColumn('ruangan_id');
        });
    }
};
