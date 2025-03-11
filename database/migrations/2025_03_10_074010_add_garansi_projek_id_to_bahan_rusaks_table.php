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
        Schema::table('bahan_rusaks', function (Blueprint $table) {
            $table->foreignId('garansi_projek_id')->nullable()->after('projek_id')->constrained('garansi_projek');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan_rusaks', function (Blueprint $table) {
            $table->dropForeign(['garansi_projek_id']);
            $table->dropColumn('garansi_projek_id');
        });
    }
};
