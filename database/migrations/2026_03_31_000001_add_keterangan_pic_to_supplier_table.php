<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('supplier', function (Blueprint $table) {
            $table->text('keterangan')->nullable()->comment('Keterangan nama barang/jasa yang disuplai');
            $table->string('nama_pic')->nullable()->comment('Nama Person In Contact (PIC) supplier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier', function (Blueprint $table) {
            $table->dropColumn('keterangan');
            $table->dropColumn('nama_pic');
        });
    }
};
