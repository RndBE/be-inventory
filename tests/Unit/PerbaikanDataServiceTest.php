<?php

namespace Tests\Unit;

use App\Exceptions\PerbaikanDataDitolak;
use App\Models\AuditPerubahanData;
use App\Services\PerbaikanDataService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Katalog kolom dan pencatatan koreksi di PerbaikanDataService.
 *
 * Modul Perbaikan Data mencatat koreksi; perubahan datanya sendiri dikerjakan
 * tim software langsung di database. Itu yang menentukan apa yang layak diuji
 * di sini, dan yang membuat sebagian besar test lama dihapus bersama mesin
 * eksekusinya — penjaga lot utuh, hitung ulang kolom turunan, penyelarasan
 * alokasi FIFO. Semuanya menjaga perilaku yang sudah tidak ada; menyimpannya
 * hanya akan membuat suite ini menjanjikan jaminan yang tidak lagi diberikan.
 *
 * Tiga hal yang menggantikannya:
 *
 * 1. terapkan() TIDAK BOLEH menyentuh baris yang dikoreksi. Ini jaminan paling
 *    penting sekarang. Kalau bocor, aplikasi menulis kolom yang tidak pernah
 *    diperiksa akibatnya pada data lain — persis alasan eksekusinya dicabut.
 * 2. Nilai lama yang dicatat harus masih cocok dengan database. Catatan yang
 *    menyebut nilai lama yang sudah lama tidak ada akan menyesatkan pembacanya.
 * 3. Perbandingan nilai harus tahan perbedaan bentuk yang tidak mengubah arti.
 *    Angka 1000 dari form dan "1000.00" dari database adalah nilai yang sama,
 *    dan menolaknya hanya akan membuat orang berhenti mencatat.
 */
class PerbaikanDataServiceTest extends TestCase
{
    private PerbaikanDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PerbaikanDataService();
    }

    /**
     * Siapkan satu tabel target palsu beserta tabel auditnya.
     *
     * Dibuat langsung lewat Schema, bukan lewat migration: riwayat migration
     * proyek ini belum bisa jalan dari database kosong, dan menunggu itu berarti
     * jaminan terpenting modul ini — bahwa datanya tidak disentuh — tidak pernah
     * teruji sama sekali.
     */
    private function siapkanTabelUji(): void
    {
        Schema::create('uji_koreksi', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('kode_transaksi')->nullable();
            $tabel->string('no_invoice')->nullable();
            $tabel->decimal('qty', 15, 2)->nullable();
            $tabel->text('details')->nullable();
            $tabel->timestamps();
        });

        Schema::create('audit_perubahan_data', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->unsignedBigInteger('perbaikan_data_id')->nullable();
            $tabel->string('modul');
            $tabel->unsignedBigInteger('modul_id');
            $tabel->string('tabel_target')->nullable();
            $tabel->unsignedBigInteger('baris_target_id')->nullable();
            $tabel->string('field');
            $tabel->text('nilai_lama')->nullable();
            $tabel->text('nilai_baru')->nullable();
            $tabel->text('alasan');
            $tabel->unsignedBigInteger('pengaju_id')->nullable();
            $tabel->unsignedBigInteger('approver_id')->nullable();
            $tabel->boolean('disetujui_sendiri')->default(false);
            $tabel->string('ip_address')->nullable();
            $tabel->timestamp('created_at')->nullable();
        });

        config()->set('perbaikan_data.modul.uji_koreksi', [
            'label' => 'Uji Koreksi',
            'model' => ModelUjiKoreksi::class,
            'kode' => 'kode_transaksi',
            'jenis' => ['Transaksi - Bahan Masuk'],
            'field' => [
                'no_invoice' => ['label' => 'No Invoice', 'tipe' => 'string'],
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal'],
                'unit_price' => [
                    'label' => 'Harga Satuan',
                    'tipe' => 'decimal',
                    'json' => ['kolom' => 'details', 'key' => 'unit_price'],
                ],
            ],
        ]);
    }

    private function bereskanTabelUji(): void
    {
        Schema::dropIfExists('uji_koreksi');
        Schema::dropIfExists('audit_perubahan_data');
    }

    /**
     * Pencatatan menulis baris audit dan TIDAK menyentuh baris yang dikoreksi.
     *
     * Jaminan inti modul ini. Perubahan datanya dikerjakan tim software langsung
     * di database, karena qty sebuah lot punya salinan di `sisa`, di alokasi
     * FIFO baris konsumsi, dan di sub total transaksi hilirnya — dan yang bisa
     * memutuskan mana saja yang ikut disesuaikan adalah orang yang melihat
     * kasusnya. Kalau aplikasi diam-diam ikut menulis, salinan-salinan itu
     * ditinggalkan tanpa ada yang tahu.
     */
    #[Test]
    public function pencatatan_tidak_mengubah_baris_yang_dikoreksi(): void
    {
        $this->siapkanTabelUji();

        try {
            $baris = ModelUjiKoreksi::create([
                'kode_transaksi' => 'UJI-1',
                'no_invoice' => 'INV-LAMA',
                'qty' => 10,
            ]);

            $audit = $this->service->terapkan([
                'modul' => 'uji_koreksi',
                'modul_id' => $baris->id,
                'field' => 'no_invoice',
                'nilai_lama' => 'INV-LAMA',
                'nilai_baru' => 'INV-BARU',
                'alasan' => 'salah ketik nomor invoice',
            ]);

            $this->assertInstanceOf(AuditPerubahanData::class, $audit);
            $this->assertSame('INV-LAMA', $audit->nilai_lama);
            $this->assertSame('INV-BARU', $audit->nilai_baru);
            $this->assertSame('uji_koreksi', $audit->tabel_target);

            $this->assertSame(
                'INV-LAMA',
                ModelUjiKoreksi::find($baris->id)->no_invoice,
                'Baris targetnya ikut berubah. Modul ini mencatat, bukan mengeksekusi.'
            );
        } finally {
            $this->bereskanTabelUji();
        }
    }

    /**
     * Nilai lama yang sudah tidak cocok dengan database ditolak.
     *
     * Catatan yang menyebut nilai lama yang sebenarnya sudah lama tidak ada
     * lebih buruk daripada tidak ada catatan: pembacanya akan menyimpulkan
     * perubahan yang tidak pernah terjadi.
     */
    #[Test]
    public function nilai_lama_yang_sudah_basi_ditolak(): void
    {
        $this->siapkanTabelUji();

        try {
            $baris = ModelUjiKoreksi::create([
                'kode_transaksi' => 'UJI-2',
                'no_invoice' => 'INV-SEKARANG',
                'qty' => 10,
            ]);

            $this->expectException(PerbaikanDataDitolak::class);

            $this->service->terapkan([
                'modul' => 'uji_koreksi',
                'modul_id' => $baris->id,
                'field' => 'no_invoice',
                'nilai_lama' => 'INV-YANG-SUDAH-LAMA-BERUBAH',
                'nilai_baru' => 'INV-BARU',
                'alasan' => 'salah ketik',
            ]);
        } finally {
            $this->bereskanTabelUji();
        }
    }

    /**
     * Nilai baru yang sama dengan yang tersimpan tidak perlu dicatat.
     */
    #[Test]
    public function nilai_baru_yang_sama_ditolak(): void
    {
        $this->siapkanTabelUji();

        try {
            $baris = ModelUjiKoreksi::create([
                'kode_transaksi' => 'UJI-3',
                'no_invoice' => 'INV-A',
                'qty' => 10,
            ]);

            $this->expectException(PerbaikanDataDitolak::class);

            $this->service->terapkan([
                'modul' => 'uji_koreksi',
                'modul_id' => $baris->id,
                'field' => 'no_invoice',
                'nilai_lama' => 'INV-A',
                'nilai_baru' => 'INV-A',
                'alasan' => 'tidak ada yang berubah',
            ]);
        } finally {
            $this->bereskanTabelUji();
        }
    }

    /**
     * Daftar kolom mengikuti apa yang tampil di layar, bukan isi tabel database.
     *
     * Menggantikan test lama yang menuntut kolom database ikut terdaftar sendiri
     * dari skema. Premis itu sudah dibalik: dropdown yang terisi kolom mesin
     * — `details` berisi JSON alokasi FIFO, `used_materials`, `qty_input`
     * sebelum konversi satuan — meminta pengaju memilih sesuatu yang tidak
     * pernah dia lihat.
     *
     * Diuji lewat modul nyata, bukan tabel karangan: yang dijaga justru isi
     * kurasinya, dan kurasi hanya bisa salah pada modul yang sebenarnya ada.
     */
    #[Test]
    public function kolom_mesin_dan_approval_tidak_ikut_didaftar(): void
    {
        $terlarang = [
            'details', 'new_details', 'details_usd', 'new_details_usd',
            'used_materials', 'satuan_input', 'qty_input', 'penanggungjawabaset',
        ];

        foreach (config('perbaikan_data.modul') as $slug => $modul) {
            foreach ((array) ($modul['field'] ?? []) as $field => $definisi) {
                $this->assertNotContains(
                    $field,
                    $terlarang,
                    "Kolom mesin {$slug}.{$field} ikut terdaftar. Tidak pernah tampil di layar mana pun."
                );

                $this->assertFalse(
                    $field === 'status'
                        || str_starts_with($field, 'status_')
                        || str_starts_with($field, 'tgl_approve_'),
                    "Kolom approval {$slug}.{$field} ikut terdaftar. Status lahir dari orang menekan "
                    . 'setuju atau tolak, bukan dari salah ketik.'
                );

                // Awalan `id_` diperiksa hanya untuk kolom angka. Gaya
                // penamaan itu dipakai dua arti di database ini: foreign key
                // (`id_qc_bahan_masuk`, bigint) dan identitas perangkat yang
                // memang diketik orang (`id_bluetooth`, `id_logger`, varchar).
                // Menyapu keduanya akan menutup kolom yang justru sering salah
                // ketik.
                $relasi = str_ends_with($field, '_id')
                    || (str_starts_with($field, 'id_') && ($definisi['tipe'] ?? '') === 'decimal');

                $this->assertFalse(
                    $relasi,
                    "Kolom relasi {$slug}.{$field} ikut terdaftar. Memindahkan baris ke induk lain "
                    . 'bukan koreksi salah ketik.'
                );
            }
        }
    }

    /**
     * Setiap kolom terdaftar harus punya label dan tipe.
     *
     * Daftarnya sekarang ditulis tangan di config, tidak lagi dilengkapi dari
     * skema. Yang dulu dijamin mesin — label selalu ada, tipe selalu terisi
     * — sekarang bergantung pada ketelitian yang menulisnya.
     */
    #[Test]
    public function setiap_kolom_punya_label_dan_tipe(): void
    {
        $tipeSah = ['string', 'text', 'decimal', 'datetime'];

        foreach (config('perbaikan_data.modul') as $slug => $modul) {
            foreach ((array) ($modul['field'] ?? []) as $field => $definisi) {
                $this->assertNotEmpty($definisi['label'] ?? '', "Kolom {$slug}.{$field} tanpa label.");
                $this->assertContains(
                    $definisi['tipe'] ?? '',
                    $tipeSah,
                    "Kolom {$slug}.{$field} tipenya tidak dikenali."
                );
            }
        }
    }

    /**
     * Jenis yang tidak dicentang sama sekali tidak menghasilkan pilihan.
     *
     * Form mencari record berdasarkan jenis yang dicentang, sebelum kolomnya
     * dipilih. Tanpa jenis, tidak ada tabel yang boleh dicari — mengembalikan
     * daftar kosong, bukan menyapu seluruh modul.
     */
    #[Test]
    public function tanpa_jenis_tidak_ada_record_yang_dicari(): void
    {
        $this->assertSame([], $this->service->opsiRecordJenis([]));
        $this->assertSame([], $this->service->opsiRecordJenis(['Jenis Yang Tidak Ada']));
    }

    /**
     * Kolom membawa nama tabelnya, dan itu yang dipakai form menyaring.
     *
     * Bukan nama modul. Tiga modul Pembelian Bahan menunjuk baris yang sama —
     * dipisah hanya supaya kolom biaya impor punya label yang terbaca. Kalau
     * form menyaring per modul, memilih satu transaksi Pembelian Bahan hanya
     * memunculkan sepertiga kolom yang sebenarnya milik baris itu.
     */
    #[Test]
    public function modul_yang_menunjuk_tabel_sama_berbagi_kolom(): void
    {
        $sekeluarga = [
            'pembelian_bahan',
            'pembelian_bahan_biaya_diajukan',
            'pembelian_bahan_biaya_dibayarkan',
        ];

        $tabel = array_map(fn ($slug) => $this->service->tabelModul($slug), $sekeluarga);

        $this->assertSame(
            [$tabel[0]],
            array_values(array_unique($tabel)),
            'Ketiganya harus menunjuk tabel yang sama; kalau tidak, pemisahannya bukan lagi soal label.'
        );

        $kolom = array_filter(
            $this->service->katalogKolom(),
            fn ($k) => $k['tabel'] === $tabel[0]
        );

        $modulTerwakili = array_unique(array_column($kolom, 'modul'));
        sort($modulTerwakili);
        sort($sekeluarga);

        $this->assertSame(
            $sekeluarga,
            $modulTerwakili,
            'Menyaring per tabel harus mengumpulkan kolom ketiga modulnya sekaligus.'
        );
    }

    /**
     * Setiap kolom di katalog membawa tabelnya.
     *
     * Kalau satu saja tidak, kolom itu tidak akan pernah muncul di form:
     * penyaringnya membandingkan tabel, dan tabel kosong tidak cocok dengan apa
     * pun. Gagalnya senyap — kolomnya sekadar tidak ada di dropdown.
     */
    #[Test]
    public function setiap_kolom_katalog_membawa_tabelnya(): void
    {
        foreach ($this->service->katalogKolom() as $kolom) {
            $this->assertNotEmpty(
                $kolom['tabel'] ?? '',
                "Kolom {$kolom['nilai']} tidak membawa nama tabel."
            );
        }
    }

    private function normalkan($nilai, string $tipe): ?string
    {
        $method = new ReflectionMethod(PerbaikanDataService::class, 'normalkan');
        $method->setAccessible(true);

        return $method->invoke($this->service, $nilai, $tipe);
    }

    /**
     * Kolom yang tidak ada di config maupun di tabelnya tetap ditolak.
     *
     * Daftar kolomnya sekarang gabungan dua sumber — config dan skema tabel
     * — karena modul ini terutama MENCATAT koreksi, dan kolom yang tidak bisa
     * dipilih berarti perubahannya terjadi tanpa jejak. Yang tidak ikut longgar
     * adalah nama kolom karangan: salah ketik harus gagal saat mengisi form,
     * bukan tersimpan jadi baris audit yang menunjuk kolom yang tidak ada.
     */
    #[Test]
    public function kolom_karangan_tetap_ditolak(): void
    {
        $this->expectException(PerbaikanDataDitolak::class);
        $this->service->definisiField('purchases', 'kolom_yang_tidak_pernah_ada');
    }

    #[Test]
    public function kolom_teks_tidak_menuntut_lampiran(): void
    {
        $this->assertFalse($this->service->wajibLampiran('bahan_keluars', 'keterangan'));
    }

    #[Test]
    public function katalog_kolom_memakai_label_yang_sama_dengan_halaman_audit(): void
    {
        $katalog = collect($this->service->katalogKolom());

        // Label pilihan di form menggabungkan label modul dan label kolom.
        // Keduanya harus tetap label yang sama dengan yang dibaca halaman audit
        // dari config, supaya satu koreksi tidak disebut dua nama berbeda di dua
        // halaman.
        $noInvoice = $katalog->firstWhere('nilai', 'purchases::no_invoice');

        $this->assertNotNull($noInvoice);
        $this->assertStringStartsWith('Bahan Masuk', $noInvoice['label']);
        $this->assertStringEndsWith('No Invoice', $noInvoice['label']);

        // Kolom uang pada lot bahan masuk harus tetap bisa dipilih.
        $this->assertNotNull($katalog->firstWhere('nilai', 'purchase_details::unit_price'));

        // Modul yang kolomnya tidak pernah dibuat tidak boleh muncul sebagai
        // pilihan: form-nya akan menawarkan koreksi yang pasti gagal.
        $this->assertNull($katalog->firstWhere('modul', 'projeks'));
    }

    #[Test]
    public function koreksi_tanpa_data_wajib_ditolak(): void
    {
        $this->expectException(PerbaikanDataDitolak::class);
        $this->service->terapkan(['modul' => 'purchases']);
    }

    #[Test]
    public function koreksi_tanpa_nilai_lama_ditolak(): void
    {
        $this->expectException(PerbaikanDataDitolak::class);
        $this->expectExceptionMessageMatches('/nilai lama/');

        $this->service->terapkan([
            'modul' => 'purchases',
            'modul_id' => 1,
            'field' => 'no_invoice',
            'alasan' => 'salah ketik',
            'nilai_baru' => 'INV-2',
        ]);
    }

    #[Test]
    public function angka_dengan_bentuk_berbeda_dianggap_sama(): void
    {
        $this->assertSame(
            $this->normalkan('1000.00', 'decimal'),
            $this->normalkan(1000, 'decimal')
        );
    }

    #[Test]
    public function pecahan_harga_dipertahankan_sampai_empat_desimal(): void
    {
        // Harga per cm bahan batangan sampai empat desimal; memotongnya lebih
        // awal akan membuat koreksi yang sah tertolak karena dianggap berubah.
        $this->assertSame('291.6667', $this->normalkan(291.66670, 'decimal'));
        $this->assertSame('291.6', $this->normalkan('291.6000', 'decimal'));
    }

    #[Test]
    public function tanggal_diseragamkan_bentuknya(): void
    {
        $this->assertSame(
            $this->normalkan('2026-09-01', 'datetime'),
            $this->normalkan('2026-09-01 00:00:00', 'datetime')
        );
    }

    #[Test]
    public function nilai_kosong_jadi_null_bukan_string_kosong(): void
    {
        $this->assertNull($this->normalkan('', 'string'));
        $this->assertNull($this->normalkan(null, 'decimal'));
    }

    /**
     * Penyambung checkbox Jenis Pengajuan dengan dropdown kode transaksi.
     *
     * Diuji karena kegagalannya tidak menimbulkan error apa pun: satu label
     * jenis yang salah tulis di kunci `jenis` sebuah modul hanya membuat
     * dropdown kodenya kosong, dan itu terlihat seperti "tidak ada datanya"
     * alih-alih salah konfigurasi. Persis begitu bentuk bug yang dilaporkan
     * pertama kali.
     */
    /**
     * Setiap kolom di daftar putih harus bisa dipilih dari form, dan sebaliknya.
     *
     * Bug yang dilaporkan pertama kali bentuknya persis begini: kolom
     * `pengajuan.keterangan` ada di daftar putih tapi tidak bisa dijangkau dari
     * form mana pun. Tidak ada error, tidak ada pesan — pilihannya cuma tidak
     * pernah muncul. Dijaga dari dua arah supaya tambahan kolom baru tidak bisa
     * menganggur, dan pilihan hantu tidak bisa muncul tanpa daftar putihnya.
     */
    #[Test]
    public function katalog_kolom_memuat_tepat_semua_kolom_di_daftar_putih(): void
    {
        $katalog = collect($this->service->katalogKolom());
        $jumlah = 0;

        foreach (config('perbaikan_data.modul') as $slug => $modul) {
            foreach (array_keys((array) ($modul['field'] ?? [])) as $field) {
                $jumlah++;

                $this->assertNotNull(
                    $katalog->firstWhere('nilai', $slug . '::' . $field),
                    "Kolom {$slug}.{$field} ada di daftar putih tapi tidak muncul sebagai pilihan di form."
                );
            }
        }

        $this->assertCount(
            $jumlah,
            $katalog,
            'Katalog kolom memuat pilihan yang tidak ada di daftar putih.'
        );
    }

    #[Test]
    public function setiap_jenis_pengajuan_punya_kunci_di_peta_modul(): void
    {
        $peta = $this->service->modulPerJenis();

        foreach ($this->service->jenisPengajuan() as $jenis) {
            $this->assertArrayHasKey(
                $jenis,
                $peta,
                "Jenis '{$jenis}' tidak punya kunci di peta modul."
            );
        }
    }

    #[Test]
    public function kunci_jenis_pada_modul_harus_ada_di_daftar_jenis_pengajuan(): void
    {
        $daftarJenis = $this->service->jenisPengajuan();

        foreach (config('perbaikan_data.modul') as $slug => $modul) {
            foreach ((array) ($modul['jenis'] ?? []) as $jenis) {
                $this->assertContains(
                    $jenis,
                    $daftarJenis,
                    "Modul '{$slug}' menunjuk jenis '{$jenis}' yang tidak ada di daftar jenis pengajuan, "
                    . 'jadi modulnya tidak akan pernah muncul di form.'
                );
            }
        }
    }

    #[Test]
    public function setiap_modul_terjangkau_dari_minimal_satu_jenis(): void
    {
        $terjangkau = collect($this->service->modulPerJenis())->flatten()->unique()->all();

        foreach (array_keys(config('perbaikan_data.modul')) as $slug) {
            $this->assertContains(
                $slug,
                $terjangkau,
                "Modul '{$slug}' ada di daftar putih tapi tidak bisa dipilih dari jenis mana pun."
            );
        }
    }

    #[Test]
    public function jenis_bahan_masuk_memunculkan_transaksi_dan_lot_harganya(): void
    {
        $peta = $this->service->modulPerJenis();

        // Satu jenis boleh menunjuk lebih dari satu modul: koreksi bahan masuk
        // bisa menyentuh header transaksinya atau harga per lotnya.
        $this->assertEqualsCanonicalizing(
            ['purchases', 'purchase_details'],
            $peta['Transaksi - Bahan Masuk']
        );
    }

    /**
     * Tidak boleh ada jenis pengajuan yang pilihan kolomnya kosong.
     *
     * Dulu 13 dari 19 jenis tidak punya modul sama sekali, dan di layar itu
     * terbaca "tidak ada datanya" alih-alih "belum dikonfigurasi". Sekarang
     * semuanya punya, dan test ini yang menjaga jenis baru tidak ditambahkan
     * tanpa modulnya.
     */
    #[Test]
    public function tidak_ada_jenis_pengajuan_yang_tanpa_kolom(): void
    {
        $kosong = [];

        foreach ($this->service->modulPerJenis() as $jenis => $modul) {
            if ($modul === []) {
                $kosong[] = $jenis;
            }
        }

        $this->assertSame(
            [],
            $kosong,
            'Jenis pengajuan tanpa satu pun kolom yang bisa dikoreksi: ' . implode(', ', $kosong)
        );
    }

    /**
     * Kolom yang isinya di dalam JSON tetap terbaca nilai lamanya.
     *
     * Harga satuan baris pembelian tidak punya kolom sendiri; nilainya di
     * dalam `details`. Tanpa pembacaan ini kolomnya masih muncul di dropdown,
     * tapi nilai lamanya selalu kosong — dan nilai lama kosong bukan cuma
     * jelek dipandang, ia melumpuhkan pemeriksaan di test berikutnya.
     */
    #[Test]
    public function nilai_lama_kolom_di_dalam_json_terbaca(): void
    {
        $this->siapkanTabelUji();

        try {
            $baris = ModelUjiKoreksi::create([
                'kode_transaksi' => 'UJI-JSON',
                'details' => json_encode(['qty' => 3, 'unit_price' => 185]),
            ]);

            $this->assertSame(
                '185',
                $this->service->nilaiSekarang('uji_koreksi', $baris->id, 'unit_price')
            );
        } finally {
            $this->bereskanTabelUji();
        }
    }

    /**
     * Daftar pilihan record ikut membawa nilai kolom JSON-nya.
     *
     * Jalur yang berbeda dari nilaiSekarang() dan gampang tertinggal. Kotak
     * "nilai sekarang" di form pengajuan tidak memanggil service per baris;
     * ia membaca nilai yang sudah ikut terkirim bersama daftar pilihan record.
     * Kalau hanya nilaiSekarang() yang bisa membaca JSON, kolomnya tampil di
     * dropdown tapi kotak nilainya tetap kosong saat dipilih — dan pengaju
     * mengira kolomnya rusak.
     */
    #[Test]
    public function daftar_pilihan_record_membawa_nilai_kolom_json(): void
    {
        $this->siapkanTabelUji();

        try {
            ModelUjiKoreksi::create([
                'kode_transaksi' => 'UJI-JSON',
                'details' => json_encode(['qty' => 3, 'unit_price' => 185]),
            ]);

            $opsi = $this->service->opsiRecord('uji_koreksi');

            $this->assertSame('185', $opsi[0]['nilai']['unit_price']);
        } finally {
            $this->bereskanTabelUji();
        }
    }

    /**
     * Harga yang keburu berubah sejak diajukan tetap ketahuan.
     *
     * Inti gunanya membaca isi JSON. Kalau nilai lamanya selalu null,
     * pemeriksaan ini membandingkan null dengan null, selalu lolos, dan baris
     * auditnya mencatat perpindahan nilai yang tidak pernah terjadi.
     */
    #[Test]
    public function koreksi_harga_json_yang_nilainya_sudah_berubah_ditolak(): void
    {
        $this->siapkanTabelUji();

        try {
            $baris = ModelUjiKoreksi::create([
                'kode_transaksi' => 'UJI-JSON',
                'details' => json_encode(['qty' => 3, 'unit_price' => 200]),
            ]);

            $this->expectException(PerbaikanDataDitolak::class);

            $this->service->terapkan([
                'modul' => 'uji_koreksi',
                'modul_id' => $baris->id,
                'field' => 'unit_price',
                'nilai_lama' => 185,
                'nilai_baru' => 230,
                'alasan' => 'harga satuan tertukar dengan baris lain',
            ]);
        } finally {
            $this->bereskanTabelUji();
        }
    }

    /**
     * JSON yang isinya daftar tidak dipaksa jadi satu angka.
     *
     * Sebagian kolom `details` menyimpan satu baris per lot alokasi. Di sana
     * "harga satuan" bukan satu nilai, dan mengambil elemen pertamanya akan
     * mencatat nilai lama yang kelihatan benar padahal cuma sebagian.
     */
    #[Test]
    public function json_berbentuk_daftar_tidak_dibaca_sebagai_satu_nilai(): void
    {
        $this->siapkanTabelUji();

        try {
            $baris = ModelUjiKoreksi::create([
                'kode_transaksi' => 'UJI-JSON',
                'details' => json_encode([
                    ['qty' => 2, 'unit_price' => 185],
                    ['qty' => 1, 'unit_price' => 230],
                ]),
            ]);

            $this->assertNull(
                $this->service->nilaiSekarang('uji_koreksi', $baris->id, 'unit_price')
            );
        } finally {
            $this->bereskanTabelUji();
        }
    }
}

/**
 * Model seadanya untuk tabel uji. Tidak ada padanannya di aplikasi.
 */
class ModelUjiKoreksi extends Model
{
    protected $table = 'uji_koreksi';

    protected $guarded = [];
}
