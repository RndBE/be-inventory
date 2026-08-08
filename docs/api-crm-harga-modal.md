# API CRM — Harga Modal Produk

Endpoint ini dipakai CRM untuk menampilkan halaman **Harga Modal** dengan tiga tab:
Produk Jadi, Produk Setengah Jadi, dan Bahan.

Inventory tetap jadi satu-satunya pemilik data harga modal — CRM memanggil endpoint
ini setiap kali halaman dibuka dan **tidak menyalin datanya ke database sendiri**.
Harga modal berubah tiap ada produksi baru; salinan yang basi berarti marketing
menghitung margin dari angka yang sudah tidak berlaku.

## Endpoint

```
GET {INVENTORY_URL}/api/crm/harga-modal?email={email_pengguna}
```

### Header wajib

```
X-API-KEY: <nilai CRM_API_KEY>
Accept: application/json
```

`CRM_API_KEY` ada di `.env` inventory dan **berbeda** dari `INVENTORY_API_KEY` milik
HRIS. Kirimkan ke tim CRM lewat jalur yang aman — jangan ditulis di repo, tiket,
atau chat grup.

### Parameter

| Nama | Wajib | Keterangan |
|---|---|---|
| `email` | ya | Email pengguna yang sedang membuka halaman di CRM. Pencocokan tidak membedakan huruf besar/kecil. |
| `hanya_tersedia` | tidak | Isi `1` untuk membatasi ke yang stoknya masih ada. Default menampilkan semua. |
| `tab` | tidak | `produk-jadi`, `setengah-jadi`, `bahan`, atau gabungan dipisah koma. Default ketiganya. Nilai yang tidak dikenal → 422. |

**Pakai `tab`.** Tab bahan sendirian ~1.800 baris; tanpa parameter ini CRM menarik
ketiga tab tiap kali halaman dibuka walau yang dilihat cuma satu. Ambil per tab saat
tabnya diklik.

> Catatan soal `hanya_tersedia`: **stok produk jadi saat ini nol untuk seluruh 50
> unit.** Kalau CRM memanggil dengan `hanya_tersedia=1`, tab Produk Jadi akan kosong
> — bukan error, memang tidak ada stoknya. Untuk keperluan referensi harga, biarkan
> default (tanpa filter).

Kirim email pengguna yang **sedang login**, bukan satu email tetap. Email inilah yang
menentukan boleh-tidaknya harga modal ditampilkan.

## Endpoint kedua — rincian bahan per kode produksi

Untuk tombol "lihat bahan" pada tab Produk Jadi dan Produk Setengah Jadi.

```
GET {INVENTORY_URL}/api/crm/harga-modal/rincian?email={email}&tipe={tipe}&produksi_id={id}
```

| Nama | Wajib | Keterangan |
|---|---|---|
| `email` | ya | Sama seperti endpoint utama |
| `tipe` | ya | `produk-jadi` atau `setengah-jadi` |
| `produksi_id` | ya | Ambil dari field `produksi_id` pada baris tab — **jangan** dari `kode_produksi` |

Header dan otorisasinya sama dengan endpoint utama.

**Pakai `produksi_id`, bukan `kode_produksi`.** Ada 123 dari 321 unit setengah jadi yang
`kode_produksi`-nya null, jadi pencarian berbasis kode akan gagal untuk sepertiga data.
Baris yang `produksi_id`-nya null berarti rinciannya memang tidak tersedia — tombolnya
sebaiknya dinonaktifkan, bukan menampilkan error.

Contoh balikan:

```json
{
  "success": true,
  "diambil_pada": "2026-08-07T11:02:10+07:00",
  "tipe": "produk-jadi",
  "produksi_id": 34,
  "kode_produksi": "BE - CST - 110-00024",
  "keterangan": "Project Jasa Tirta Lampung",
  "mulai_produksi": "2026-06-23 12:00:00",
  "status": "Selesai",
  "jml_produksi": 7,
  "jumlah_item": 74,
  "total_biaya_bahan": 87711202.72,
  "harga_modal_satuan": 12530171.82,
  "data": [
    {
      "jenis": "Bahan",
      "nama": "ARRESTER Schneider DC 2P 1000V (A9L40281)",
      "kode": "ARR-003",
      "serial_number": null,
      "gambar_url": "https://inventory.beacontelemetry.com/storage/bahan/1773371696_QILAH%20CANS%20EDIT%20%2819%29.png",
      "qty": 7,
      "harga_satuan": 1288403,
      "sub_total": 9018821,
      "batch": [{ "qty": 7, "unit_price": 1288403 }]
    }
  ]
}
```

Catatan isi:

- **`jenis`** membedakan `Bahan` (mentah) dari `Produk Setengah Jadi`. Produk jadi bisa
  memakai keduanya — pada produksi contoh di atas, 64 bahan dan 10 produk setengah jadi.
  Komponen setengah jadi tidak punya `kode` dan `gambar_url`, tapi punya `serial_number`.
- **`harga_satuan`** adalah rata-rata (`sub_total ÷ qty`), karena satu bahan bisa diambil
  dari beberapa batch pembelian berharga berbeda.
- **`batch`** memerinci tiap batch itu. Selisihnya kadang besar — ada bahan yang batch-nya
  Rp 3.369.589 dan Rp 4.354.629 sekaligus dalam satu produksi.

### Angkanya rekonsiliasi dengan tab

`total_biaya_bahan ÷ jml_produksi` = `harga_modal_satuan` di tab, karena keduanya membaca
daftar yang sama yang dipakai QC. Marketing bisa menelusuri harga modal sampai ke bahan
tanpa menemukan selisih.

Satu pengecualian kecil: untuk **setengah jadi** bisa ada selisih **di bawah Rp 1**
(mis. tab Rp 389.300 vs rincian Rp 389.299,68). Penyebabnya kolom
`bahan_setengahjadi_details.unit_price` bertipe integer sehingga sen-nya terpotong saat
disimpan, sedangkan `produk_jadi_details.unit_price` bertipe desimal. Tidak berpengaruh
untuk keperluan harga jual, tapi jangan dijadikan patokan kalau nanti ada pencocokan
akuntansi yang menuntut sama persis.

## Gambar

Ada dua sumber gambar yang berbeda, keduanya keluar sebagai `gambar_url` siap pakai
di `<img src>`.

### Foto produk — tab Produk Jadi & Produk Setengah Jadi

Diisi petugas di inventory lewat tombol gambar pada tabel Produk Jadi / Produk Setengah
Jadi, berupa **tautan** (umumnya Google Drive), bukan berkas yang diunggah ke server.
Foto ini menggambarkan unit yang masuk gudang, bukan foto katalog produknya.

| Field | Isi |
|---|---|
| `gambar_url` | Tautan Drive sudah diubah jadi URL thumbnail siap `<img>`. Tautan non-Drive diteruskan apa adanya. |
| `link_gambar` | Tautan asli seperti yang diisi petugas, untuk tombol "buka di tab baru" |

Keduanya `null` kalau unit itu belum ada fotonya — tampilkan placeholder, jangan
`<img>` kosong.

#### Tampilan yang diharapkan

Gambar diletakkan **sebelum nama produk, di dalam sel yang sama**, bukan sebagai kolom
tersendiri. Tujuannya supaya orang membaca "gambar ini adalah produk ini" dalam satu
tarikan, dan kolomnya tidak melebar percuma di layar sempit.

```
┌────┬──────────────────────────────────────┬───────────────┬──────────────┐
│ NO │ PRODUK                               │ KODE PRODUKSI │ HARGA MODAL  │
├────┼──────────────────────────────────────┼───────────────┼──────────────┤
│  1 │ ┌────┐  BE - CST - 110               │ BE-CST-110-   │ 12.530.171,82│
│    │ │ 📷 │  260715070010385008           │ 00024         │              │
│    │ └────┘  ↑serial, teks kecil abu-abu  │               │              │
├────┼──────────────────────────────────────┼───────────────┼──────────────┤
│  2 │ ┌────┐  BE - CST - 110               │ BE-CST-110-   │ 12.530.171,82│
│    │ │ ⬜ │  260715070010386009           │ 00024         │              │
│    │ └────┘  ↑placeholder, belum ada foto │               │              │
└────┴──────────────────────────────────────┴───────────────┴──────────────┘
```

Aturan yang membuat tabelnya tetap rapi:

1. **Kotak gambar berukuran tetap** (mis. 48×48 atau 64×64) dengan `object-fit: cover`,
   dipakai untuk semua keadaan — ada gambar, belum ada, maupun gagal dimuat. Tanpa ini
   tinggi baris akan loncat-loncat mengikuti ada-tidaknya foto.
2. **Placeholder, bukan `<img>` kosong**, saat `gambar_url` bernilai null.
3. **`onerror` mengganti gambar dengan placeholder yang sama**, supaya berkas yang belum
   dibagikan tidak meninggalkan ikon rusak.

### Foto bahan — tab Bahan dan rincian bahan

Berasal dari berkas yang diunggah di master Bahan inventory, jadi mekanismenya beda:

| Field | Isi |
|---|---|
| `gambar_url` | URL absolut siap dipakai di `<img src>`. Spasi pada nama file sudah di-encode. |
| `gambar_path` | Path relatif, mis. `bahan/kabel utp.png` |

Keduanya `null` untuk bahan tanpa gambar — 1.949 dari 2.077 bahan punya gambar.

Dua hal yang perlu diketahui:

1. **`gambar_url` dibentuk dari `APP_URL` inventory.** Kalau `APP_URL` di server tidak
   menunjuk ke URL publik, URL-nya akan salah dan gambar tidak muncul. `gambar_path` ada
   sebagai jalan keluar — CRM bisa menyusun sendiri URL-nya.
2. **Gambar disajikan tanpa API key.** Berkasnya diambil langsung dari
   `/storage/...` oleh browser, jadi siapa pun yang punya URL-nya bisa melihat. Untuk foto
   bahan ini wajar, tapi jangan berasumsi gambar ikut terlindungi seperti data harganya.

## Otorisasi — dua lapis, keduanya wajib

1. **`X-API-KEY`** membuktikan pemanggilnya memang CRM
2. **Permission `lihat-harga-modal`** pada user pemilik email tersebut

Lapis kedua bukan formalitas. Kalau email hanya dipakai untuk memilih data, siapa pun
yang memegang API key bisa mengganti email di query string dan tetap menerima seluruh
harga modal. Permission-nya diatur di inventory (Role & Permission), sehingga
pencabutan akses satu orang tidak perlu perubahan apa pun di CRM.

## Kode status

| Kode | Arti | Yang sebaiknya ditampilkan CRM |
|---|---|---|
| 200 | Berhasil | Data |
| 401 | API key salah/tidak dikirim | Pesan teknis, bukan untuk pengguna akhir |
| 403 | User terdaftar tapi tidak punya `lihat-harga-modal` | "Anda tidak punya akses harga modal" |
| 404 | Email tidak terdaftar di inventory | "Email Anda belum terdaftar di inventory" |
| 422 | Parameter `email` kosong | Pesan teknis |
| 503 | `CRM_API_KEY` belum diisi di inventory | Pesan teknis |

## Contoh balikan

```json
{
  "success": true,
  "diambil_pada": "2026-08-07T10:12:44+07:00",
  "hanya_tersedia": false,
  "pengguna": { "nama": "DEWI SETIAWATI", "email": "dewi.priyambodo@yahoo.com" },
  "produk_jadi": {
    "jumlah_unit": 50,
    "ringkasan": [
      {
        "nama_produk": "BE - CST - 110",
        "jumlah_unit": 7,
        "stok_tersedia": 0,
        "harga_modal_terakhir": 12530171.82,
        "harga_modal_terendah": 12530171.82,
        "harga_modal_tertinggi": 12530171.82
      }
    ],
    "data": [
      {
        "nama_produk": "BE - CST - 110",
        "kode_produk": "BE - CST - 110",
        "sub_solusi": "Automatic Water Level Recorder",
        "kode_produksi": "BE - CST - 110-00024",
        "produksi_id": 34,
        "kode_unit": "BE - CST - 110-00024-1/7",
        "serial_number": "260715070010385008",
        "tgl_masuk": "2026-07-15 16:28:19",
        "qty": 1,
        "stok_sisa": 0,
        "harga_modal_satuan": 12530171.82,
        "sumber": "Produksi"
      }
    ]
  },
  "produk_setengah_jadi": {
    "jumlah_unit": 321,
    "ringkasan": [ "...bentuknya sama..." ],
    "data": [ "...bentuknya sama, `nama_produk` diisi nama bahan setengah jadi..." ]
  },
  "bahan": {
    "jumlah_bahan": 1795,
    "data": [
      {
        "nama_produk": "Baterai CR 2032",
        "kode_produk": "BTR-2032",
        "jenis_bahan": "Elektronik",
        "unit": "Pcs",
        "gambar_url": "https://inventory.beacontelemetry.com/storage/bahan/baterai%20cr2032.png",
        "gambar_path": "bahan/baterai cr2032.png",
        "stok_sisa": 40,
        "harga_modal_satuan": 4000,
        "harga_modal_rata2": 5500,
        "nilai_persediaan": 220000,
        "tgl_masuk": "2026-05-20 09:00:00",
        "sumber": "Pembelian"
      }
    ]
  }
}
```

Tab Produk Jadi dan Produk Setengah Jadi bentuknya identik, jadi komponen tabelnya
cukup dibuat sekali. Tab Bahan memakai nama kunci inti yang sama
(`nama_produk`, `kode_produk`, `stok_sisa`, `harga_modal_satuan`, `tgl_masuk`,
`sumber`) plus tiga kolom khusus, dan **tidak punya `ringkasan`** — barisnya sudah
satu per bahan, jadi ringkasan per nama hanya akan menyalin ulang isinya.

## Dari mana angkanya berasal

`harga_modal_satuan` adalah `unit_price` yang dibekukan saat unit lolos QC dan masuk
gudang — **nilai yang sama persis dengan yang tampil di halaman Produk Jadi dan Produk
Setengah Jadi di inventory**. Tidak ada perhitungan ulang, supaya tidak pernah muncul
dua angka berbeda untuk unit yang sama.

### Kenapa bukan "bahan yang pertama kali keluar"

Satu kode produksi bisa punya banyak pengeluaran bahan susulan. Contoh nyata pada
produksi `BE - CST - 110-00024` (7 unit):

| Basis | Total | Per unit |
|---|---|---|
| Pengeluaran bahan pertama saja | Rp 40.917.623,51 | Rp 5.845.374,79 |
| Seluruh bahan keluar disetujui (23 pengeluaran) | Rp 88.520.550,34 | Rp 12.645.792,91 |
| **Yang dipakai endpoint ini (nilai QC)** | Rp 87.711.202,72 | **Rp 12.530.171,82** |

Memakai pengeluaran pertama akan menampilkan harga modal **kurang dari separuh** biaya
sebenarnya. Untuk dasar penetapan harga jual, itu berbahaya.

### Tab Bahan sumbernya lain

Bahan **tidak punya HPP yang dibekukan** seperti produk hasil QC — yang ada hanya harga
beli per batch pembelian. Jadi angkanya dibentuk dari `purchase_details`, mengikuti
aturan yang sudah dipakai halaman Kalkulasi Restock: stok = `SUM(sisa)`, harga = harga
beli terakhir. Aturannya disamakan supaya dua halaman tidak memberi angka berbeda untuk
bahan yang sama.

Karena "harga modal bahan" tidak punya satu jawaban yang benar untuk semua keperluan,
dilaporkan tiga angka sekaligus:

| Field | Arti | Kapan dipakai |
|---|---|---|
| `harga_modal_satuan` | Harga beli **terakhir** | Memperkirakan biaya restock |
| `harga_modal_rata2` | Rata-rata **tertimbang** dari stok yang benar-benar ada | Menghitung modal stok yang dipegang sekarang |
| `nilai_persediaan` | `SUM(sisa × harga batch)` | Nilai uang yang menganggur di gudang |

Ketiganya perlu ditampilkan, bukan dipilih satu: pada data sekarang **121 dari 907**
bahan berstok punya selisih di atas 20% antara harga terakhir dan rata-rata tertimbang.
Contoh: Baterai CR 2032 harga terakhirnya Rp 4.000 tapi rata-rata stok yang dipegang
Rp 5.500. Menampilkan satu angka saja akan menyesatkan ke salah satu arah.

Bahan yang **belum pernah dibeli tidak muncul** — harga modalnya memang tidak ada, dan
menampilkannya sebagai Rp 0 lebih buruk daripada tidak menampilkannya. Dari 2.077 bahan,
1.795 pernah dibeli dan 907 di antaranya masih berstok.

### Produk dari produk sample

Field `sumber` bernilai `Produk Sample` atau `Produksi`, ditentukan dari kolom
`produk_sample_id` — **bukan** dari membaca teks keterangan dan bukan dari awalan kode
`PRS`. Dua cara terakhir tidak bisa diandalkan: keterangan bisa diedit pengguna, dan
kode produksi hasil sample memakai `kode_bahan` sebagai awalan bila tersedia, sehingga
tidak selalu berawalan `PRS`.

### `kode_produksi` bisa null, `kode_unit` tidak

`kode_produksi` dicari dari tabel stok lebih dulu, lalu dari daftar QC. Untuk data
lama yang dibuat sebelum alur QC berjalan, keduanya kosong — saat ini 123 dari 321
unit setengah jadi. Semua produk jadi (50/50) sudah terisi.

`kode_unit` selalu terisi dan sudah memuat kode produksinya (mis.
`PRD-20260413135218-0002-16`, `PRS - 00017-SJ`). **Tampilkan `kode_unit` sebagai
cadangan** kalau `kode_produksi` null, jangan tampilkan sel kosong.

## Catatan

- Belum ada penomoran halaman. Saat ini datanya masih ratusan baris, tapi kalau nanti
  sudah ribuan, endpoint ini perlu ditambahi paginasi.
- Panggil dari **server** CRM, bukan dari JavaScript di browser. Kalau dipanggil dari
  sisi klien, `CRM_API_KEY` terbaca siapa pun yang membuka DevTools — dan itu berarti
  seluruh harga modal terbuka.
