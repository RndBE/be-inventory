<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Surat penunjukan pelaksana untuk satu pengajuan perbaikan data.
 *
 * Tabel terpisah dari `perbaikan_data`, bukan kolom tambahan di sana, karena
 * penunjukan punya penulis, waktu, dan berkasnya sendiri. Kalau digabung,
 * kolom `form_pengajuan` dan `form_penunjukan` hidup di satu baris yang
 * pemiliknya berbeda: yang pertama milik pengaju, yang kedua milik pemegang
 * pintu perbaikan data. Menggabungkannya berarti satu-satunya cara membatasi
 * siapa boleh mengubah apa adalah menghafal kolom mana milik siapa.
 *
 * Satu pengajuan hanya boleh punya satu penunjukan — `perbaikan_data_id`
 * unique. Dua surat penunjukan atas satu pengajuan berarti dua orang mengaku
 * ditunjuk untuk pekerjaan yang sama, dan tidak ada aturan yang menentukan
 * mana yang berlaku. Penunjukan yang salah orang diperbaiki dengan mengubah
 * barisnya, bukan menerbitkan surat kedua.
 *
 * Kolom pelaksanaan (`tgl_pelaksanaan`, `nama_petugas`, `keterangan`) nullable
 * karena diisi belakangan oleh pelaksananya, bukan saat surat dibuat.
 * `nama_petugas` disimpan sebagai teks di samping `ditunjuk_user_id`: yang
 * ditunjuk lewat surat belum tentu yang benar-benar mengerjakan, dan surat
 * penunjukan yang dicetak harus menunjukkan keduanya apa adanya.
 *
 * Tanpa foreign key ke `users`. Baris ini bagian dari jejak audit; kalau user
 * yang menandatangani dihapus, suratnya harus tetap terbaca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perbaikan_data_penunjukan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('perbaikan_data_id')
                ->unique()
                ->constrained('perbaikan_data');

            $table->string('kode_penunjukan')->unique();

            // Pelaksana: yang ditunjuk mengubah datanya.
            $table->unsignedBigInteger('ditunjuk_user_id');
            // Pemegang pintu: yang menerbitkan surat dan mengunggah berkasnya.
            $table->unsignedBigInteger('ditunjuk_oleh_user_id')->nullable();

            $table->dateTime('tgl_penunjukan');
            $table->text('catatan_penunjukan')->nullable();
            $table->string('form_penunjukan')->nullable();

            // Diisi pelaksana setelah pekerjaannya dilakukan.
            $table->dateTime('tgl_pelaksanaan')->nullable();
            $table->string('nama_petugas')->nullable();
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('diisi_oleh_user_id')->nullable();

            // Ditunjuk               - surat terbit, pelaksanaan belum diisi
            // Sedang Dikerjakan      - pelaksana sudah mulai
            // Selesai                - pelaksanaan selesai
            // Tidak Dapat Dilaksanakan - pelaksana menolak, alasannya di keterangan
            $table->string('status')->default('Ditunjuk');

            $table->timestamps();

            $table->index('ditunjuk_user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perbaikan_data_penunjukan');
    }
};
