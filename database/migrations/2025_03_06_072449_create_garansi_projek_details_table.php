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
        Schema::create('garansi_projek_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garansi_projek_id')->constrained('garansi_projek')->onDelete('cascade');
            $table->unsignedBigInteger('bahan_id')->nullable();
            $table->unsignedBigInteger('produk_id')->nullable();
            $table->integer('qty')->nullable();
            $table->integer('jml_bahan')->nullable();
            $table->integer('used_materials')->nullable();
            $table->text('details')->nullable();
            $table->decimal('sub_total', 10, 2)->nullable();
            $table->string('serial_number')->nullable();
            $table->timestamps();


            $table->foreign('bahan_id')->references('id')->on('bahan')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('produk_id')->references('id')->on('bahan_setengahjadi_details')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garansi_projek_details');
    }
};
