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
        Schema::table('pengajuan_details', function (Blueprint $table) {
            $table->text('new_details')->nullable()->after('sub_total');
            $table->integer('new_sub_total')->nullable()->after('new_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_details', function (Blueprint $table) {
            $table->dropColumn('new_details');
            $table->dropColumn('new_sub_total');
        });
    }
};
