# Produk Sample QC Produk Jadi Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Route finished Produk Sample into QC Produk Jadi and finally Produk Jadi stock using a selected master Produk Jadi.

**Architecture:** Add nullable source columns for sample-origin QC rows, store `produk_jadi_id` on Produk Sample, and update controller/UI/Livewire logic to read either production-origin or sample-origin data.

**Tech Stack:** Laravel controllers, Eloquent models, Blade, Livewire, PHPUnit feature tests, migrations.

---

### Task 1: Write Regression Tests

**Files:**
- Create: `tests/Feature/ProdukSampleQcProdukJadiTest.php`

- [ ] Add sqlite schema setup for `produk_sample`, `produk_jadi`, `qc_produk_jadi_list`, `produk_jadis`, `produk_jadi_details`, `bahan_keluars`, `bahan_keluar_details`, and `log_activities`.
- [ ] Test `masukkanKeStok()` creates a `qc_produk_jadi_list` row with `produk_sample_id` and `produk_jadi_id`.
- [ ] Test `QcProdukJadiTable::prosesKeGudang()` creates `produk_jadis` and `produk_jadi_details` for sample-origin QC rows.
- [ ] Run `php artisan test tests/Feature/ProdukSampleQcProdukJadiTest.php` and confirm it fails because the new sample-to-QC-Jadi behavior is missing.

### Task 2: Add Schema Support

**Files:**
- Create: `database/migrations/2026_06_04_000001_add_produk_jadi_mapping_to_produk_sample_table.php`
- Create: `database/migrations/2026_06_04_000002_add_produk_sample_source_to_qc_produk_jadi_list_table.php`

- [ ] Add nullable `produk_jadi_id` FK to `produk_sample`.
- [ ] Make `qc_produk_jadi_list.produksi_produk_jadi_id` nullable and add nullable `produk_sample_id`.
- [ ] Add rollback logic for both migrations.

### Task 3: Update Models and Controller

**Files:**
- Modify: `app/Models/ProdukSample.php`
- Modify: `app/Models/QcProdukJadiList.php`
- Modify: `app/Http/Controllers/ProdukSampleController.php`

- [ ] Add `ProdukSample::produkJadi()` relation.
- [ ] Add `QcProdukJadiList::produkSample()` relation.
- [ ] Load `produkJadis` in create/edit forms.
- [ ] Store/update `produk_jadi_id` from Produk Sample forms.
- [ ] Replace `masukkanKeStok()` body so it creates `QcProdukJadiList` instead of `BahanSetengahjadi`.

### Task 4: Update Produk Sample UI

**Files:**
- Modify: `resources/views/pages/produk-sample/create.blade.php`
- Modify: `resources/views/pages/produk-sample/edit.blade.php`

- [ ] Add required Produk Jadi select.
- [ ] Keep button label `Masukkan ke Stok`, but submit to the new QC Produk Jadi behavior.
- [ ] Show `Sudah Dikirim ke QC Produk Jadi` when a sample already has a QC Produk Jadi row.

### Task 5: Update QC Produk Jadi Processing

**Files:**
- Modify: `app/Livewire/Quality/QcProdukJadiTable.php`
- Modify: `resources/views/livewire/quality/qc-produk-jadi-table.blade.php`

- [ ] Load `produkSample` and `produkJadi` relations.
- [ ] Generate serial number using `produk_jadi_id` for sample-origin rows.
- [ ] Create `produk_jadis.produk_sample_id` when processing sample-origin QC to gudang.
- [ ] Create `produk_jadi_details.produk_id` and `nama_produk` from `produkJadi` for sample-origin rows.

### Task 6: Verify

**Files:**
- Test: `tests/Feature/ProdukSampleQcProdukJadiTest.php`

- [ ] Run `php artisan test tests/Feature/ProdukSampleQcProdukJadiTest.php`.
- [ ] Run relevant existing QC sample tests if still present.
- [ ] Run `php artisan test` if targeted tests pass.
