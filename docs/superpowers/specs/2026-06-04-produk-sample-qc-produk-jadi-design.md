# Produk Sample to QC Produk Jadi Design

## Goal

Ubah aksi Produk Sample yang sudah `Selesai` agar tombol `Masukkan ke Stok` mengirim sample ke QC Produk Jadi, bukan lagi ke stok Produk Setengah Jadi atau QC Produk Setengah Jadi.

## Data Model

Produk Sample harus punya mapping ke master `produk_jadi` melalui `produk_jadi_id`. Mapping ini dipakai oleh QC Produk Jadi untuk membuat serial number dan oleh proses gudang untuk membuat stok di `produk_jadis` serta `produk_jadi_details`.

`qc_produk_jadi_list` perlu menerima sumber dari Produk Sample. Karena alur existing QC Produk Jadi berasal dari `produksi_produk_jadi_id`, kolom itu dibuat nullable dan ditambah `produk_sample_id` nullable. Satu baris QC hanya boleh berasal dari salah satu sumber: produksi produk jadi atau produk sample.

## Flow

Pada create/edit Produk Sample, user memilih master Produk Jadi. Ketika Produk Sample berstatus `Selesai`, tombol `Masukkan ke Stok` membuka modal konfirmasi dan submit ke route existing `produk-sample.masukkanKeStok`.

Controller menghitung HPP dari `bahan_keluar_details` yang bahan keluarnya sudah `Disetujui`, lalu membuat `qc_produk_jadi_list` dengan `produk_sample_id`, `produk_jadi_id`, `kode_list`, `qty = 1`, `unit_price`, dan `sub_total`.

Pada halaman QC Produk Jadi, data dari sample tampil memakai nama master Produk Jadi dan kode sample. Saat proses ke gudang, sistem membuat `produk_jadis` dengan `produk_sample_id` dan `id_qc_produk_jadi`, lalu membuat `produk_jadi_details` memakai `produk_jadi_id` dari QC.

## Validation

Produk Sample harus `Selesai`, punya `produk_jadi_id`, dan belum pernah dikirim ke QC Produk Jadi. Jika sudah ada QC Produk Jadi dengan `produk_sample_id` yang sama, aksi ditolak.

## Tests

Feature test membuktikan Produk Sample bisa dikirim ke QC Produk Jadi, dan proses QC Produk Jadi dari sample menghasilkan stok Produk Jadi.
