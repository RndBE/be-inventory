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
        Schema::create('qc_1_produk_setengah_jadi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_produk_setengah_jadi_list')
                ->constrained('qc_produk_setengah_jadi_list')
                ->cascadeOnDelete();

            $table->enum('grade', ['A', 'B'])->nullable();
            $table->string('laporan_qc')->nullable(); // simpan path file
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qc_1_produk_setengah_jadi');
    }
};
