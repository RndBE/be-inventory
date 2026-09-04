<?php

use App\Models\BahanKeluar;
use App\Models\BahanKeluarDetails;
use App\Models\BahanReturDetails;
use App\Models\GaransiProjekDetails;
use App\Models\PengambilanBahanDetails;
use App\Models\ProdukSampleDetails;
use App\Models\ProduksiDetails;
use App\Models\ProduksiProdukJadiDetails;
use App\Models\ProjekDetails;
use App\Models\ProjekRndDetails;
use App\Models\QcBahanMasukDetails;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanRusakDetails;
use App\Models\BahanSetengahjadi;
use App\Models\BahanSetengahjadiDetails;
use App\Models\GaransiProjek;
use App\Models\LaporanGaransiProyek;
use App\Models\LaporanProyek;
use App\Models\PembelianBahan;
use App\Models\PembelianBahanDetails;
use App\Models\Pengajuan;
use App\Models\PengambilanBahan;
use App\Models\ProdukJadi;
use App\Models\ProdukJadiDetails;
use App\Models\ProdukSample;
use App\Models\Produksi;
use App\Models\ProduksiProdukJadi;
use App\Models\Projek;
use App\Models\ProjekRnd;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\QcBahanMasuk;
use App\Models\QcProdukJadiList;
use App\Models\QcProdukSetengahJadiList;
use App\Models\StockOpname;

/**
 * Kurasi kolom untuk modul Perbaikan Data.
 *
 * PENTING: berkas ini menentukan seluruh isi dropdown "Data yang ingin
 * diubah". Yang tidak ditulis di sini tidak bisa dipilih, dan kolom yang tidak
 * bisa dipilih berarti perubahannya terjadi tanpa jejak — persis keadaan yang
 * modul ini ada untuk mencegahnya. Jadi kalau ada kolom yang tampil di layar
 * tapi tidak muncul di form, itu bukan penyaringan yang disengaja, melainkan
 * kolom yang belum sempat ditulis ke sini.
 *
 * (Berkas ini pernah cuma jadi pelengkap label, dengan sisa kolom dibaca
 * sendiri dari skema. Itu sudah tidak berlaku; PerbaikanDataService::fieldModul()
 * sekarang membaca berkas ini saja.)
 *
 * Modul Perbaikan Data mencatat koreksi, tidak menjalankannya — perubahan
 * datanya dikerjakan tim software langsung di database.
 *
 * Yang tidak pernah ikut, karena bukan data yang bisa salah ketik: primary key,
 * kolom relasi (`*_id`), dan `created_at` / `updated_at` / `deleted_at`.
 *
 * GUNANYA MENULIS KOLOM DI SINI
 *
 * Empat hal yang tidak bisa disimpulkan dari skema:
 *
 * - `label`. Nama yang terbaca orang. Tanpa ini labelnya dibuat apa adanya dari
 *   nama kolom (`tgl_approve_leader` jadi "Tgl Approve Leader") — cukup untuk
 *   dikenali, tapi kalah enak dibaca daripada "Tanggal Approve Leader".
 * - `tipe`. Bentuk nilai yang divalidasi: string, text, decimal, datetime.
 *   Ditebak dari tipe kolom kalau tidak ditulis, dan tebakannya benar untuk
 *   hampir semua kolom — kecuali kolom teks yang isinya sebenarnya tanggal.
 * - `wajib_lampiran`. Pengajuan ditolak tanpa lampiran bukti. Untuk kolom uang
 *   dan jumlah, approver tidak punya dasar memverifikasi angka tanpa dokumen.
 *   Ini satu-satunya penjagaan yang masih benar-benar berlaku di sini.
 * - Urutan. Kolom yang ditulis di sini muncul lebih dulu di dropdown, jadi yang
 *   paling sering dikoreksi tidak perlu dicari di tengah puluhan kolom lain.
 * - `json`. Untuk nilai yang tampil di layar sebagai kolom tersendiri padahal
 *   disimpan di dalam kolom JSON, mis. harga satuan baris pembelian yang
 *   tinggal di `details`. Isinya `['kolom' => 'details', 'key' => 'unit_price']`:
 *   kolom pembungkusnya dan kunci di dalamnya. Nama kunci di berkas ini boleh
 *   sama dengan nama kunci JSON-nya — yang penting nama itu unik di dalam satu
 *   modul, karena dipakai sebagai identitas kolom di baris audit.
 *
 *   Hanya untuk JSON yang isinya satu objek datar. JSON yang isinya daftar
 *   (`bahan_keluar_details.details` berisi satu baris per lot alokasi FIFO)
 *   tidak bisa ditunjuk begini: nilainya bukan satu, jadi tidak ada satu angka
 *   yang bisa dicatat sebagai nilai lama.
 *
 * Kunci di tingkat modul:
 *
 * - `label`       nama modul, jadi awalan label kolom: "<modul> — <kolom>".
 * - `model`       kelas Eloquent-nya. Menentukan tabel dan primary key.
 * - `kode`        kolom kode yang dicari di dropdown pemilih record.
 * - `jenis`       label "Jenis Pengajuan" yang memunculkan modul ini di form.
 * - `induk`       untuk tabel detail: relasi dan kolom kode milik induknya.
 * - `label_relasi` / `label_kolom`  tambahan label pada pilihan kode, supaya
 *   baris detail yang kode induknya sama tetap bisa dibedakan.
 * - `lengkapi`    setel false kalau modulnya sengaja menunjuk tabel yang sama
 *   dengan modul lain hanya untuk mengelompokkan kolom (mis. biaya impor
 *   diajukan lawan dibayarkan). Tanpa itu tiap kolom tabel tersebut muncul
 *   berulang di dropdown dengan awalan berbeda.
 *
 * CATATAN LAMA YANG SUDAH TIDAK BERLAKU
 *
 * Berkas ini pernah memuat penjagaan `hanya_lot_utuh`, `hitung_ulang`, dan
 * `stok`, dari masa ketika aplikasi ikut menulis koreksinya. Semuanya sudah
 * dicabut bersama mesin eksekusinya. Kalau nanti eksekusi otomatis dihidupkan
 * lagi, penjagaan itu harus ditulis ulang — bukan dihidupkan dari catatan ini —
 * karena alasan tiap kolom perlu diperiksa ulang terhadap keadaan data saat itu.
 */

return [

    'modul' => [

        // ================= Bahan Masuk =================

        'purchases' => [
            'label' => 'Bahan Masuk',
            'model' => Purchase::class,
            'kode' => 'kode_transaksi',
            'jenis' => ['Transaksi - Bahan Masuk'],
            'field' => [
                'no_invoice' => ['label' => 'No Invoice', 'tipe' => 'string'],
                'tgl_masuk' => ['label' => 'Tanggal Masuk', 'tipe' => 'datetime'],
                'kode_transaksi' => ['label' => 'Kode Transaksi', 'tipe' => 'string'],
            ],
        ],

        'purchase_details' => [
            'label' => 'Lot Bahan Masuk',
            'model' => PurchaseDetail::class,
            'jenis' => ['Transaksi - Bahan Masuk'],
            'induk' => [
                'relasi' => 'purchase',
                'kode' => 'kode_transaksi',
            ],
            // Baris detail tidak punya kode sendiri, jadi kode induknya saja
            // tidak cukup untuk membedakan: satu bahan masuk bisa berisi
            // sepuluh lot. Nama bahannya ditempelkan ke label pilihan supaya
            // pengaju tahu lot mana yang dipilihnya.
            'label_relasi' => [
                'relasi' => 'dataBahan',
                'kolom' => 'nama_bahan',
            ],
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'unit_price' => ['label' => 'Harga per Unit', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'sisa' => ['label' => 'Sisa', 'tipe' => 'decimal'],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
            ],
        ],

        // ================= Transaksi lain =================

        'pembelian_bahan' => [
            'label' => 'Pengajuan Pembelian Bahan',
            'model' => PembelianBahan::class,
            'kode' => 'kode_transaksi',
            'jenis' => ['Transaksi - Pembelian Bahan'],
            'field' => [
                'keterangan' => ['label' => 'Keterangan', 'tipe' => 'text'],
                'catatan' => ['label' => 'Catatan', 'tipe' => 'string'],
                'link' => ['label' => 'Tautan', 'tipe' => 'string'],
                'tujuan' => ['label' => 'Tujuan', 'tipe' => 'string'],
                'divisi' => ['label' => 'Divisi', 'tipe' => 'string'],
                'tgl_pengajuan' => ['label' => 'Tanggal Pengajuan', 'tipe' => 'datetime'],
                'tgl_keluar' => ['label' => 'Tanggal Keluar', 'tipe' => 'datetime'],
                'ongkir' => ['label' => 'Ongkir', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'asuransi' => ['label' => 'Asuransi', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'layanan' => ['label' => 'Biaya Layanan', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'jasa_aplikasi' => ['label' => 'Jasa Aplikasi', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'ppn' => ['label' => 'PPN', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'kode_transaksi' => ['label' => 'Kode Transaksi', 'tipe' => 'string'],
                'pengaju' => ['label' => 'Pengaju', 'tipe' => 'decimal'],
                'dokumen' => ['label' => 'Dokumen', 'tipe' => 'string'],
                'jenis_pengajuan' => ['label' => 'Jenis Pengajuan', 'tipe' => 'string'],
                'shipping_cost' => ['label' => 'Shipping Cost', 'tipe' => 'decimal'],
                'full_amount_fee' => ['label' => 'Full Amount Fee', 'tipe' => 'decimal'],
                'value_today_fee' => ['label' => 'Value Today Fee', 'tipe' => 'decimal'],
                'shipping_cost_usd' => ['label' => 'Shipping Cost Usd', 'tipe' => 'decimal'],
                'full_amount_fee_usd' => ['label' => 'Full Amount Fee Usd', 'tipe' => 'decimal'],
                'value_today_fee_usd' => ['label' => 'Value Today Fee Usd', 'tipe' => 'decimal'],
                'new_shipping_cost' => ['label' => 'New Shipping Cost', 'tipe' => 'decimal'],
                'new_full_amount_fee' => ['label' => 'New Full Amount Fee', 'tipe' => 'decimal'],
                'new_value_today_fee' => ['label' => 'New Value Today Fee', 'tipe' => 'decimal'],
                'new_shipping_cost_usd' => ['label' => 'New Shipping Cost Usd', 'tipe' => 'decimal'],
                'new_full_amount_fee_usd' => ['label' => 'New Full Amount Fee Usd', 'tipe' => 'decimal'],
                'new_value_today_fee_usd' => ['label' => 'New Value Today Fee Usd', 'tipe' => 'decimal'],
            ],
        ],

        /**
         * Biaya impor dipisah jadi dua modul, bukan satu modul dengan dua belas
         * kolom bertanda kurung.
         *
         * Tiap biaya impor punya sepasang kolom, dan keduanya angka yang
         * berbeda — bukan salinan:
         *
         *   `shipping_cost`      nominal yang DIAJUKAN saat pengajuan dibuat
         *   `new_shipping_cost`  nominal yang benar-benar DIBAYARKAN
         *
         * PembelianBahanExport memakai yang kedua kalau isinya lebih dari nol,
         * dan jatuh ke yang pertama kalau kosong; hasilnya yang masuk kolom
         * "Nominal yang dibayar" dan ikut menjumlah total transaksi. Jadi
         * dua-duanya perlu bisa dikoreksi: salah ketik di angka pengajuan
         * mengubah dokumen pengajuannya, salah ketik di angka dibayarkan
         * mengubah total yang dibukukan.
         *
         * Dipisah karena label pilihan berbentuk "<modul> — <kolom>", dan yang
         * dibaca mata lebih dulu bagian depannya. Digabung, dua belas barisnya
         * jadi kalimat panjang yang cuma beda di ujung — "Pengajuan Pembelian
         * Bahan — Shipping Cost (diajukan)" berhadapan dengan "… (dibayarkan)".
         * Dipisah, pembedanya pindah ke depan dan keduanya berkelompok sendiri.
         *
         * Ketiganya menunjuk model dan kode yang sama; yang berbeda hanya kolom
         * yang boleh disentuh. Modul di sini memang penanda kelompok kolom,
         * bukan wajib satu tabel satu modul.
         */
        'pembelian_bahan_biaya_diajukan' => [
            'label' => 'Biaya Impor Diajukan',
            'model' => PembelianBahan::class,
            'kode' => 'kode_transaksi',
            'jenis' => ['Transaksi - Pembelian Bahan'],
            'lengkapi' => false,
            'field' => [
                'shipping_cost' => ['label' => 'Shipping Cost', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'full_amount_fee' => ['label' => 'Full Amount Fee', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'value_today_fee' => ['label' => 'Value Today Fee', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'shipping_cost_usd' => ['label' => 'Shipping Cost USD', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'full_amount_fee_usd' => ['label' => 'Full Amount Fee USD', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'value_today_fee_usd' => ['label' => 'Value Today Fee USD', 'tipe' => 'decimal', 'wajib_lampiran' => true],
            ],
        ],

        'pembelian_bahan_biaya_dibayarkan' => [
            'label' => 'Biaya Impor Dibayarkan',
            'model' => PembelianBahan::class,
            'kode' => 'kode_transaksi',
            'jenis' => ['Transaksi - Pembelian Bahan'],
            'lengkapi' => false,
            'field' => [
                'new_shipping_cost' => ['label' => 'Shipping Cost', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'new_full_amount_fee' => ['label' => 'Full Amount Fee', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'new_value_today_fee' => ['label' => 'Value Today Fee', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'new_shipping_cost_usd' => ['label' => 'Shipping Cost USD', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'new_full_amount_fee_usd' => ['label' => 'Full Amount Fee USD', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'new_value_today_fee_usd' => ['label' => 'Value Today Fee USD', 'tipe' => 'decimal', 'wajib_lampiran' => true],
            ],
        ],

        'pembelian_bahan_details' => [
            'label' => 'Baris Pengajuan Pembelian',
            'model' => PembelianBahanDetails::class,
            'jenis' => ['Transaksi - Pembelian Bahan'],
            'induk' => [
                'relasi' => 'pembelianBahan',
                'kode' => 'kode_transaksi',
            ],
            // Relasi lebih dulu, kolomnya sebagai cadangan: `nama_bahan` di
            // baris ini kosong pada sebagian besar data, namanya ada di tabel
            // bahan.
            'label_relasi' => [
                'relasi' => 'dataBahan',
                'kolom' => 'nama_bahan',
            ],
            'label_kolom' => 'nama_bahan',
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'qty_pengajuan' => ['label' => 'Jumlah Diajukan', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'spesifikasi' => ['label' => 'Spesifikasi', 'tipe' => 'text'],
                'keterangan_pembayaran' => ['label' => 'Keterangan Pembayaran', 'tipe' => 'string'],
                'alasan' => ['label' => 'Alasan', 'tipe' => 'text'],
                'nama_bahan' => ['label' => 'Nama Bahan', 'tipe' => 'string'],
                'jml_bahan' => ['label' => 'Kebutuhan', 'tipe' => 'decimal'],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
                // Harga satuan bukan kolom tabel: nilainya tinggal di dalam
                // JSON `details` / `details_usd`. Lihat penjelasan kunci `json`
                // di kepala berkas ini.
                //
                // Rupiah dan mata uang asing diisi terpisah oleh pengguna dan
                // tidak ada kurs yang tersimpan di baris ini, jadi keduanya
                // kolom yang berdiri sendiri. Koreksi harga USD tidak dengan
                // sendirinya membetulkan harga rupiahnya — kalau dua-duanya
                // salah, dua-duanya harus diajukan.
                'unit_price' => [
                    'label' => 'Harga Satuan (Rp)',
                    'tipe' => 'decimal',
                    'wajib_lampiran' => true,
                    'json' => ['kolom' => 'details', 'key' => 'unit_price'],
                ],
                'unit_price_usd' => [
                    'label' => 'Harga Satuan (Mata Uang Asing)',
                    'tipe' => 'decimal',
                    'wajib_lampiran' => true,
                    'json' => ['kolom' => 'details_usd', 'key' => 'unit_price_usd'],
                ],
            ],
        ],

        'bahan_keluar_details' => [
            'label' => 'Baris Bahan Keluar',
            'model' => BahanKeluarDetails::class,
            'jenis' => ['Transaksi - Bahan Keluar'],
            'induk' => [
                'relasi' => 'bahanKeluar',
                'kode' => 'kode_transaksi',
            ],
            'label_relasi' => [
                'relasi' => 'dataBahan',
                'kolom' => 'nama_bahan',
            ],
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'jml_bahan' => ['label' => 'Kebutuhan', 'tipe' => 'decimal'],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
                'serial_number' => ['label' => 'Serial Number', 'tipe' => 'string'],
            ],
        ],

        'produksi_details' => [
            'label' => 'Baris Produksi Produk Setengah Jadi',
            'model' => ProduksiDetails::class,
            'jenis' => ['Produksi Produk Setengah Jadi'],
            'induk' => [
                'relasi' => 'produksis',
                'kode' => 'kode_produksi',
            ],
            'label_relasi' => [
                'relasi' => 'dataBahan',
                'kolom' => 'nama_bahan',
            ],
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'jml_bahan' => ['label' => 'Kebutuhan', 'tipe' => 'decimal'],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
            ],
        ],

        'projek_details' => [
            'label' => 'Baris Bahan Proyek',
            'model' => ProjekDetails::class,
            'jenis' => ['Proyek'],
            'induk' => [
                'relasi' => 'projek',
                'kode' => 'kode_projek',
            ],
            'label_relasi' => [
                'relasi' => 'dataBahan',
                'kolom' => 'nama_bahan',
            ],
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
                'serial_number' => ['label' => 'Serial Number', 'tipe' => 'string'],
            ],
        ],

        'projek_rnd_details' => [
            'label' => 'Baris Bahan Proyek RnD',
            'model' => ProjekRndDetails::class,
            'jenis' => ['Proyek RnD'],
            'induk' => [
                'relasi' => 'projekRnd',
                'kode' => 'kode_projek_rnd',
            ],
            'label_relasi' => [
                'relasi' => 'dataBahan',
                'kolom' => 'nama_bahan',
            ],
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'keterangan_penanggungjawab' => ['label' => 'Keterangan Penanggung Jawab', 'tipe' => 'text'],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
                'serial_number' => ['label' => 'Serial Number', 'tipe' => 'string'],
            ],
        ],

        'garansi_projek_details' => [
            'label' => 'Baris Bahan Garansi Proyek',
            'model' => GaransiProjekDetails::class,
            'jenis' => ['Garansi Proyek'],
            'induk' => [
                'relasi' => 'garansiProjek',
                'kode' => 'kode_garansi',
            ],
            'label_relasi' => [
                'relasi' => 'dataBahan',
                'kolom' => 'nama_bahan',
            ],
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
                'serial_number' => ['label' => 'Serial Number', 'tipe' => 'string'],
            ],
        ],

        'produk_sample_details' => [
            'label' => 'Baris Bahan Produk Sample',
            'model' => ProdukSampleDetails::class,
            'jenis' => ['Produk Sample'],
            'induk' => [
                'relasi' => 'produkSample',
                'kode' => 'kode_produk_sample',
            ],
            'label_relasi' => [
                'relasi' => 'dataBahan',
                'kolom' => 'nama_bahan',
            ],
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
                'serial_number' => ['label' => 'Serial Number', 'tipe' => 'string'],
            ],
        ],

        'pengambilan_bahan_details' => [
            'label' => 'Baris Pengambilan Bahan',
            'model' => PengambilanBahanDetails::class,
            'jenis' => ['Pengambilan Bahan Non Proyek/Produksi'],
            'induk' => [
                'relasi' => 'pengambilanBahan',
                'kode' => 'kode_pengajuan',
            ],
            'label_relasi' => [
                'relasi' => 'dataBahan',
                'kolom' => 'nama_bahan',
            ],
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'jml_bahan' => ['label' => 'Kebutuhan', 'tipe' => 'decimal'],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
            ],
        ],

        'produksi_produk_jadi_details' => [
            'label' => 'Baris Produksi Produk Jadi',
            'model' => ProduksiProdukJadiDetails::class,
            'jenis' => ['Produksi Produk Jadi'],
            'induk' => [
                'relasi' => 'produksiProdukJadi',
                'kode' => 'kode_produksi',
            ],
            'label_relasi' => [
                'relasi' => 'dataBahan',
                'kolom' => 'nama_bahan',
            ],
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'serial_number' => ['label' => 'Serial Number', 'tipe' => 'string'],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
            ],
        ],

        'bahan_retur_details' => [
            'label' => 'Baris Bahan Retur',
            'model' => BahanReturDetails::class,
            'jenis' => ['Bahan Retur'],
            'induk' => [
                'relasi' => 'bahanRetur',
                'kode' => 'kode_transaksi',
            ],
            'label_relasi' => [
                'relasi' => 'dataBahan',
                'kolom' => 'nama_bahan',
            ],
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'unit_price' => ['label' => 'Harga per Unit', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
                'serial_number' => ['label' => 'Serial Number', 'tipe' => 'string'],
            ],
        ],

        'qc_bahan_masuk_details' => [
            'label' => 'Baris QC Bahan Masuk',
            'model' => QcBahanMasukDetails::class,
            'jenis' => ['QC Bahan Masuk'],
            'induk' => [
                'relasi' => 'qc',
                'kode' => 'kode_qc',
            ],
            'label_relasi' => [
                'relasi' => 'bahan',
                'kolom' => 'nama_bahan',
            ],
            'field' => [
                'fisik_baik' => ['label' => 'Jumlah Fisik Baik', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'unit_price' => ['label' => 'Harga per Unit', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'no_invoice' => ['label' => 'No Invoice', 'tipe' => 'string'],
                'jumlah_pengajuan' => ['label' => 'Jumlah Pengajuan', 'tipe' => 'decimal'],
                'jumlah_pembelian' => ['label' => 'Jumlah Pembelian', 'tipe' => 'decimal'],
                'stok_lama' => ['label' => 'Stok Lama', 'tipe' => 'decimal'],
                'jumlah_diterima' => ['label' => 'Jumlah Diterima', 'tipe' => 'decimal'],
                'fisik_rusak' => ['label' => 'Fisik Rusak', 'tipe' => 'decimal'],
                'fisik_retur' => ['label' => 'Fisik Retur', 'tipe' => 'decimal'],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
                'notes' => ['label' => 'Catatan QC', 'tipe' => 'text'],
            ],
        ],

        'bahan_keluars' => [
            'label' => 'Bahan Keluar',
            'model' => BahanKeluar::class,
            'kode' => 'kode_transaksi',
            'jenis' => ['Transaksi - Bahan Keluar'],
            'field' => [
                'keterangan' => ['label' => 'Keterangan', 'tipe' => 'text'],
                'tujuan' => ['label' => 'Tujuan', 'tipe' => 'string'],
                'tgl_pengajuan' => ['label' => 'Tanggal Pengajuan', 'tipe' => 'datetime'],
                'tgl_keluar' => ['label' => 'Tanggal Keluar', 'tipe' => 'datetime'],
                'kode_transaksi' => ['label' => 'Kode Transaksi', 'tipe' => 'string'],
                'divisi' => ['label' => 'Divisi', 'tipe' => 'string'],
                'pengaju' => ['label' => 'Pengaju', 'tipe' => 'decimal'],
            ],
        ],

        'pengajuan' => [
            'label' => 'Pengajuan Bahan',
            'model' => Pengajuan::class,
            'kode' => 'kode_pengajuan',
            'jenis' => ['Pengajuan Bahan'],
            'field' => [
                'keterangan' => ['label' => 'Keterangan', 'tipe' => 'string'],
                'kode_pengajuan' => ['label' => 'Kode Pengajuan', 'tipe' => 'string'],
                'mulai_pengajuan' => ['label' => 'Mulai Pengajuan', 'tipe' => 'datetime'],
                'selesai_pengajuan' => ['label' => 'Selesai Pengajuan', 'tipe' => 'datetime'],
                'divisi' => ['label' => 'Divisi', 'tipe' => 'string'],
                'pengaju' => ['label' => 'Pengaju', 'tipe' => 'string'],
                'project' => ['label' => 'Proyek', 'tipe' => 'string'],
                'jenis_pengajuan' => ['label' => 'Jenis Pengajuan', 'tipe' => 'string'],
                'ongkir' => ['label' => 'Ongkir', 'tipe' => 'decimal'],
                'asuransi' => ['label' => 'Asuransi', 'tipe' => 'decimal'],
                'layanan' => ['label' => 'Layanan', 'tipe' => 'decimal'],
                'jasa_aplikasi' => ['label' => 'Jasa Aplikasi', 'tipe' => 'decimal'],
                'shipping_cost' => ['label' => 'Shipping Cost', 'tipe' => 'decimal'],
                'full_amount_fee' => ['label' => 'Full Amount Fee', 'tipe' => 'decimal'],
                'value_today_fee' => ['label' => 'Value Today Fee', 'tipe' => 'decimal'],
                'shipping_cost_usd' => ['label' => 'Shipping Cost Usd', 'tipe' => 'decimal'],
                'full_amount_fee_usd' => ['label' => 'Full Amount Fee Usd', 'tipe' => 'decimal'],
                'value_today_fee_usd' => ['label' => 'Value Today Fee Usd', 'tipe' => 'decimal'],
                'new_shipping_cost' => ['label' => 'New Shipping Cost', 'tipe' => 'decimal'],
                'new_full_amount_fee' => ['label' => 'New Full Amount Fee', 'tipe' => 'decimal'],
                'new_value_today_fee' => ['label' => 'New Value Today Fee', 'tipe' => 'decimal'],
                'new_shipping_cost_usd' => ['label' => 'New Shipping Cost Usd', 'tipe' => 'decimal'],
                'new_full_amount_fee_usd' => ['label' => 'New Full Amount Fee Usd', 'tipe' => 'decimal'],
                'new_value_today_fee_usd' => ['label' => 'New Value Today Fee Usd', 'tipe' => 'decimal'],
                'catatan' => ['label' => 'Catatan', 'tipe' => 'string'],
                'ppn' => ['label' => 'Ppn', 'tipe' => 'decimal'],
            ],
        ],

        'pengambilan_bahan' => [
            'label' => 'Pengambilan Bahan',
            'model' => PengambilanBahan::class,
            'kode' => 'kode_pengajuan',
            'jenis' => ['Pengambilan Bahan Non Proyek/Produksi'],
            'field' => [
                'keterangan' => ['label' => 'Keterangan', 'tipe' => 'string'],
                'kode_pengajuan' => ['label' => 'Kode Pengajuan', 'tipe' => 'string'],
                'mulai_pengajuan' => ['label' => 'Mulai Pengajuan', 'tipe' => 'datetime'],
                'selesai_pengajuan' => ['label' => 'Selesai Pengajuan', 'tipe' => 'datetime'],
                'divisi' => ['label' => 'Divisi', 'tipe' => 'string'],
                'pengaju' => ['label' => 'Pengaju', 'tipe' => 'string'],
                'project' => ['label' => 'Proyek', 'tipe' => 'string'],
            ],
        ],

        // ================= Bahan Rusak & Retur =================

        'bahan_rusaks' => [
            'label' => 'Bahan Rusak',
            'model' => BahanRusak::class,
            'kode' => 'kode_transaksi',
            'jenis' => ['Bahan Rusak'],
            // Tabel ini tidak punya kolom keterangan; yang ada hanya tanggal
            // dokumennya. Jumlahnya tinggal di bahan_rusak_details.
            'field' => [
                'tgl_pengajuan' => ['label' => 'Tanggal Pengajuan', 'tipe' => 'datetime'],
                'tgl_diterima' => ['label' => 'Tanggal Diterima', 'tipe' => 'datetime'],
                'kode_transaksi' => ['label' => 'Kode Transaksi', 'tipe' => 'string'],
            ],
        ],

        'bahan_rusak_details' => [
            'label' => 'Lot Bahan Rusak',
            'model' => BahanRusakDetails::class,
            'jenis' => ['Bahan Rusak'],
            'induk' => [
                'relasi' => 'bahanRusak',
                'kode' => 'kode_transaksi',
            ],
            'label_relasi' => [
                'relasi' => 'dataBahan',
                'kolom' => 'nama_bahan',
            ],
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'unit_price' => ['label' => 'Harga per Unit', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'sisa' => ['label' => 'Sisa', 'tipe' => 'decimal'],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
                'serial_number' => ['label' => 'Serial Number', 'tipe' => 'string'],
            ],
        ],

        'bahan_retur' => [
            'label' => 'Bahan Retur',
            'model' => BahanRetur::class,
            'kode' => 'kode_transaksi',
            'jenis' => ['Bahan Retur'],
            'field' => [
                'tujuan' => ['label' => 'Tujuan', 'tipe' => 'string'],
                'divisi' => ['label' => 'Divisi', 'tipe' => 'string'],
                'tgl_pengajuan' => ['label' => 'Tanggal Pengajuan', 'tipe' => 'datetime'],
                'tgl_diterima' => ['label' => 'Tanggal Diterima', 'tipe' => 'datetime'],
                'kode_transaksi' => ['label' => 'Kode Transaksi', 'tipe' => 'string'],
            ],
        ],

        // ================= Stock Opname =================

        'stock_opname' => [
            'label' => 'Stock Opname',
            'model' => StockOpname::class,
            // `nomor_referensi` dipakai sebagai kunci pencarian, jadi tidak
            // ikut jadi kolom yang bisa dikoreksi — memperbaikinya berarti
            // mengubah kunci yang dipakai mencarinya.
            'kode' => 'nomor_referensi',
            'jenis' => ['Stock Opname'],
            'field' => [
                'keterangan' => ['label' => 'Keterangan', 'tipe' => 'text'],
                'auditor' => ['label' => 'Auditor', 'tipe' => 'string'],
                'tgl_pengajuan' => ['label' => 'Tanggal Pengajuan', 'tipe' => 'datetime'],
                'tgl_audit' => ['label' => 'Tanggal Audit', 'tipe' => 'datetime'],
                'tgl_diterima' => ['label' => 'Tanggal Diterima', 'tipe' => 'datetime'],
                'nomor_referensi' => ['label' => 'Nomor Referensi', 'tipe' => 'string'],
                'pengaju' => ['label' => 'Pengaju', 'tipe' => 'decimal'],
            ],
        ],

        // ================= Produksi & hasilnya =================

        'produksis' => [
            'label' => 'Produksi Produk Setengah Jadi',
            'model' => Produksi::class,
            // Catatan: 27 dari 96 baris kode produksinya masih kosong, jadi
            // baris-baris itu belum bisa ditemukan lewat dropdown pencarian.
            'kode' => 'kode_produksi',
            'jenis' => ['Produksi Produk Setengah Jadi'],
            'field' => [
                'keterangan' => ['label' => 'Keterangan', 'tipe' => 'string'],
                'jenis_produksi' => ['label' => 'Jenis Produksi', 'tipe' => 'string'],
                'serial_number' => ['label' => 'Serial Number', 'tipe' => 'string'],
                'mulai_produksi' => ['label' => 'Mulai Produksi', 'tipe' => 'datetime'],
                'selesai_produksi' => ['label' => 'Selesai Produksi', 'tipe' => 'datetime'],
                'kode_produksi' => ['label' => 'Kode Produksi', 'tipe' => 'string'],
                'pengaju' => ['label' => 'Pengaju', 'tipe' => 'string'],
                'jml_produksi' => ['label' => 'Jumlah Produksi', 'tipe' => 'decimal'],
            ],
        ],

        'bahan_setengahjadis' => [
            'label' => 'Produk Setengah Jadi',
            'model' => BahanSetengahjadi::class,
            'kode' => 'kode_transaksi',
            'jenis' => ['Produk Setengah Jadi'],
            'field' => [
                'link_gambar' => ['label' => 'Tautan Gambar', 'tipe' => 'string'],
                'tgl_masuk' => ['label' => 'Tanggal Masuk', 'tipe' => 'datetime'],
                'kode_transaksi' => ['label' => 'Kode Transaksi', 'tipe' => 'string'],
            ],
        ],

        'bahan_setengahjadi_details' => [
            'label' => 'Lot Produk Setengah Jadi',
            'model' => BahanSetengahjadiDetails::class,
            'jenis' => ['Produk Setengah Jadi'],
            'induk' => [
                'relasi' => 'bahanSetengahjadi',
                'kode' => 'kode_transaksi',
            ],
            // Nama bahannya tersimpan di barisnya sendiri, jadi tidak perlu
            // relasi tambahan hanya untuk labelnya.
            'label_kolom' => 'nama_bahan',
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'unit_price' => ['label' => 'Harga per Unit (HPP)', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'sisa' => ['label' => 'Sisa', 'tipe' => 'decimal'],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
                'serial_number' => ['label' => 'Serial Number', 'tipe' => 'string'],
                'nama_bahan' => ['label' => 'Nama Bahan', 'tipe' => 'string'],
            ],
        ],

        'produksi_produk_jadi' => [
            'label' => 'Produksi Produk Jadi',
            'model' => ProduksiProdukJadi::class,
            'kode' => 'kode_produksi',
            'jenis' => ['Produksi Produk Jadi'],
            'field' => [
                'keterangan' => ['label' => 'Keterangan', 'tipe' => 'text'],
                'jenis_produksi' => ['label' => 'Jenis Produksi', 'tipe' => 'string'],
                'serial_number' => ['label' => 'Serial Number', 'tipe' => 'string'],
                'mulai_produksi' => ['label' => 'Mulai Produksi', 'tipe' => 'datetime'],
                'selesai_produksi' => ['label' => 'Selesai Produksi', 'tipe' => 'datetime'],
                'kode_produksi' => ['label' => 'Kode Produksi', 'tipe' => 'string'],
                'pengaju' => ['label' => 'Pengaju', 'tipe' => 'string'],
                'jml_produksi' => ['label' => 'Jumlah Produksi', 'tipe' => 'decimal'],
            ],
        ],

        'produk_jadi' => [
            'label' => 'Master Produk Jadi',
            'model' => ProdukJadi::class,
            // Master data, bukan transaksi: yang dicari namanya sendiri.
            'kode' => 'nama_produk',
            'jenis' => ['Produk Jadi'],
            'field' => [
                'sub_solusi' => ['label' => 'Sub Solusi', 'tipe' => 'string'],
                'gambar' => ['label' => 'Gambar', 'tipe' => 'string'],
                'nama_produk' => ['label' => 'Nama Produk', 'tipe' => 'string'],
                'kode_bahan' => ['label' => 'Kode Bahan', 'tipe' => 'string'],
            ],
        ],

        'produk_jadi_details' => [
            'label' => 'Lot Produk Jadi',
            'model' => ProdukJadiDetails::class,
            'jenis' => ['Produk Jadi'],
            'induk' => [
                'relasi' => 'ProdukJadis',
                'kode' => 'kode_transaksi',
            ],
            'label_kolom' => 'nama_produk',
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'unit_price' => ['label' => 'Harga per Unit (HPP)', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'sisa' => ['label' => 'Sisa', 'tipe' => 'decimal'],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
                'serial_number' => ['label' => 'Serial Number', 'tipe' => 'string'],
                'nama_produk' => ['label' => 'Nama Produk', 'tipe' => 'string'],
            ],
        ],

        // ================= Proyek =================

        'projek' => [
            'label' => 'Proyek',
            'model' => Projek::class,
            'kode' => 'kode_projek',
            'jenis' => ['Proyek'],
            // Catatan lama di berkas ini menyebut `projek.keterangan` tidak
            // pernah dibuat. Itu keliru — kolomnya ada, varchar(255).
            'field' => [
                'keterangan' => ['label' => 'Keterangan', 'tipe' => 'string'],
                'nama_projek' => ['label' => 'Nama Proyek', 'tipe' => 'string'],
                'mulai_projek' => ['label' => 'Mulai Proyek', 'tipe' => 'datetime'],
                'selesai_projek' => ['label' => 'Selesai Proyek', 'tipe' => 'datetime'],
                'kode_projek' => ['label' => 'Kode Proyek', 'tipe' => 'string'],
                'pengaju' => ['label' => 'Pengaju', 'tipe' => 'string'],
            ],
        ],


        /**
         * Biaya tambahan proyek: `qty` di sini bukan jumlah barang.
         *
         * Perlu disebut karena mudah salah baca. Yang dicatat baris ini biaya
         * operasional proyek — BBM, sewa, ongkos — dengan `qty` berarti
         * "1 kegiatan" atau "15 liter", bukan barang yang keluar dari gudang.
         * Tidak ada lot yang berkurang dan tidak ada lot yang lahir.
         *
         * `total_biaya` hasil qty x unit_price, dan ikut menjumlah biaya proyek
         * lewat sum('total_biaya') di LaporanProyekExport dan
         * LaporanGaransiProyekExport. Jadi kalau qty atau harganya dikoreksi di
         * database, `total_biaya` harus ikut disesuaikan di sana — aplikasi
         * tidak menghitungnya ulang.
         */
        'laporan_proyek' => [
            'label' => 'Biaya Tambahan Proyek',
            'model' => LaporanProyek::class,
            'jenis' => ['Proyek'],
            'induk' => [
                'relasi' => 'dataProyek',
                'kode' => 'kode_projek',
            ],
            'label_kolom' => 'nama_biaya_tambahan',
            'field' => [
                'nama_biaya_tambahan' => ['label' => 'Nama Biaya', 'tipe' => 'string'],
                'keterangan' => ['label' => 'Keterangan', 'tipe' => 'string'],
                'satuan' => ['label' => 'Satuan', 'tipe' => 'string'],
                'tanggal' => ['label' => 'Tanggal', 'tipe' => 'datetime'],
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'unit_price' => ['label' => 'Harga per Unit', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'total_biaya' => ['label' => 'Total Biaya', 'tipe' => 'decimal'],
            ],
        ],

        'garansi_projek' => [
            'label' => 'Garansi Proyek',
            'model' => GaransiProjek::class,
            'kode' => 'kode_garansi',
            'jenis' => ['Garansi Proyek'],
            'field' => [
                'keterangan' => ['label' => 'Keterangan', 'tipe' => 'string'],
                'mulai_garansi' => ['label' => 'Mulai Garansi', 'tipe' => 'datetime'],
                'selesai_garansi' => ['label' => 'Selesai Garansi', 'tipe' => 'datetime'],
                'anggaran' => ['label' => 'Anggaran', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'kode_garansi' => ['label' => 'Kode Garansi', 'tipe' => 'string'],
                'pengaju' => ['label' => 'Pengaju', 'tipe' => 'string'],
            ],
        ],

        'laporan_garansi_proyek' => [
            'label' => 'Biaya Tambahan Garansi Proyek',
            'model' => LaporanGaransiProyek::class,
            'jenis' => ['Garansi Proyek'],
            'induk' => [
                'relasi' => 'dataProyek',
                'kode' => 'kode_garansi',
            ],
            'label_kolom' => 'nama_biaya_tambahan',
            'field' => [
                'nama_biaya_tambahan' => ['label' => 'Nama Biaya', 'tipe' => 'string'],
                'keterangan' => ['label' => 'Keterangan', 'tipe' => 'string'],
                'satuan' => ['label' => 'Satuan', 'tipe' => 'string'],
                'tanggal' => ['label' => 'Tanggal', 'tipe' => 'datetime'],
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'unit_price' => ['label' => 'Harga per Unit', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'total_biaya' => ['label' => 'Total Biaya', 'tipe' => 'decimal'],
            ],
        ],

        'projek_rnd' => [
            'label' => 'Proyek RnD',
            'model' => ProjekRnd::class,
            'kode' => 'kode_projek_rnd',
            'jenis' => ['Proyek RnD'],
            'field' => [
                'keterangan' => ['label' => 'Keterangan', 'tipe' => 'string'],
                'keterangan_status' => ['label' => 'Keterangan Status', 'tipe' => 'text'],
                'nama_projek_rnd' => ['label' => 'Nama Proyek RnD', 'tipe' => 'string'],
                'serial_number' => ['label' => 'Serial Number', 'tipe' => 'string'],
                'mulai_projek_rnd' => ['label' => 'Mulai Proyek RnD', 'tipe' => 'datetime'],
                'selesai_projek_rnd' => ['label' => 'Selesai Proyek RnD', 'tipe' => 'datetime'],
                'kode_projek_rnd' => ['label' => 'Kode Proyek RnD', 'tipe' => 'string'],
                'pengaju' => ['label' => 'Pengaju', 'tipe' => 'string'],
                'is_riset_lapangan' => ['label' => 'Riset Lapangan', 'tipe' => 'decimal'],
                'file_laporan' => ['label' => 'File Laporan', 'tipe' => 'string'],
                'file_proposal_riset' => ['label' => 'File Proposal Riset', 'tipe' => 'string'],
                'file_surat_tugas_riset' => ['label' => 'File Surat Tugas Riset', 'tipe' => 'string'],
            ],
        ],

        // ================= Produk Sample =================

        'produk_sample' => [
            'label' => 'Produk Sample',
            'model' => ProdukSample::class,
            'kode' => 'kode_produk_sample',
            'jenis' => ['Produk Sample'],
            'field' => [
                'keterangan' => ['label' => 'Keterangan', 'tipe' => 'string'],
                'kode_produk_sample' => ['label' => 'Kode Produk Sample', 'tipe' => 'string'],
                'mulai_produk_sample' => ['label' => 'Mulai Produk Sample', 'tipe' => 'datetime'],
                'selesai_produk_sample' => ['label' => 'Selesai Produk Sample', 'tipe' => 'datetime'],
                'pengaju' => ['label' => 'Pengaju', 'tipe' => 'string'],
                'nama_produk_sample' => ['label' => 'Nama Produk Sample', 'tipe' => 'string'],
                'kategori_pengajuan' => ['label' => 'Kategori Pengajuan', 'tipe' => 'string'],
            ],
        ],

        // ================= Quality Control =================

        'qc_bahan_masuk' => [
            'label' => 'QC Bahan Masuk',
            'model' => QcBahanMasuk::class,
            'kode' => 'kode_qc',
            'jenis' => ['QC Bahan Masuk'],
            'field' => [
                'keterangan_qc' => ['label' => 'Keterangan QC', 'tipe' => 'text'],
                'tanggal_qc' => ['label' => 'Tanggal QC', 'tipe' => 'datetime'],
                'kode_qc' => ['label' => 'Kode QC', 'tipe' => 'string'],
                'tanggal_masuk_gudang' => ['label' => 'Tanggal Masuk Gudang', 'tipe' => 'datetime'],
            ],
        ],

        'qc_produk_setengah_jadi_list' => [
            'label' => 'QC Produk Setengah Jadi',
            'model' => QcProdukSetengahJadiList::class,
            'kode' => 'kode_list',
            'jenis' => ['QC Produk Setengah Jadi'],
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'unit_price' => ['label' => 'Harga per Unit (HPP)', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'serial_number' => ['label' => 'Serial Number', 'tipe' => 'string'],
                'jenis_sn' => ['label' => 'Jenis SN', 'tipe' => 'string'],
                'id_bluetooth' => ['label' => 'ID Bluetooth', 'tipe' => 'string'],
                'kode_jenis_unit' => ['label' => 'Kode Jenis Unit', 'tipe' => 'string'],
                'kode_wiring_unit' => ['label' => 'Kode Wiring Unit', 'tipe' => 'string'],
                'petugas_produksi' => ['label' => 'Petugas Produksi', 'tipe' => 'string'],
                'kode_list' => ['label' => 'Kode List', 'tipe' => 'string'],
                'kode_produksi' => ['label' => 'Kode Produksi', 'tipe' => 'string'],
                'mulai_produksi' => ['label' => 'Mulai Produksi', 'tipe' => 'datetime'],
                'selesai_produksi' => ['label' => 'Selesai Produksi', 'tipe' => 'datetime'],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
                'tanggal_masuk_gudang' => ['label' => 'Tanggal Masuk Gudang', 'tipe' => 'datetime'],
            ],
        ],

        'qc_produk_jadi_list' => [
            'label' => 'QC Produk Jadi',
            'model' => QcProdukJadiList::class,
            'kode' => 'kode_list',
            'jenis' => ['QC Produk Jadi'],
            'field' => [
                'qty' => ['label' => 'Jumlah', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'unit_price' => ['label' => 'Harga per Unit (HPP)', 'tipe' => 'decimal', 'wajib_lampiran' => true],
                'serial_number' => ['label' => 'Serial Number', 'tipe' => 'string'],
                'id_logger' => ['label' => 'ID Logger', 'tipe' => 'string'],
                'petugas_produksi' => ['label' => 'Petugas Produksi', 'tipe' => 'string'],
                'kode_list' => ['label' => 'Kode List', 'tipe' => 'string'],
                'mulai_produksi' => ['label' => 'Mulai Produksi', 'tipe' => 'datetime'],
                'selesai_produksi' => ['label' => 'Selesai Produksi', 'tipe' => 'datetime'],
                'sub_total' => ['label' => 'Sub Total', 'tipe' => 'decimal'],
                'tanggal_masuk_gudang' => ['label' => 'Tanggal Masuk Gudang', 'tipe' => 'datetime'],
            ],
        ],

    ],

    /**
     * Pilihan "Jenis Pengajuan" pada form pengajuan.
     *
     * Daftar ini dan daftar modul di atas harus dibaca bersama: kunci `jenis` di
     * tiap modul menunjuk ke label di daftar ini, dan itulah yang menyaring
     * pilihan kolom begitu jenisnya dicentang. Selama keduanya tinggal di dua
     * berkas berbeda, satu label yang diubah di satu sisi akan memutus
     * penyaringannya tanpa error apa pun — pilihan kolomnya cuma jadi kosong.
     * Dijaga oleh test di tests/Unit/PerbaikanDataServiceTest.php.
     *
     * Setiap jenis di sini sekarang punya minimal satu modul.
     */
    'jenis_pengajuan' => [
        'Transaksi - Bahan Masuk',
        'Transaksi - Bahan Keluar',
        'Transaksi - Pembelian Bahan',
        'Pengajuan Bahan',
        'Bahan Rusak',
        'Bahan Retur',
        'Stock Opname',
        'Pengambilan Bahan Non Proyek/Produksi',
        'Produksi Produk Setengah Jadi',
        'Produk Setengah Jadi',
        'Produksi Produk Jadi',
        'Produk Jadi',
        'Proyek',
        'Produk Sample',
        'Garansi Proyek',
        'Proyek RnD',
        'QC Bahan Masuk',
        'QC Produk Setengah Jadi',
        'QC Produk Jadi',
    ],

];
