<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak perubahan data yang dilakukan lewat aplikasi, per kolom.
 *
 * `log_activities` tidak bisa dipakai untuk ini: isinya pesan teks bebas tanpa
 * referensi ke record mana pun, jadi pertanyaan "kolom ini pernah diubah dari
 * berapa, oleh siapa" tidak punya jawaban di sana. Tabel ini menyimpan nilai
 * sebelum dan sesudah per kolom, beserta siapa yang mengajukan, menyetujui, dan
 * mengeksekusinya.
 *
 * Sengaja tanpa `updated_at`: barisnya tidak pernah diubah setelah ditulis.
 * AuditPerubahanData menolak update dan delete di tingkat model, dan satu-satunya
 * jalan tulis adalah AuditPerubahanDataService. Kalau nanti diinginkan penjagaan
 * yang tidak bisa dilewati sama sekali, tambahkan trigger MySQL BEFORE UPDATE dan
 * BEFORE DELETE yang memanggil SIGNAL SQLSTATE — itu butuh hak akses TRIGGER di
 * server produksi, jadi tidak dilakukan dari migration ini.
 *
 * Kolom pengguna dan `perbaikan_data_id` tidak diberi foreign key. Baris audit
 * harus tetap hidup walau user atau tiket pengajuannya dihapus; jejak yang bisa
 * ikut terhapus bersama subjeknya bukan jejak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_perubahan_data', function (Blueprint $table) {
            $table->id();

            // Boleh kosong supaya koreksi darurat tanpa tiket tetap tercatat.
            // Kalau kolom ini dipaksa wajib, jalur daruratnya akan pindah ke
            // penyuntingan database langsung dan hilang dari tabel ini.
            $table->unsignedBigInteger('perbaikan_data_id')->nullable();

            $table->string('modul');
            $table->unsignedBigInteger('modul_id');

            // Diisi kalau yang dikoreksi baris detail, bukan baris induk.
            $table->string('tabel_target')->nullable();
            $table->unsignedBigInteger('baris_target_id')->nullable();

            $table->string('field');
            $table->text('nilai_lama')->nullable();
            $table->text('nilai_baru')->nullable();

            $table->text('alasan');

            $table->unsignedBigInteger('pengaju_id')->nullable();
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->unsignedBigInteger('eksekutor_id')->nullable();

            // Benar kalau pengaju dan eksekutornya orang yang sama. Tidak
            // dilarang — melarangnya akan mendorong orang kembali ke jalur
            // database langsung — tapi harus terlihat di halaman audit.
            $table->boolean('diajukan_sendiri')->default(false);

            $table->string('ip_address')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['modul', 'modul_id']);
            $table->index('created_at');
            $table->index('eksekutor_id');
            $table->index('perbaikan_data_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_perubahan_data');
    }
};
