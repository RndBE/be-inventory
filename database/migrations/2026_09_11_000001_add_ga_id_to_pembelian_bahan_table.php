<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Simpan siapa General Affair yang memproses pengajuan.
 *
 * Sampai sekarang `pembelian_bahan` hanya menyimpan `status_general_manager`
 * dan `tgl_approve_general_manager` — tanpa identitas approver. Nama & tanda
 * tangan GA di PDF karena itu dicari ulang setiap kali dokumen dicetak, dari
 * siapa pun yang saat itu memegang role `general_affair`.
 *
 * Akibatnya satu kali pergantian GA menulis ulang nama di SELURUH dokumen lama:
 * pengajuan yang dulu disetujui Widya ikut berganti nama begitu role pindah,
 * dan sebaliknya. Dokumen resmi yang isinya berubah sendiri karena mutasi
 * kepegawaian bukan dokumen.
 *
 * Kolom di sini yang membekukannya, diisi dari pengguna yang menekan tombol
 * persetujuan GA (PembelianBahanController::updateApprovalGM).
 *
 * Hanya tabel ini yang dapat kolomnya. Tabel induk `pengajuan` ikut menyalin
 * `status_general_manager`, tapi tidak punya tanggal approve sama sekali dan
 * tidak pernah dipakai mencetak PDF — kolom di sana hanya akan jadi data mati.
 *
 * Backfill memakai aturan yang sama dengan resolver berbasis tanggal yang
 * dipakai PDF sebelum ini, supaya tidak ada dokumen lama yang berubah isi saat
 * migration jalan: serah terima GA Widya -> Avissa berlaku 2026-07-31 WIB.
 * Hanya baris yang benar-benar sudah melewati tahap GA yang diisi; yang masih
 * menunggu sengaja dibiarkan null supaya tetap menampilkan GA yang menjabat
 * sekarang — dia yang harus tanda tangan, bukan pejabat lama.
 *
 * Tanpa foreign key, mengikuti `perbaikan_data.approver_id`: baris persetujuan
 * harus tetap terbaca walau usernya dihapus.
 *
 * Catatan kompatibilitas, sama seperti migration perbaikan data: introspeksi
 * skema Laravel 11 membaca kolom `generation_expression` yang belum ada di
 * MySQL/MariaDB versi server produksi, jadi pemeriksaan kolom memakai query
 * information_schema seadanya dan perubahannya memakai ALTER TABLE mentah.
 */
return new class extends Migration
{
    private const TABEL = 'pembelian_bahan';

    private const CUTOFF = '2026-07-31 00:00:00';

    private const GA_LAMA = 'WIDYA ANNISA RAHMAWATI';

    private const GA_BARU = 'AVISSA NOVA FAUZISTIKA';

    public function up(): void
    {
        if (! $this->punyaKolom(self::TABEL, 'ga_id')) {
            DB::statement('alter table `' . self::TABEL . '` add `ga_id` bigint unsigned null');
        }

        $this->backfill();
    }

    public function down(): void
    {
        if ($this->punyaKolom(self::TABEL, 'ga_id')) {
            DB::statement('alter table `' . self::TABEL . '` drop `ga_id`');
        }
    }

    /**
     * Isi ga_id untuk baris yang tahap GA-nya sudah diproses, memakai pembagian
     * tanggal yang sebelumnya dipakai saat mencetak PDF.
     */
    private function backfill(): void
    {
        $pembagian = [
            self::GA_BARU => ['>=', self::CUTOFF],
            self::GA_LAMA => ['<', self::CUTOFF],
        ];

        foreach ($pembagian as $nama => [$operator, $batas]) {
            $userId = DB::table('users')->where('name', $nama)->value('id');

            if (! $userId) {
                // Nama pejabat tidak ada di database ini (mis. dump parsial).
                // Biarkan null; PDF jatuh ke resolver berbasis tanggal.
                continue;
            }

            DB::table(self::TABEL)
                ->whereNull('ga_id')
                ->whereNotNull('tgl_approve_general_manager')
                ->where('status_general_manager', '!=', 'Belum disetujui')
                ->where('tgl_pengajuan', $operator, $batas)
                ->update(['ga_id' => $userId]);
        }
    }

    private function punyaKolom(string $tabel, string $kolom): bool
    {
        return (int) DB::selectOne(
            'select count(*) as jumlah from information_schema.columns
             where table_schema = database() and table_name = ? and column_name = ?',
            [$tabel, $kolom]
        )->jumlah > 0;
    }
};
