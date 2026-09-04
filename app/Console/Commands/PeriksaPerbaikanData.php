<?php

namespace App\Console\Commands;

use App\Exceptions\PerbaikanDataDitolak;
use App\Services\PerbaikanDataService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Periksa config/perbaikan_data.php terhadap database yang sebenarnya.
 *
 * Daftar kolom modul Perbaikan Data ditulis tangan, dikurasi dari apa yang
 * tampil di layar tiap modul. Sebelumnya daftar itu dibaca langsung dari skema,
 * dan selama itu berlaku kolom yang tidak ada mustahil masuk daftar — skema
 * yang jadi penjaganya.
 *
 * Penjaga itu sekarang tidak ada. Salah ketik satu nama kolom di config
 * menghasilkan pilihan yang tampak wajar di dropdown dan baru meledak saat ada
 * yang memilihnya, yaitu di tengah pencatatan koreksi — momen paling buruk
 * untuk error, karena yang mengerjakannya sedang menutup tiket yang sudah
 * disetujui.
 *
 * Test unit tidak bisa menangkapnya: suite jalan di sqlite tanpa satu pun tabel
 * ini. Yang bisa hanya pemeriksaan terhadap database hidup, dan itu tugas
 * perintah ini. Jalankan sebelum deploy, atau setiap kali config-nya disunting.
 *
 * Yang diperiksa per modul:
 *
 * 1. Kelas modelnya ada dan tabelnya ada.
 * 2. Setiap kolom di daftar `field` benar-benar ada di tabel itu.
 * 3. Kolom `kode` ada — itu yang dicari orang di dropdown pemilih record.
 * 4. Relasi `induk` beserta kolom kodenya bisa ditempuh, untuk modul detail.
 * 5. Relasi `label_relasi` dan kolom `label_kolom` bisa dibaca.
 * 6. opsiRecord() mengembalikan pilihan, dan kode induknya terbaca.
 *
 * Nomor 6 sengaja ikut walau paling lambat: lima yang pertama memeriksa
 * bentuknya, dan bentuk yang benar masih bisa menghasilkan dropdown kosong.
 */
class PeriksaPerbaikanData extends Command
{
    protected $signature = 'perbaikan-data:periksa
                            {--modul= : Periksa satu modul saja, sesuai kuncinya di config}
                            {--cepat : Lewati uji opsiRecord, yang paling banyak query}';

    protected $description = 'Periksa daftar kolom Perbaikan Data terhadap database yang sebenarnya';

    /** @var array<int, string> */
    private array $masalah = [];

    public function handle(PerbaikanDataService $perbaikan): int
    {
        $modul = (array) config('perbaikan_data.modul', []);
        $hanya = $this->option('modul');

        if ($hanya !== null) {
            if (! isset($modul[$hanya])) {
                $this->error("Modul {$hanya} tidak ada di config.");

                return self::FAILURE;
            }

            $modul = [$hanya => $modul[$hanya]];
        }

        $kolomDiperiksa = 0;

        foreach ($modul as $slug => $konfigurasi) {
            $kolomDiperiksa += $this->periksaModul($perbaikan, (string) $slug, (array) $konfigurasi);
        }

        $this->newLine();

        if ($this->masalah !== []) {
            $this->error(sprintf('%d masalah ditemukan:', count($this->masalah)));

            foreach ($this->masalah as $baris) {
                $this->line('  - ' . $baris);
            }

            return self::FAILURE;
        }

        $this->info(sprintf(
            '%d modul, %d kolom. Semuanya cocok dengan database.',
            count($modul),
            $kolomDiperiksa
        ));

        return self::SUCCESS;
    }

    /**
     * @return int jumlah kolom yang diperiksa pada modul ini
     */
    private function periksaModul(PerbaikanDataService $perbaikan, string $slug, array $konfigurasi): int
    {
        $kelas = $konfigurasi['model'] ?? null;

        if (! is_string($kelas) || ! class_exists($kelas)) {
            $this->catat($slug, 'kelas model tidak ada: ' . var_export($kelas, true));

            return 0;
        }

        $model = new $kelas;

        if (! $model instanceof Model) {
            $this->catat($slug, "{$kelas} bukan model Eloquent.");

            return 0;
        }

        $tabel = $model->getTable();
        $kolom = $this->kolomTabel($tabel);

        if ($kolom === []) {
            $this->catat($slug, "tabel {$tabel} tidak ada atau tidak punya kolom.");

            return 0;
        }

        $field = array_keys((array) ($konfigurasi['field'] ?? []));

        if ($field === []) {
            $this->catat($slug, 'tidak punya satu pun kolom di daftar field.');
        }

        foreach ($field as $nama) {
            if (! in_array($nama, $kolom, true)) {
                $this->catat($slug, "kolom {$nama} tidak ada di tabel {$tabel}.");
            }
        }

        // Kolom kode dipakai dua kali: mencari record di dropdown, dan
        // menampilkan kodenya di baris perubahan. Modul detail tidak punya
        // kode sendiri — kodenya diambil dari induk.
        if (isset($konfigurasi['kode']) && ! in_array($konfigurasi['kode'], $kolom, true)) {
            $this->catat($slug, "kolom kode {$konfigurasi['kode']} tidak ada di tabel {$tabel}.");
        }

        $this->periksaRelasi($slug, $model, $konfigurasi);

        if (isset($konfigurasi['label_kolom']) && ! in_array($konfigurasi['label_kolom'], $kolom, true)) {
            $this->catat($slug, "label_kolom {$konfigurasi['label_kolom']} tidak ada di tabel {$tabel}.");
        }

        if (! $this->option('cepat')) {
            $this->periksaOpsi($perbaikan, $slug);
        }

        $this->line(sprintf('  %-34s %-30s %3d kolom', $slug, $tabel, count($field)));

        return count($field);
    }

    private function periksaRelasi(string $slug, Model $model, array $konfigurasi): void
    {
        foreach (['induk', 'label_relasi'] as $kunci) {
            if (! isset($konfigurasi[$kunci]['relasi'])) {
                continue;
            }

            $relasi = $konfigurasi[$kunci]['relasi'];

            if (! method_exists($model, $relasi)) {
                $this->catat($slug, "{$kunci}: relasi {$relasi}() tidak ada di " . $model::class . '.');

                continue;
            }

            $terkait = $model->{$relasi}()->getRelated();
            $kolomTerkait = $this->kolomTabel($terkait->getTable());

            // `induk` menyebut kolom kodenya, `label_relasi` menyebut kolomnya.
            $kolomDicari = $konfigurasi[$kunci]['kode'] ?? $konfigurasi[$kunci]['kolom'] ?? null;

            if ($kolomDicari !== null && ! in_array($kolomDicari, $kolomTerkait, true)) {
                $this->catat($slug, sprintf(
                    '%s: kolom %s tidak ada di tabel %s.',
                    $kunci,
                    $kolomDicari,
                    $terkait->getTable()
                ));
            }
        }
    }

    /**
     * Dropdown yang bentuknya benar tapi tidak pernah berisi apa-apa tetap
     * dropdown yang tidak bisa dipakai. Tabel kosong bukan masalah config, jadi
     * dilaporkan sebagai catatan, bukan kegagalan.
     */
    private function periksaOpsi(PerbaikanDataService $perbaikan, string $slug): void
    {
        try {
            $opsi = $perbaikan->opsiRecord($slug, null, 1);
        } catch (PerbaikanDataDitolak $e) {
            $this->catat($slug, 'opsiRecord ditolak: ' . $e->getMessage());

            return;
        } catch (\Throwable $e) {
            $this->catat($slug, 'opsiRecord gagal: ' . $e->getMessage());

            return;
        }

        if ($opsi === []) {
            $this->warn("  {$slug}: tidak ada satu pun record. Tabelnya kosong, bukan confignya yang salah.");

            return;
        }

        if (($opsi[0]['kode'] ?? '') === '') {
            $this->catat($slug, 'kode record tidak terbaca, jadi pilihannya tampil tanpa kode.');
        }
    }

    /**
     * @return array<int, string>
     */
    private function kolomTabel(string $tabel): array
    {
        try {
            return array_map(
                fn ($kolom) => $kolom->Field,
                DB::select('show columns from `' . $tabel . '`')
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function catat(string $slug, string $pesan): void
    {
        $this->masalah[] = "{$slug}: {$pesan}";
    }
}
