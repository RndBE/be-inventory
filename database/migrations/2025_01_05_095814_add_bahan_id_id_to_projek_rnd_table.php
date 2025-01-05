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
        Schema::table('projek_rnd', function (Blueprint $table) {
            $table->foreignId('bahan_id')->nullable()->constrained('bahan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projek_rnd', function (Blueprint $table) {
            $table->dropForeign(['bahan_id']);
            $table->dropColumn('bahan_id');
        });
    }
};
