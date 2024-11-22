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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('name')->constrained('organization')->onDelete('set null')->onUpdate('cascade');
            $table->foreignId('job_position_id')->nullable()->after('organization_id')->constrained('job_position')->onDelete('set null')->onUpdate('cascade');
            $table->integer('job_level')->nullable()->after('job_position_id');
            $table->string('telephone')->nullable()->after('email');
            $table->string('tanda_tangan')->nullable()->after('telephone');
            $table->foreignId('atasan_level1_id')->nullable()->constrained('users')->after('tanda_tangan')->onDelete('set null')->onUpdate('cascade');
            $table->foreignId('atasan_level2_id')->nullable()->constrained('users')->after('atasan_level1_id')->onDelete('set null')->onUpdate('cascade');
            $table->foreignId('atasan_level3_id')->nullable()->constrained('users')->after('atasan_level2_id')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
            $table->dropForeign(['job_position_id']);
            $table->dropColumn('job_position_id');
            $table->dropColumn('job_level');
            $table->dropColumn('telephone');
            $table->dropColumn('tanda_tangan');
            $table->dropForeign(['atasan_level1_id']);
            $table->dropColumn('atasan_level1_id');
            $table->dropForeign(['atasan_level2_id']);
            $table->dropColumn('atasan_level2_id');
            $table->dropForeign(['atasan_level3_id']);
            $table->dropColumn('atasan_level3_id');
        });
    }
};
