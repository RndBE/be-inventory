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
        Schema::table('rekap_aset', function (Blueprint $table) {
            $table->unsignedBigInteger('pic_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('ruangan_id')->nullable()->after('pic_id');

            $table->foreign('pic_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('ruangan_id')->references('id')->on('ruangan')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_aset', function (Blueprint $table) {
            $table->dropForeign(['pic_id']);
            $table->dropForeign(['ruangan_id']);
            $table->dropColumn(['pic_id', 'ruangan_id']);
        });
    }
};
