# API HRIS — Aset yang Sedang Dipegang Karyawan

Endpoint ini dipakai HRIS untuk menampilkan daftar aset yang sedang dibawa seorang
karyawan di halaman detail karyawan. Inventory tetap jadi satu-satunya pemilik data
aset — HRIS memanggil endpoint ini setiap kali halaman dibuka dan tidak menyalin
datanya, supaya tidak ada data yang basi.

## Endpoint

```
GET {INVENTORY_URL}/api/hris/aset-karyawan?email={email_karyawan}
```

### Header wajib

```
X-API-KEY: <nilai INVENTORY_API_KEY>
Accept: application/json
```

Nilai `INVENTORY_API_KEY` ada di `.env` inventory. Kirimkan ke tim HRIS lewat jalur
yang aman — jangan ditulis di repo, tiket, atau chat grup.

### Parameter

| Nama | Wajib | Keterangan |
|---|---|---|
| `email` | ya | Email karyawan. Pencocokan tidak membedakan huruf besar/kecil. |

## Yang dihitung sebagai "sedang dipegang"

Ada **dua** bentuk kepemilikan yang dilaporkan, dibedakan lewat field `sumber` pada
setiap baris `data`.

### `sumber: "Peminjaman"` — pinjaman sementara

Aset yang **benar-benar sudah keluar dan belum kembali**, yaitu detail peminjaman
yang memenuhi semua syarat berikut:

1. `status_pengembalian` = `Belum dikembalikan`
2. Pengajuan induknya sudah disetujui General Affair (`status` = `Disetujui`)
3. **dan** sudah diketahui HRD (`status_hrd` = `Disetujui`)

Syarat ke-3 penting: pengajuan yang sudah lolos GA tapi masih menunggu HRD berarti
asetnya belum boleh keluar, jadi tidak dihitung sebagai sedang dipegang.

### `sumber: "PIC"` — penugasan tetap

Aset yang `pic_id`-nya karyawan tersebut, ditetapkan lewat rekap aset (form atau
import Excel) tanpa melalui peminjaman. Sifatnya penugasan tetap, bukan pinjaman
berjangka, jadi tidak punya kode maupun tanggal pinjam — field `peminjaman`-nya
bernilai `null`.

### Tidak ada perhitungan ganda

Peminjaman yang disetujui ikut menetapkan `pic_id` ke peminjam, jadi satu aset bisa
memenuhi kedua definisi sekaligus. Dalam kasus itu **baris peminjaman yang dipakai**
dan baris PIC-nya dibuang, karena keterangannya lebih lengkap. Setiap `rekap_aset_id`
dijamin muncul paling banyak sekali.

> **Perubahan perilaku.** Sebelum ini endpoint hanya melaporkan peminjaman aktif dan
> sengaja mengabaikan `pic_id`, sehingga aset yang ditugaskan tetap tidak pernah tampil
> di HRIS padahal fisiknya dibawa karyawan. Baris ber-`sumber: "PIC"` adalah tambahan
> baru. Kalau HRIS ingin menampilkan pinjaman saja seperti sebelumnya, saring
> `sumber === 'Peminjaman'` di sisi HRIS.

## Contoh response

### 200 — berhasil

```json
{
  "success": true,
  "karyawan": {
    "id": 58,
    "name": "WAYAN",
    "email": "wayantrk@gmail.com",
    "job_position": "Software",
    "organization": "Software"
  },
  "total": 2,
  "total_peminjaman": 1,
  "total_pic": 1,
  "data": [
    {
      "rekap_aset_id": 12,
      "sumber": "Peminjaman",
      "nomor_aset": "INV/ATC/CHAIR-001/DIR/2025",
      "nama_barang": "Informa King B13 Director Chair Coffe",
      "serial_number": null,
      "kondisi": "Baik",
      "ruangan": "Ruang Meeting",
      "jumlah": 1,
      "peminjaman": {
        "kode": "PJA-20260725-0003",
        "tgl_pinjam": "2026-07-25",
        "keperluan": "Demo klien",
        "divisi": "Software",
        "lama_dipinjam_hari": 7
      }
    },
    {
      "rekap_aset_id": 31,
      "sumber": "PIC",
      "nomor_aset": "INV/ATC/LAPTOP-014/SFW/2025",
      "nama_barang": "Lenovo ThinkPad E14",
      "serial_number": "PF2ABC12",
      "kondisi": "Baik",
      "ruangan": "Ruang Software",
      "jumlah": 1,
      "peminjaman": null
    }
  ]
}
```

Baris ber-`sumber: "Peminjaman"` selalu didahulukan, karena sifatnya sementara dan
lebih perlu ditindaklanjuti daripada penugasan tetap.

Karyawan yang tidak sedang memegang aset tetap membalas `200` dengan `total: 0` dan
`data: []` — bukan `404`. HRIS cukup menampilkan "Tidak ada aset yang sedang dipegang".

### 422 — parameter kurang

```json
{ "success": false, "message": "Parameter email wajib diisi." }
```

### 404 — karyawan tidak ditemukan

```json
{ "success": false, "message": "Karyawan dengan email tersebut tidak terdaftar di inventory." }
```

Ini berarti emailnya tidak terdaftar di tabel `users` inventory — bukan berarti
karyawannya tidak punya aset. Sebaiknya HRIS membedakan keduanya di tampilan, misalnya
"Karyawan belum terhubung ke sistem inventory".

### 401 — token salah

```json
{ "message": "Unauthenticated." }
```

## Contoh pemanggilan

```bash
curl -H "X-API-KEY: $INVENTORY_API_KEY" \
     -H "Accept: application/json" \
     "https://inventory.beacontelemetry.com/api/hris/aset-karyawan?email=budi@bejogja.com"
```

```php
// Sisi HRIS (Laravel)
$response = Http::withHeaders(['X-API-KEY' => config('services.inventory.key')])
    ->acceptJson()
    ->timeout(5)
    ->get(config('services.inventory.url') . '/api/hris/aset-karyawan', [
        'email' => $karyawan->email,
    ]);

$aset = $response->successful() ? $response->json('data', []) : [];
```

Beri `timeout` dan tangani kegagalan dengan anggun: kalau inventory sedang tidak bisa
dihubungi, halaman detail karyawan sebaiknya tetap terbuka dengan keterangan bahwa data
aset gagal dimuat, bukan ikut gagal.

## Prasyarat data

Pencocokan memakai email, jadi email di HRIS dan di inventory harus sama persis
(besar/kecil huruf tidak masalah). Karyawan yang emailnya berbeda atau kosong di salah
satu sistem akan selalu balas `404`. Kalau ini sering terjadi, pertimbangkan menambah
kolom `nik` di tabel `users` inventory sebagai kunci pencocokan yang lebih tahan
perubahan.

## Berkas terkait

- Route: `routes/api.php` (grup `hris`, middleware `inventory_api_token`)
- Controller: `app/Http/Controllers/Api/AsetKaryawanApiController.php`
- Middleware token: `app/Http/Middleware/InventoryApiToken.php`
