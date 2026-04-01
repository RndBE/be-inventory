<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Membuat Pivot Table
        Schema::create('bahan_supplier', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bahan_id');
            $table->unsignedBigInteger('supplier_id');
            $table->timestamps();

            // Aturan Foreign Key (Opsional, tapi sangat disarankan)
            $table->foreign('bahan_id')->references('id')->on('bahan')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('supplier')->onDelete('cascade');
        });

        // 2. Memindahkan Data Lama
        // Kita ambil semua bahan yang memiliki supplier_id tidak null
        $bahans = DB::table('bahan')->whereNotNull('supplier_id')->get();
        
        $pivotData = [];
        $now = now();
        foreach ($bahans as $bahan) {
            $pivotData[] = [
                'bahan_id' => $bahan->id,
                'supplier_id' => $bahan->supplier_id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($pivotData)) {
            DB::table('bahan_supplier')->insert($pivotData);
        }

        // 3. Menghapus Kolom supplier_id dari tabel bahan
        Schema::table('bahan', function (Blueprint $table) {
            // Karena ini foreign key di Laravel, kadang kita perlu drop foreign indexnya dulu
            // asumsikan namanya bahan_supplier_id_foreign kalau diautogenerate.
            // Kita hilangkan dengan pengecekan aman (ini bergantung struktur asli Anda)
            // Namun jika sebelumnya tidak pakai constraint foreign key, drop column cukup.
        });
        
        // Coba drop foreign key jika exist, lalu drop column.
        Schema::table('bahan', function(Blueprint $table) {
            try {
                $table->dropForeign(['supplier_id']);
            } catch (\Exception $e) {
                // Biarkan jika foreign constraint tidak ada
            }
            $table->dropColumn('supplier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Mengembalikan kolom supplier_id ke tabel bahan
        Schema::table('bahan', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')->nullable();
            
            // Re-add foreign if needed
            // $table->foreign('supplier_id')->references('id')->on('supplier');
        });

        // 2. Mengisi kembali data dari pivot table ke tabel bahan
        // Karena sebelumnya 1 bahan bisa banyak supplier, saat di-down kita ambil supplier pertama saja
        $pivots = DB::table('bahan_supplier')->get();
        
        foreach ($pivots as $pivot) {
            // update bahan, jika belum ada isinya (mengambil supplier pertama yang nyantol)
            $currentData = DB::table('bahan')->where('id', $pivot->bahan_id)->first();
            if ($currentData && is_null($currentData->supplier_id)) {
                DB::table('bahan')->where('id', $pivot->bahan_id)->update([
                    'supplier_id' => $pivot->supplier_id
                ]);
            }
        }

        // 3. Menghapus Pivot Table
        Schema::dropIfExists('bahan_supplier');
    }
};
