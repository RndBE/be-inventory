<?php

namespace App\Console\Commands;

use App\Services\HrisPegawai;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Periksa sambungan ke HRIS, dan sebutkan apa yang kurang.
 *
 * Dibuat karena kegagalan HRIS di aplikasi ini SENYAP. HrisPegawai::byEmail()
 * mengembalikan null untuk semua sebab — belum dikonfigurasi, sertifikat tidak
 * terverifikasi, HRIS mati, email tidak terdaftar — dan itu memang disengaja:
 * pembuatan BAST maupun surat penunjukan tidak boleh gagal hanya karena sistem
 * lain bermasalah.
 *
 * Harganya, dari layar tidak ada bedanya antara "pegawainya memang tidak
 * terdaftar" dan "sudah berbulan-bulan tidak pernah tersambung". Di mesin
 * pengembangan hal itu benar-benar terjadi: PHP-nya tidak punya trust store,
 * setiap panggilan gagal dengan cURL error 60, dan dokumen diam-diam terisi
 * dari cadangan sejak fitur itu dibuat.
 *
 * Perintah ini yang membedakannya. Dijalankan di server setelah deploy, atau
 * kapan pun ada yang curiga nomor ID di dokumen tidak ikut terisi.
 */
class CekHris extends Command
{
    protected $signature = 'hris:cek
                            {email? : Email pegawai untuk uji baca. Kosong berarti diambil dari user aktif pertama}';

    protected $description = 'Periksa konfigurasi dan sambungan ke HRIS';

    public function handle(): int
    {
        $url = rtrim((string) config('services.hris.url'), '/');
        $key = (string) config('services.hris.key');
        $ca = (string) config('services.hris.ca');

        $this->line('');
        $this->line('<options=bold>Konfigurasi</>');
        $this->baris('HRIS_URL', $url !== '', $url !== '' ? $url : 'KOSONG — HrisPegawai berhenti sebelum memanggil apa pun');
        $this->baris('HRIS_API_KEY', $key !== '', $key !== '' ? 'terisi (' . strlen($key) . ' karakter)' : 'KOSONG');
        $this->baris('HRIS_TIMEOUT', true, config('services.hris.timeout', 5) . ' detik');

        $this->line('');
        $this->line('<options=bold>Verifikasi sertifikat</>');
        $this->periksaSertifikat($ca);

        if ($url === '' || $key === '') {
            $this->line('');
            $this->error('Konfigurasi belum lengkap, uji baca dilewati.');

            return self::FAILURE;
        }

        $this->line('');
        $this->line('<options=bold>Uji baca</>');

        return $this->ujiBaca($url, $key, $ca);
    }

    /**
     * Trust store mana yang dipakai, dan apakah benar-benar ada.
     *
     * Tiga keadaan yang perlu dibedakan, karena penanganannya berbeda: berkas
     * CA disetel dan ada (dipakai), disetel tapi hilang (paling berbahaya —
     * seolah sudah diatur padahal jatuh ke trust store sistem), dan tidak
     * disetel sama sekali (benar di server yang PHP-nya waras).
     */
    private function periksaSertifikat(string $ca): void
    {
        $cainfo = (string) ini_get('curl.cainfo');
        $cafile = (string) ini_get('openssl.cafile');
        $sistem = $cainfo !== '' || $cafile !== '';

        if ($ca !== '' && ! is_file($ca)) {
            $this->baris('HRIS_CA_BUNDLE', false, $ca . ' — DISETEL TAPI BERKASNYA TIDAK ADA');
            $this->warn('  Karena berkasnya tidak ada, verifikasi jatuh ke trust store sistem.');
        } elseif ($ca !== '') {
            $this->baris('HRIS_CA_BUNDLE', true, $ca . ' (' . number_format(filesize($ca) / 1024, 0) . ' KB)');
        } else {
            $this->baris('HRIS_CA_BUNDLE', $sistem, $sistem
                ? 'kosong — memakai trust store sistem, dan itu memang yang benar'
                : 'kosong, DAN php.ini tidak punya curl.cainfo/openssl.cafile');
        }

        // Dua baris php.ini di bawah cuma keterangan kalau HRIS_CA_BUNDLE sudah
        // menutupinya. Menandainya merah di keadaan itu akan membuat orang
        // mengejar masalah yang sudah selesai.
        $tertutup = $ca !== '' && is_file($ca);

        $this->baris('curl.cainfo', $tertutup || $cainfo !== '', $cainfo !== '' ? $cainfo : 'tidak disetel');
        $this->baris('openssl.cafile', $tertutup || $cafile !== '', $cafile !== '' ? $cafile : 'tidak disetel');

        if ($ca === '' && ! $sistem) {
            $this->warn('  PHP di mesin ini tidak menunjuk berkas CA mana pun. Kalau uji baca di bawah');
            $this->warn('  gagal dengan cURL error 60, isi HRIS_CA_BUNDLE dengan path cacert.pem.');
        }
    }

    /**
     * Panggil HRIS dua kali: mentah, lalu lewat HrisPegawai.
     *
     * Yang mentah memperlihatkan status dan pesan aslinya — itu yang ditelan
     * HrisPegawai. Yang kedua membuktikan jalur yang benar-benar dipakai
     * aplikasi memberi hasil yang sama; keduanya berbeda kalau ada yang salah
     * di config atau di penanganan responsnya.
     */
    private function ujiBaca(string $url, string $key, string $ca): int
    {
        $email = (string) ($this->argument('email') ?: $this->emailUjiBawaan());

        if ($email === '') {
            $this->error('Tidak ada email untuk diuji, dan tidak ada user aktif ber-email di database.');

            return self::FAILURE;
        }

        $this->line('  Email diuji: ' . $email);

        try {
            $permintaan = Http::withHeaders(['X-API-KEY' => $key])
                ->acceptJson()
                ->timeout((int) config('services.hris.timeout', 5));

            if ($ca !== '' && is_file($ca)) {
                $permintaan = $permintaan->withOptions(['verify' => $ca]);
            }

            $mulai = microtime(true);
            $respons = $permintaan->get($url . '/api/pegawai/by-email', ['email' => $email]);
            $ms = (int) round((microtime(true) - $mulai) * 1000);
        } catch (Throwable $e) {
            $this->baris('Panggilan mentah', false, $e->getMessage());
            $this->line('');
            $this->error('HRIS tidak bisa dihubungi. Selama ini berlangsung, nomor ID di dokumen '
                . 'terisi dari data lokal tanpa ada peringatan di layar.');

            return self::FAILURE;
        }

        $this->baris('Panggilan mentah', $respons->successful() || $respons->status() === 404,
            'HTTP ' . $respons->status() . ' dalam ' . $ms . 'ms');

        if ($respons->status() === 404) {
            $this->line('  Balasan: ' . $respons->json('message', $respons->body()));
            $this->line('');
            $this->info('Sambungan ke HRIS SEHAT. Emailnya saja yang tidak terdaftar di sana.');

            return self::SUCCESS;
        }

        if (! $respons->successful()) {
            $this->line('  Balasan: ' . substr($respons->body(), 0, 300));
            $this->line('');
            $this->error('HRIS membalas dengan kesalahan. Periksa HRIS_API_KEY.');

            return self::FAILURE;
        }

        $lewatService = HrisPegawai::byEmail($email);

        $this->baris('Lewat HrisPegawai', $lewatService !== null,
            $lewatService !== null ? 'mengembalikan data' : 'mengembalikan NULL padahal panggilan mentah berhasil');

        if ($lewatService === null) {
            return self::FAILURE;
        }

        $this->line('');
        $this->line('<options=bold>Data yang diterima</>');

        foreach ($lewatService as $kunci => $nilai) {
            $this->line(sprintf('  %-10s %s', $kunci, $nilai ?? '(kosong)'));
        }

        $this->line('');
        $this->info('HRIS tersambung dan membalas dengan benar.');

        return self::SUCCESS;
    }

    /**
     * User aktif pertama yang punya email, untuk dipakai kalau tidak disebut.
     *
     * Memakai data sungguhan, bukan email karangan: yang ingin dibuktikan
     * bukan cuma "HRIS hidup", tapi "pegawai di sistem ini memang ketemu di
     * sana". Email karangan selalu berakhir 404 dan tidak membuktikan itu.
     */
    private function emailUjiBawaan(): string
    {
        return (string) DB::table('users')
            ->where('status', 'Aktif')
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->orderBy('id')
            ->value('email');
    }

    private function baris(string $label, bool $baik, string $isi): void
    {
        $this->line(sprintf(
            '  %s %-16s %s',
            $baik ? '<fg=green>OK  </>' : '<fg=red>GAGAL</>',
            $label,
            $isi
        ));
    }
}
