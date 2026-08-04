# Integrasi ke HRIS — Identitas Pegawai

Arah panggilannya **inventory → HRIS**, kebalikan dari
[api-hris-aset-karyawan.md](api-hris-aset-karyawan.md) yang mendokumentasikan HRIS
memanggil inventory. Keduanya berdiri sendiri dan memakai kunci yang berbeda.

## Kenapa diambil dari HRIS

Berita Acara Serah Terima Aset format resmi mencantumkan Nomor ID, Jabatan, dan
Divisi kedua pihak. HRIS pemilik sah data itu, dan salinan di inventory sudah
terbukti melenceng — divisi seorang HRD tercatat `Admin` di inventory, sedangkan
HRIS menyebut `HRD & CORPORATE SERVICE`.

## Kapan dipanggil

**Hanya saat BAST dibuat.** Hasilnya dibekukan ke kolom `hrd_nomor_id`,
`hrd_jabatan`, `hrd_divisi`, `jabatan_terdahulu`, dan `divisi_terdahulu` pada tabel
`serah_terima_aset`. Pencetakan PDF membaca kolom-kolom itu dan **tidak pernah**
memanggil HRIS.

Dua alasannya:

1. Dokumen yang sudah ditandatangani tidak boleh berubah isinya. Kalau identitas
   ditarik saat mencetak, cetak ulang tahun depan bisa memunculkan jabatan yang
   berbeda dari kertas yang sudah ada tanda tangannya.
2. Pencetakan tidak boleh gagal hanya karena HRIS sedang mati — berkasnya
   dibutuhkan tepat saat serah terima berlangsung.

## Endpoint

```
GET {HRIS_URL}/api/pegawai/by-email?email={email}
```

### Header wajib

```
X-API-KEY: <nilai HRIS_API_KEY>
Accept: application/json
```

### Konfigurasi

`.env` inventory:

```
HRIS_URL=http://127.0.0.1:8001
HRIS_API_KEY=<kunci dari tim HRIS>
HRIS_TIMEOUT=5
```

Dibaca lewat `config('services.hris.*')`, bukan `env()` langsung, supaya tetap
bekerja saat konfigurasi di-cache.

## Respons

### 200 — ditemukan

```json
{
  "name": "AVISSA NOVA FAUZISTIKA",
  "nomor_id": "003/HRDCS/II/2025",
  "jabatan": "HRD",
  "divisi": "HRD & CORPORATE SERVICE"
}
```

### 404 — email tidak terdaftar di HRIS

```json
{ "success": false, "message": "Pegawai dengan email tersebut tidak terdaftar di HRIS." }
```

Bukan gangguan, jadi tidak dicatat sebagai error. Nilainya jatuh ke data inventory.

### 422 — parameter kurang

```json
{ "success": false, "message": "Parameter email wajib diisi." }
```

### 401 — kunci salah

```json
{ "success": false, "message": "X-API-KEY tidak valid." }
```

## Perilaku saat gagal

Semua kegagalan berakhir sebagai `null`, tidak pernah melempar exception, dan
**tidak pernah menggagalkan pembuatan BAST**. Menahan proses keluar karyawan
karena sistem lain bermasalah itu berlebihan.

Cadangannya data user inventory, diperiksa **per kolom**: HRIS yang menjawab tapi
salah satu kolomnya kosong tetap dilengkapi dari inventory, daripada dibiarkan
bolong di dokumen resmi.

| Keadaan | Yang terjadi | Dicatat di log |
|---|---|---|
| HRIS mati / jaringan putus | Pakai data inventory | Ya |
| HTTP 401 / 5xx | Pakai data inventory | Ya |
| HTTP 404 (tidak terdaftar) | Pakai data inventory | Tidak |
| `HRIS_URL`/`HRIS_API_KEY` kosong | Pakai data inventory | Tidak |
| Kolom kosong di respons | Kolom itu saja dari inventory | Tidak |

## Prasyarat data

Pencocokannya lewat **email**, sama seperti arah sebaliknya. Email di HRIS dan di
inventory harus sama persis untuk pegawai yang sama. Yang berbeda akan selalu
jatuh ke data inventory tanpa pemberitahuan di layar — periksa log kalau isian
dokumen terlihat tidak sesuai HRIS.

## Berkas terkait

- Klien: `app/Services/HrisPegawai.php`
- Pemakaian: `app/Http/Controllers/SerahTerimaAsetController.php`
  (`identitasPihakKedua()` dan `jabatanTerdahulu()`)
- Konfigurasi: `config/services.php` bagian `hris`
