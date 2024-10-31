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
        Schema::table('bahan_retur', function (Blueprint $table) {
            $table->foreignId('projek_rnd_id')->nullable()->constrained('projek_rnd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan_retur', function (Blueprint $table) {
            $table->dropForeign(['projek_rnd_id']);
            $table->dropColumn('projek_rnd_id');
        });
    }
};
