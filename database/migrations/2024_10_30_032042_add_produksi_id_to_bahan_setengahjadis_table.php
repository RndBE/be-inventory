<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProduksiIdToBahanSetengahjadisTable  extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('bahan_setengahjadis', function (Blueprint $table) {
            $table->foreignId('produksi_id')->nullable()->constrained('produksis');
        });
    }

    public function down()
    {
        Schema::table('bahan_setengahjadis', function (Blueprint $table) {
            $table->dropForeign(['produksi_id']);
            $table->dropColumn('produksi_id');
        });
    }
};
