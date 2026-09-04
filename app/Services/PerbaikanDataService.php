<?php

namespace App\Services;

use App\Exceptions\PerbaikanDataDitolak;
use App\Models\AuditPerubahanData;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Katalog kolom dan jejak koreksi data. Tidak mengubah data yang dikoreksi.
 *
 * Modul Perbaikan Data adalah PENCATATAN. Perubahan datanya sendiri dikerjakan
 * tim software langsung di database, dan itu keputusan yang diambil sadar:
 * sebagian besar koreksi menyentuh angka yang punya salinan di tempat lain
 * — qty sebuah lot ikut hidup di `sisa`, di alokasi FIFO baris konsumsi, dan
 * di sub total transaksi hilirnya. Yang bisa memutuskan mana saja yang ikut
 * disesuaikan adalah orang yang melihat kasusnya, bukan aturan umum yang
 * ditulis sekali untuk seluruh kolom.
 *
 * Konsekuensinya pada daftar kolom: tidak ada yang disembunyikan. Kolom yang
 * tidak bisa dipilih berarti perubahannya terjadi tanpa jejak, dan itu persis
 * keadaan yang modul ini ada untuk mencegahnya. Daftarnya karena itu gabungan
 * dua sumber — yang ditulis di config/perbaikan_data.php, ditambah sisa kolom
 * tabelnya yang dibaca dari skema. Lihat fieldModul().
 *
 * Yang dijaga terapkan() bukan keutuhan stok — itu di luar jangkauannya —
 * melainkan kejujuran catatannya: kolomnya benar-benar ada, nilai lamanya masih
 * cocok dengan database saat dicatat, dan nilai barunya memang berbeda.
 *
 * Semua penulisan jejak lewat method ini. Kalau ada dua jalan tulis, salah
 * satunya cepat atau lambat akan lupa menulis audit, dan halaman audit yang
 * tidak lengkap lebih berbahaya daripada tidak ada halaman audit sama sekali:
 * yang membacanya akan menyimpulkan "tidak ada perubahan" padahal yang benar
 * "perubahannya tidak tercatat".
 */
class PerbaikanDataService
{
    /**
     * Pilihan "Jenis Pengajuan" pada form.
     *
     * @return array<int, string>
     */
    public function jenisPengajuan(): array
    {
        return array_values((array) config('perbaikan_data.jenis_pengajuan', []));
    }

    /**
     * Modul mana yang muncul untuk setiap jenis pengajuan.
     *
     * Inilah yang menyambungkan checkbox "Jenis Pengajuan" dengan dropdown kode
     * transaksi. Tanpa peta ini keduanya jadi dua daftar yang tidak saling
     * kenal: pengaju mencentang "Transaksi - Bahan Masuk" lalu masih harus
     * menebak sendiri bahwa modul yang benar bernama "Bahan Masuk" atau "Harga
     * Lot Bahan Masuk".
     *
     * Jenis yang tidak punya modul tetap muncul sebagai kunci dengan nilai array
     * kosong, bukan dihilangkan. Bedanya penting di layar: kunci yang ada dengan
     * isi kosong bisa dijawab "jenis ini tidak punya kolom yang boleh dikoreksi",
     * sedangkan kunci yang hilang tidak bisa dibedakan dari salah ketik label.
     *
     * @return array<string, array<int, string>> label jenis => daftar slug modul
     */
    public function modulPerJenis(): array
    {
        $peta = array_fill_keys($this->jenisPengajuan(), []);

        foreach ((array) config('perbaikan_data.modul', []) as $slug => $modul) {
            foreach ((array) ($modul['jenis'] ?? []) as $jenis) {
                // Jenis yang tidak ada di daftar tetap dimasukkan. Kalau kunci
                // `jenis` sebuah modul salah tulis, lebih baik modulnya muncul
                // di bawah label asing yang kelihatan janggal daripada hilang
                // diam-diam dari form.
                $peta[$jenis][] = $slug;
            }
        }

        return $peta;
    }

    /**
     * Semua kolom yang boleh dikoreksi, rata dalam satu daftar.
     *
     * Menggabungkan modul dan kolom jadi satu pilihan: "Bahan Masuk — No
     * Invoice", "Harga Lot Bahan Masuk — Harga per Unit". Sebelumnya form
     * meminta pengaju memilih modul lebih dulu, padahal setelah jenis
     * pengajuannya dicentang hampir semua jenis cuma punya satu modul — satu
     * dropdown yang isinya selalu satu baris. Yang benar-benar ingin dijawab
     * pengaju adalah "kolom mana yang salah", dan modulnya menyusul dari situ.
     *
     * `nilai` memakai pemisah "::" supaya satu <option> cukup membawa keduanya.
     * Modulnya tetap disimpan terpisah di `perbaikan_data_target.modul`, jadi
     * bentuk gabungan ini tidak pernah masuk database dan tidak menyentuh jalur
     * eksekusi maupun audit.
     *
     * @return array<int, array{nilai: string, modul: string, field: string, label: string}>
     */
    /**
     * Kolom sebuah modul, apa adanya dari config.
     *
     * Isinya kolom yang BENAR-BENAR TAMPIL di layar modul itu — kolom tabel
     * indeksnya, tabel detail di halaman show, dan kartu isian Livewire-nya.
     * Bukan seluruh kolom tabel database.
     *
     * Sempat dibaca langsung dari skema supaya tidak ada yang tertinggal.
     * Hasilnya salah arah: dropdown terisi kolom mesin (`details` berisi JSON
     * alokasi FIFO, `used_materials`, `qty_input` sebelum konversi satuan) dan
     * kolom yang tidak pernah muncul di halaman mana pun (`pengisi_harga`,
     * `kategori_pengajuan`, `panjang_standar`). Meminta pengaju memilih kolom
     * yang tidak pernah dia lihat bukan koreksi, melainkan tebakan.
     *
     * Daftarnya karena itu dikurasi dari tampilan, dan disimpan di config.
     * Konsekuensinya: kolom database yang baru tidak muncul dengan sendirinya.
     * Itu memang yang diinginkan — sebuah kolom baru baru layak dicatat
     * setelah ada halaman yang menampilkannya.
     *
     * Status approval (`status`, `status_*`, `tgl_approve_*`) tidak ikut walau
     * tampil jelas di layar. Nilainya lahir dari seseorang menekan setuju atau
     * tolak; "memperbaikinya" berarti menulis ulang riwayat siapa menyetujui
     * apa, dan itu urusan alur approval, bukan koreksi data.
     *
     * @return array<string, array<string, mixed>>
     */
    public function fieldModul(string $slug): array
    {
        return (array) config("perbaikan_data.modul.{$slug}.field", []);
    }

    /**
     * Nama tabel yang ditunjuk sebuah modul.
     *
     * Modul bukan padanan satu-satu dengan tabel. Tiga modul Pembelian Bahan
     * menunjuk baris yang sama persis, dipisah hanya untuk mengelompokkan kolom
     * biaya impor supaya labelnya terbaca. Yang menentukan "record mana yang
     * sedang dikoreksi" adalah tabel dan id barisnya, bukan modulnya — dan
     * itu yang dipakai memutuskan kolom apa saja yang boleh ditawarkan.
     */
    public function tabelModul(string $slug): string
    {
        $kelas = config("perbaikan_data.modul.{$slug}.model");

        return is_string($kelas) && class_exists($kelas) ? (new $kelas)->getTable() : $slug;
    }

    public function katalogKolom(): array
    {
        $hasil = [];

        foreach ((array) config('perbaikan_data.modul', []) as $slug => $modul) {
            foreach ($this->fieldModul($slug) as $field => $definisi) {
                $hasil[] = [
                    'nilai' => $slug . '::' . $field,
                    'modul' => $slug,
                    'tabel' => $this->tabelModul($slug),
                    'field' => $field,
                    'label' => ($modul['label'] ?? $slug) . ' — ' . ($definisi['label'] ?? $field),
                ];
            }
        }

        return $hasil;
    }

    /**
     * Definisi satu kolom dari daftar putih.
     *
     * @throws PerbaikanDataDitolak kalau modul atau kolomnya tidak terdaftar
     */
    public function definisiField(string $modul, string $field): array
    {
        $definisi = $this->fieldModul($modul)[$field] ?? null;

        if (! is_array($definisi)) {
            throw new PerbaikanDataDitolak(
                "Kolom {$field} pada {$modul} tidak ada, jadi tidak bisa dicatat sebagai koreksi."
            );
        }

        return $definisi + ['tipe' => 'string'];
    }

    /**
     * Apakah kolom ini mewajibkan lampiran bukti.
     */
    public function wajibLampiran(string $modul, string $field): bool
    {
        return (bool) ($this->definisiField($modul, $field)['wajib_lampiran'] ?? false);
    }

    /**
     * Nilai kolom yang tersimpan sekarang, sudah dinormalkan jadi teks.
     *
     * Dipakai form pengajuan untuk membekukan nilai lama, dan dipakai lagi saat
     * eksekusi untuk memastikan angkanya belum berubah sejak diajukan.
     */
    public function nilaiSekarang(string $modul, int $modulId, string $field): ?string
    {
        $definisi = $this->definisiField($modul, $field);
        $record = $this->record($modul, $modulId);

        return $this->normalkan($record->{$field}, $definisi['tipe']);
    }

    /**
     * Kode record target untuk ditampilkan, mis. "KBM - 0001".
     *
     * Untuk tabel detail, kodenya diambil dari induknya: baris detail tidak
     * punya kode sendiri yang dikenali pengguna.
     */
    public function kodeRecord(string $modul, int $modulId): ?string
    {
        return $this->kodeDari(
            $this->record($modul, $modulId),
            $this->konfigurasiModul($modul)
        );
    }

    /**
     * Kode yang dikenali pengguna dari satu record yang sudah dimuat.
     *
     * Dipisah dari kodeRecord() supaya opsiRecord() bisa memakainya tanpa
     * memuat ulang recordnya satu per satu: memanggil kodeRecord() di dalam
     * loop berarti satu query find() per baris pilihan.
     */
    private function kodeDari(Model $record, array $konfigurasi): ?string
    {
        if (isset($konfigurasi['induk'])) {
            $induk = $record->{$konfigurasi['induk']['relasi']};

            return $induk ? (string) $induk->{$konfigurasi['induk']['kode']} : null;
        }

        return isset($konfigurasi['kode']) ? (string) $record->{$konfigurasi['kode']} : null;
    }

    /**
     * Daftar record yang bisa dipilih pada satu modul, untuk dropdown pencarian.
     *
     * Selalu dibatasi $batas baris. Modul seperti bahan masuk punya puluhan ribu
     * baris; mengirim semuanya ke browser akan membuat form-nya berhenti bisa
     * dipakai justru pada modul yang paling sering dikoreksi. Yang dikirim
     * adalah hasil pencarian, dan tanpa kata pencarian yang dikirim adalah
     * $batas record terbaru — itu yang paling mungkin dicari.
     *
     * Nilai kolom setiap record ikut dikirim supaya kolom "nilai lama" terisi
     * begitu pilihannya diklik, tanpa permintaan kedua ke server. Nilai ini
     * tidak dipercaya saat penyimpanan — store() membacanya ulang dari
     * database — tapi tanpa ditampilkan, pengaju hanya bisa menebak apa yang
     * dikoreksinya.
     *
     * @return array<int, array{modul_id: int, kode: string, label: string, nilai: array<string, ?string>}>
     */
    public function opsiRecord(string $modul, ?string $cari = null, int $batas = 30): array
    {
        $konfigurasi = $this->konfigurasiModul($modul);
        $kelas = $konfigurasi['model'];
        $cari = trim((string) $cari);

        $query = $kelas::query()->orderByDesc((new $kelas)->getKeyName());

        if (isset($konfigurasi['induk'])) {
            $relasiInduk = $konfigurasi['induk']['relasi'];
            $kodeInduk = $konfigurasi['induk']['kode'];

            $query->with($relasiInduk);

            if ($cari !== '') {
                $query->whereHas(
                    $relasiInduk,
                    fn ($q) => $q->where($kodeInduk, 'like', '%' . $cari . '%')
                );
            }
        } else {
            $kolomKode = $konfigurasi['kode'] ?? null;

            if ($kolomKode === null) {
                throw new PerbaikanDataDitolak(
                    "Modul {$modul} belum punya kolom kode, jadi recordnya tidak bisa dipilih dari daftar."
                );
            }

            if ($cari !== '') {
                $query->where($kolomKode, 'like', '%' . $cari . '%');
            }
        }

        if (isset($konfigurasi['label_relasi'])) {
            $query->with($konfigurasi['label_relasi']['relasi']);
        }

        $field = array_keys($this->fieldModul($modul));

        return $query->limit($batas)->get()->map(function ($record) use ($modul, $konfigurasi, $field) {
            $kode = (string) ($this->kodeDari($record, $konfigurasi) ?? '');
            $label = $kode !== '' ? $kode : '#' . $record->getKey();

            // Dua sumber label tambahan, dicoba berurutan: `label_relasi`
            // untuk nama yang harus diambil dari tabel lain, `label_kolom`
            // untuk nama yang tersimpan di barisnya sendiri.
            //
            // Dicoba berurutan, bukan salah satu saja. Baris pengajuan
            // pembelian menyimpan `nama_bahan` sendiri, tapi kosong pada 2.356
            // dari 2.866 barisnya — namanya ada di tabel bahan lewat
            // `bahan_id`. Yang memakai satu sumber saja akan kehilangan nama
            // pada sebagian besar baris, dan daftar pilihannya jadi berisi
            // baris-baris yang tampak kembar.
            $tambahan = null;

            if (isset($konfigurasi['label_relasi'])) {
                $tambahan = optional($record->{$konfigurasi['label_relasi']['relasi']})
                    ->{$konfigurasi['label_relasi']['kolom']};
            }

            if (blank($tambahan) && isset($konfigurasi['label_kolom'])) {
                $tambahan = $record->{$konfigurasi['label_kolom']};
            }

            if (filled($tambahan)) {
                $label .= ' — ' . $tambahan;
            } elseif (isset($konfigurasi['induk'])) {
                // Baris detail tanpa nama sama sekali. Kode induknya sama untuk
                // semua saudaranya, jadi tanpa penanda ini pilihannya tampil
                // identik dan tidak ada cara memilih yang dimaksud.
                $label .= ' #' . $record->getKey();
            }

            $nilai = [];

            foreach ($field as $nama) {
                $definisi = $this->definisiField($modul, $nama);
                $nilai[$nama] = $this->normalkan($record->{$nama}, $definisi['tipe']);
            }

            return [
                'modul_id' => (int) $record->getKey(),
                'kode' => $kode,
                'label' => $label,
                'nilai' => $nilai,
            ];
        })->all();
    }

    /**
     * Record dari SEMUA modul milik jenis yang dicentang, dalam satu daftar.
     *
     * Urutan pengisian form mengikuti cara orang berpikir soal koreksinya:
     * jenis pengajuan, lalu transaksi mana, baru kolom mana yang salah. Yang
     * pertama diingat pengaju adalah kode transaksinya — dia datang membawa
     * dokumen di tangan — bukan nama kolomnya.
     *
     * Sebelumnya kolom harus dipilih lebih dulu, semata karena kolom itu yang
     * menentukan tabel mana yang dicari. Itu urutan yang nyaman bagi mesin dan
     * canggung bagi orang.
     *
     * Satu kode transaksi bisa menunjuk lebih dari satu record: barisnya
     * sendiri, dan tiap baris detail di bawahnya. Karena itu label tiap pilihan
     * menyebut modulnya — "PBL-001 · Pengajuan Pembelian Bahan" berbeda dari
     * "PBL-001 — Resistor 10k · Baris Pengajuan Pembelian". Tanpa itu daftarnya
     * berisi baris-baris yang tampak kembar.
     *
     * Jatah per modul dibatasi, bukan hanya totalnya. Tanpa itu satu modul yang
     * kodenya banyak cocok akan menghabiskan seluruh daftar, dan modul lain
     * pada jenis yang sama tidak pernah muncul sama sekali.
     *
     * @param  array<int, string>  $jenis  label jenis pengajuan yang dicentang
     * @return array<int, array<string, mixed>>
     */
    public function opsiRecordJenis(array $jenis, ?string $cari = null, int $batas = 30): array
    {
        $peta = $this->modulPerJenis();
        $slugTerpilih = [];

        foreach ($jenis as $satu) {
            foreach ($peta[$satu] ?? [] as $slug) {
                $slugTerpilih[$slug] = true;
            }
        }

        $slugTerpilih = array_keys($slugTerpilih);

        if ($slugTerpilih === []) {
            return [];
        }

        // Dibagi rata, minimal lima per modul. Membagi persis rata pada jenis
        // dengan banyak modul menyisakan satu baris per modul — terlalu sedikit
        // untuk bisa dikenali sebagai daftar.
        $jatah = max(5, (int) ceil($batas / count($slugTerpilih)));
        $hasil = [];

        foreach ($slugTerpilih as $slug) {
            $label = config("perbaikan_data.modul.{$slug}.label", $slug);

            try {
                $opsi = $this->opsiRecord($slug, $cari, $jatah);
            } catch (PerbaikanDataDitolak $e) {
                // Satu modul yang belum punya kolom kode tidak boleh membuat
                // seluruh daftar gagal: modul lain pada jenis yang sama masih
                // berguna, dan yang salah confignya, bukan pencarian ini.
                continue;
            }

            $tabel = $this->tabelModul($slug);

            foreach ($opsi as $satu) {
                // Baris yang sama tidak boleh muncul berkali-kali hanya karena
                // beberapa modul menunjuk tabel yang sama. Yang dipilih pengaju
                // adalah recordnya; kolom milik modul saudaranya tetap ikut
                // ditawarkan sesudahnya, karena penyaringannya per tabel.
                $kunci = $tabel . '#' . $satu['modul_id'];

                if (isset($hasil[$kunci])) {
                    continue;
                }

                $satu['modul'] = $slug;
                $satu['tabel'] = $tabel;
                $satu['label'] = $satu['label'] . ' · ' . $label;
                $hasil[$kunci] = $satu;
            }
        }

        return array_slice(array_values($hasil), 0, max($batas, count($slugTerpilih) * 5));
    }

    /**
     * Catat satu koreksi ke jejak audit. TIDAK mengubah data yang dikoreksi.
     *
     * Modul Perbaikan Data adalah pencatatan, bukan pelaksana. Perubahan
     * datanya sendiri dikerjakan tim software langsung di database, karena
     * sebagian besar koreksi menyentuh angka yang punya salinan di tempat lain
     * — qty sebuah lot ikut hidup di `sisa`, di alokasi FIFO baris konsumsi,
     * dan di sub total transaksi hilirnya. Yang bisa memutuskan mana saja yang
     * ikut disesuaikan adalah orang yang melihat kasusnya, bukan aturan umum
     * yang ditulis sekali untuk seluruh kolom.
     *
     * Karena itu method ini menulis SATU baris: jejaknya. Yang dijaga bukan
     * keutuhan stok — itu di luar jangkauannya — melainkan kejujuran
     * catatannya:
     *
     * 1. Kolomnya harus benar-benar ada, di config atau di skema tabelnya.
     *    Baris audit yang menunjuk kolom karangan tidak bisa ditelusuri.
     * 2. `nilai_lama` harus masih cocok dengan isi database saat dicatat. Kalau
     *    sudah berubah, catatannya akan menyesatkan pembacanya nanti: dia akan
     *    mengira perubahannya dari nilai yang sebenarnya sudah lama tidak ada.
     * 3. Nilai barunya harus berbeda. Mencatat "diubah dari X jadi X" hanya
     *    menambah baris tanpa menambah keterangan.
     *
     * Kunci $koreksi: modul, modul_id, field, nilai_lama, nilai_baru, alasan,
     * pengaju_id, approver_id, perbaikan_data_id (opsional),
     * ip_address (opsional).
     *
     * @throws PerbaikanDataDitolak
     */
    public function terapkan(array $koreksi): AuditPerubahanData
    {
        foreach (['modul', 'modul_id', 'field', 'alasan'] as $wajib) {
            if (blank($koreksi[$wajib] ?? null)) {
                throw new PerbaikanDataDitolak("Data koreksi tidak lengkap: {$wajib} kosong.");
            }
        }

        if (! array_key_exists('nilai_lama', $koreksi)) {
            throw new PerbaikanDataDitolak(
                'Data koreksi tidak lengkap: nilai lama tidak disertakan, jadi tidak bisa dipastikan datanya belum berubah.'
            );
        }

        $modul = (string) $koreksi['modul'];
        $modulId = (int) $koreksi['modul_id'];
        $field = (string) $koreksi['field'];
        $definisi = $this->definisiField($modul, $field);

        $record = $this->record($modul, $modulId);

        $nilaiSekarang = $this->normalkan($record->{$field}, $definisi['tipe']);
        $nilaiLamaDicatat = $this->normalkan($koreksi['nilai_lama'], $definisi['tipe']);

        if ($nilaiSekarang !== $nilaiLamaDicatat) {
            throw new PerbaikanDataDitolak(sprintf(
                'Pencatatan dibatalkan: nilai %s di database sekarang %s, sedangkan yang dicatat sebagai '
                . 'nilai lama %s. Segarkan halamannya dan catat ulang dengan nilai terbaru.',
                $definisi['label'] ?? $field,
                $nilaiSekarang ?? '(kosong)',
                $nilaiLamaDicatat ?? '(kosong)'
            ));
        }

        $nilaiBaru = $this->normalkan($koreksi['nilai_baru'] ?? null, $definisi['tipe']);

        if ($nilaiBaru === $nilaiSekarang) {
            throw new PerbaikanDataDitolak('Nilai barunya sama dengan yang tersimpan, tidak ada yang perlu dicatat.');
        }

        $pengajuId = $koreksi['pengaju_id'] ?? null;
        $approverId = $koreksi['approver_id'] ?? null;

        return AuditPerubahanData::create([
            'perbaikan_data_id' => $koreksi['perbaikan_data_id'] ?? null,
            'modul' => $modul,
            'modul_id' => $modulId,
            'tabel_target' => $record->getTable(),
            'baris_target_id' => $record->getKey(),
            'field' => $field,
            'nilai_lama' => $nilaiSekarang,
            'nilai_baru' => $nilaiBaru,
            'alasan' => (string) $koreksi['alasan'],
            'pengaju_id' => $pengajuId,
            'approver_id' => $approverId,
            // Menyetujui permintaan sendiri. Tidak dilarang — melarangnya cuma
            // mendorong orang kembali menyunting database tanpa jejak sama
            // sekali — tapi harus terlihat saat diperiksa.
            //
            // Dulu yang dibandingkan pengaju dengan eksekutor. Perbandingan itu
            // ikut hilang bersama kolom eksekutor, dan memang bukan yang
            // penting: mencatat permintaan sendiri bukan apa-apa, menyetujui
            // permintaan sendiri baru kelemahan kontrol.
            'disetujui_sendiri' => $pengajuId !== null
                && $approverId !== null
                && (int) $pengajuId === (int) $approverId,
            'ip_address' => $koreksi['ip_address'] ?? null,
            'created_at' => Carbon::now('Asia/Jakarta'),
        ]);
    }

    private function konfigurasiModul(string $modul): array
    {
        $konfigurasi = config("perbaikan_data.modul.{$modul}");

        if (! is_array($konfigurasi)) {
            throw new PerbaikanDataDitolak("Modul {$modul} tidak terdaftar sebagai modul yang bisa dikoreksi.");
        }

        return $konfigurasi;
    }

    /**
     * Record target sebuah modul.
     *
     * Melempar PerbaikanDataDitolak, bukan ModelNotFoundException: pemanggilnya
     * menangkap satu jenis pengecualian untuk semua sebab penolakan, dan
     * halaman 404 di tengah proses pencatatan tidak menjelaskan apa pun kepada
     * yang mengerjakannya.
     */
    private function record(string $modul, int $modulId): Model
    {
        $kelas = $this->konfigurasiModul($modul)['model'];
        $record = $kelas::find($modulId);

        if (! $record instanceof Model) {
            throw new PerbaikanDataDitolak(
                "Baris #{$modulId} pada modul {$modul} tidak ditemukan, mungkin sudah dihapus."
            );
        }

        return $record;
    }

    /**
     * Samakan bentuk nilai jadi teks sebelum dibandingkan dan disimpan.
     *
     * Perbandingan nilai lama harus tahan terhadap perbedaan bentuk yang tidak
     * mengubah arti: 1000 dari form dan "1000.00" dari database adalah angka
     * yang sama, dan menolak koreksi karena itu hanya akan membingungkan.
     */
    private function normalkan($nilai, string $tipe): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return match ($tipe) {
            'decimal' => rtrim(rtrim(number_format((float) $nilai, 4, '.', ''), '0'), '.'),
            'datetime' => Carbon::parse($nilai)->format('Y-m-d H:i:s'),
            default => trim((string) $nilai),
        };
    }
}
