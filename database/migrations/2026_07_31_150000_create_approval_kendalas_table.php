<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_kendalas', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->unsignedBigInteger('module_id');
            $table->string('approval_role');
            $table->string('status')->nullable();
            $table->text('kendala')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->unique(['module', 'module_id', 'approval_role'], 'approval_kendalas_unique_role');
            $table->index(['module', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_kendalas');
    }
};
