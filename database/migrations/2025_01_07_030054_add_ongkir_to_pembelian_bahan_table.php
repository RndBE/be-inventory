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
        Schema::table('pembelian_bahan', function (Blueprint $table) {
            $table->enum('status_general_manager', ['Belum disetujui', 'Disetujui', 'Ditolak'])->default('Belum disetujui');
            $table->string('jenis_pengajuan')->nullable();
            $table->integer('ongkir')->nullable();
            $table->integer('asuransi')->nullable();
            $table->integer('layanan')->nullable();
            $table->integer('jasa_aplikasi')->nullable();
            $table->float('shipping_cost')->nullable();
            $table->float('full_amount_fee')->nullable();
            $table->float('value_today_fee')->nullable();
            $table->float('shipping_cost_usd')->nullable();
            $table->float('full_amount_fee_usd')->nullable();
            $table->float('value_today_fee_usd')->nullable();
            $table->float('new_shipping_cost')->nullable();
            $table->float('new_full_amount_fee')->nullable();
            $table->float('new_value_today_fee')->nullable();
            $table->float('new_shipping_cost_usd')->nullable();
            $table->float('new_full_amount_fee_usd')->nullable();
            $table->float('new_value_today_fee_usd')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembelian_bahan', function (Blueprint $table) {
            $table->dropColumn('status_general_manager');
            $table->dropColumn('jenis_pengajuan');
            $table->dropColumn('ongkir');
            $table->dropColumn('asuransi');
            $table->dropColumn('layanan');
            $table->dropColumn('jasa_aplikasi');
            $table->dropColumn('shipping_cost');
            $table->dropColumn('full_amount_fee');
            $table->dropColumn('value_today_fee');
            $table->dropColumn('shipping_cost_usd');
            $table->dropColumn('full_amount_fee_usd');
            $table->dropColumn('value_today_fee_usd');
            $table->dropColumn('new_shipping_cost');
            $table->dropColumn('new_full_amount_fee');
            $table->dropColumn('new_value_today_fee');
            $table->dropColumn('new_shipping_cost_usd');
            $table->dropColumn('new_full_amount_fee_usd');
            $table->dropColumn('new_value_today_fee_usd');
        });
    }
};
