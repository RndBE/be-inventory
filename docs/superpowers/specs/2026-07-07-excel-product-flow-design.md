# Excel Product Flow Export Design

## Goal

Tambahkan informasi flow produk hanya pada hasil export Excel untuk modul:

- Rekap semua Proyek RnD
- Bahan Keluar
- Produk Setengah Jadi
- Produk Sample
- Produk Jadi

Perubahan ini tidak menambah tampilan flow di halaman index, detail, atau modal UI. User cukup klik export seperti biasa, lalu file Excel berisi kolom tambahan yang menjelaskan asal dan tujuan barang/produk.

## Scope

Setiap export menambahkan kolom flow yang menjawab:

- Item ini jenisnya apa: bahan, produk setengah jadi, atau produk jadi.
- Kode sumbernya apa, jika tersedia.
- Item berasal dari proses/stok mana.
- Serial number apa, jika tersedia.
- Item keluar/dipakai untuk tujuan apa.
- Kode tujuan apa, jika tersedia.
- Status flow terakhir berdasarkan transaksi terkait.

Produk Sample perlu export yang benar dari data `ProdukSample`, karena export sample saat ini masih mengarah ke `ProjekExport` dan model `Projek`. Implementasi harus memperbaiki arah export sample agar file benar-benar menggambarkan produk sample.

## Data Sources

Flow diambil dari data existing, tanpa membuat tabel baru.

- `bahan_keluar` dan `bahan_keluar_details` sebagai sumber pergerakan keluar.
- `projek_rnd` dan `projek_rnd_details` sebagai tujuan/pemakaian RnD.
- `bahan_setengahjadis` dan `bahan_setengahjadi_details` sebagai stok produk setengah jadi.
- `produk_sample` dan `produk_sample_details` sebagai pemakaian sample.
- `produk_jadis` dan `produk_jadi_details` sebagai stok produk jadi.
- Relasi QC/produksi digunakan jika sudah tersedia di model untuk menjelaskan asal proses.

Jika data asal atau tujuan tidak lengkap, export harus tetap berhasil dan mengisi nilai fallback seperti `-` atau `Tidak diketahui`.

## Export Columns

Kolom dasar tiap export tetap dipertahankan. Kolom flow ditambahkan di sisi kanan tabel supaya tidak mengubah makna kolom existing.

Kolom standar flow:

- `Jenis Item`
- `Kode Sumber`
- `Asal Flow`
- `Serial Number Flow`
- `Tujuan Flow`
- `Kode Tujuan`
- `Status Flow`

Untuk export stok harian Setengah Jadi dan Produk Jadi, kolom flow boleh lebih ringkas:

- `Asal Flow`
- `Tujuan Flow Terakhir`
- `Kode Tujuan Terakhir`
- `Status Flow`

## Module Behavior

### Bahan Keluar

Setiap baris detail bahan keluar menampilkan item yang keluar, asal stoknya, dan tujuan transaksi bahan keluar. Tujuan ditentukan dari relasi induk bahan keluar, seperti RnD, Sample, Produksi, Produk Jadi, atau keterangan transaksi jika relasi tujuan tidak spesifik.

### Rekap Proyek RnD

Setiap baris detail RnD menampilkan asal bahan/produk yang dipakai. Jika detail menunjuk produk setengah jadi atau produk jadi, export mencantumkan kode transaksi stok dan serial number. Jika hanya bahan, export menampilkan sumber sebagai stok bahan/pembelian jika dapat dikenali.

### Produk Setengah Jadi

Setiap baris stok produk setengah jadi menampilkan kode produksi/transaksi masuk sebagai asal. Tujuan terakhir dicari dari `bahan_keluar_details` yang memakai detail produk setengah jadi tersebut, termasuk tujuan RnD, Sample, Produksi Produk Jadi, atau transaksi lain yang tersedia.

### Produk Sample

Export sample menampilkan header sample dan detail item yang dipakai. Tiap detail mencantumkan asal item yang dipakai serta status lanjut sample, misalnya belum dikirim QC, sudah masuk QC Produk Jadi, atau sudah menjadi stok Produk Jadi jika datanya tersedia.

### Produk Jadi

Setiap baris stok produk jadi menampilkan asal produk jadi, seperti produksi produk jadi atau sample jika relasinya tersedia. Tujuan terakhir dicari dari bahan keluar yang memakai detail produk jadi tersebut.

## Architecture

Buat service terpusat, misalnya `App\Services\ProductFlowService`, agar logika flow tidak tersebar di semua export.

Service menyediakan method kecil yang bisa dipakai export:

- Resolve flow dari `BahanKeluarDetails`.
- Resolve flow dari `ProjekRndDetails`.
- Resolve flow dari `BahanSetengahjadiDetails`.
- Resolve flow dari `ProdukSampleDetails`.
- Resolve flow dari `ProdukJadiDetails`.

Return value berupa array sederhana dengan key yang sama dengan kolom flow. Export bertugas menambahkan nilai tersebut ke row Excel dan menyesuaikan styling/border sampai kolom terakhir.

## Error Handling

Export tidak boleh gagal hanya karena relasi flow kosong. Semua accessor flow harus null-safe dan memberi fallback:

- `Jenis Item`: `Tidak diketahui`
- `Kode Sumber`: `-`
- `Asal Flow`: `Tidak diketahui`
- `Serial Number Flow`: `-`
- `Tujuan Flow`: `-`
- `Kode Tujuan`: `-`
- `Status Flow`: `-`

## Testing

Tambahkan unit test untuk service flow dengan skenario minimal:

- Bahan keluar detail dari bahan biasa.
- Bahan keluar detail dari produk setengah jadi.
- Produk setengah jadi yang pernah keluar ke transaksi bahan keluar.
- Produk jadi yang pernah keluar ke transaksi bahan keluar.
- Produk sample export menggunakan model `ProdukSample`, bukan `Projek`.

Tambahkan test export jika memungkinkan dengan memastikan heading flow muncul pada class export utama.

## Out of Scope

- Tidak membuat timeline visual di halaman.
- Tidak menambah tombol flow di tabel.
- Tidak membuat tabel audit/ledger baru.
- Tidak mengubah proses stok masuk/keluar existing.
- Tidak mengubah format import Excel.
