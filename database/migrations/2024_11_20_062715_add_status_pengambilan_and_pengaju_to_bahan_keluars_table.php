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
        Schema::table('bahan_keluars', function (Blueprint $table) {
            $table->foreignId('pengaju')->nullable()->after('divisi')->constrained('users')->onDelete('set null')->onUpdate('cascade');
            $table->string('status_pengambilan')->nullable()->after('pengaju');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan_keluars', function (Blueprint $table) {
            $table->dropForeign(['pengaju']);
            $table->dropColumn('pengaju');
            $table->dropColumn('status_pengambilan');
        });
    }
};
