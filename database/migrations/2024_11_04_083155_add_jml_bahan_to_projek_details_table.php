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
        Schema::table('projek_details', function (Blueprint $table) {
            $table->integer('jml_bahan')->nullable()->after('qty');
            $table->integer('used_materials')->nullable()->after('jml_bahan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projek_details', function (Blueprint $table) {
            $table->dropColumn('jml_bahan');
            $table->dropColumn('used_materials');
        });
    }
};
