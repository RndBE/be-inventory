<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bahan_setengahjadis', function (Blueprint $table) {
            $table->foreignId('produk_sample_id')->nullable()->after('projek_rnd_id')
                  ->constrained('produk_sample')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('bahan_setengahjadis', function (Blueprint $table) {
            $table->dropForeign(['produk_sample_id']);
            $table->dropColumn('produk_sample_id');
        });
    }
};
