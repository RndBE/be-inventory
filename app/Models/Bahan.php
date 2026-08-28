<?php

namespace App\Models;

use App\Helpers\SatuanBahanHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bahan extends Model
{
    use HasFactory;
    protected $table = 'bahan';

    protected $guarded = [];

    /**
     * Bahan batangan boleh diinput per batang atau per cm.
     *
     * Angka stok yang tersimpan tetap dalam cm; lihat SatuanBahanHelper.
     */
    public function dwiSatuan(): bool
    {
        return SatuanBahanHelper::dwiSatuan($this);
    }

    /**
     * Angka satuan dasar jadi teks siap tampil, mis. "6 Batang + 40 cm".
     */
    public function formatQty($qtyDasar): string
    {
        return SatuanBahanHelper::format($qtyDasar, $this, $this->dataUnit->nama ?? null);
    }

    /**
     * Alasan kolom panjang standar tidak boleh diisi/diubah, atau null.
     *
     * Panjang standar menentukan arti setiap angka stok bahan ini: kolom `sisa`
     * dibaca sebagai cm begitu kolomnya terisi. Yang menentukan aman atau tidak
     * bukan ada tidaknya stok, tapi bisa atau tidaknya angka stok itu
     * dikonversi:
     *
     * - Panjang standar masih kosong dan lot juga belum punya salinan panjang:
     *   aman. Angka lot masih dalam batang, dan
     *   PurchaseDetail::konversiLotLama() mengalikannya ke cm di transaksi yang
     *   sama, termasuk sisa yang masih berjalan.
     * - Panjang standar sudah terisi lalu diganti angkanya: aman lewat form,
     *   karena PurchaseDetail::setelUlangPanjangLot() menyetel ulang lot ke
     *   ukuran baru dengan jumlah batang yang dipertahankan.
     * - Panjang standar masih kosong tapi ada lot berjalan yang sudah membawa
     *   salinan panjang: lot itu sudah tercatat cm sementara masternya bukan
     *   bahan batangan. Mengalikannya lagi akan menggandakan angkanya, jadi
     *   ditolak sampai sisanya habis.
     *
     * Dipakai bersama oleh BahanController::update(), BahanImport, dan form
     * edit bahan supaya ketiganya tidak bisa berbeda aturan.
     */
    public function alasanPanjangStandarTerkunci(): ?string
    {
        if (SatuanBahanHelper::panjangStandar($this) !== null) {
            return null;
        }

        if ($this->purchaseDetails()->whereNotNull('panjang_standar')->where('sisa', '>', 0)->exists()) {
            return 'Panjang standar belum bisa diisi karena bahan ini punya lot stok yang angkanya sudah tercatat dalam cm. Habiskan sisa lot itu dulu, atau buat data bahan baru.';
        }

        return null;
    }

    /**
     * Alasan menolak satu nilai panjang standar baru, atau null kalau boleh.
     *
     * Nilai yang sama dengan yang tersimpan tidak diperiksa sama sekali: form
     * dan import selalu mengirim ulang kolom ini, dan menolak nilai yang tidak
     * berubah akan memblokir penyuntingan kolom lain pada bahan yang stoknya
     * berjalan.
     *
     * `$setelUlangLot` membedakan dua jalur pemanggilnya. Form edit bahan
     * menyetel ulang lot begitu panjangnya berubah, jadi mengubah angka boleh
     * walau stok berjalan. Import tidak: satu sel Excel bisa menyentuh puluhan
     * bahan tanpa ada yang melihatnya satu per satu, dan menyetel ulang stok
     * sebanyak itu tanpa konfirmasi terlalu berisiko — jadi lewat import,
     * mengubah angka tetap harus menunggu stoknya habis.
     */
    public function alasanTolakPanjangStandar($panjangBaru, bool $setelUlangLot = true): ?string
    {
        $panjangLama = SatuanBahanHelper::panjangStandar($this);
        $panjangBaru = SatuanBahanHelper::panjangStandar($panjangBaru);

        if ($panjangLama === $panjangBaru) {
            return null;
        }

        // Mengosongkan panjang standar berarti aplikasi berhenti membaca kolom
        // qty sebagai cm, sementara lot yang sudah terkonversi tetap menyimpan
        // angka cm. Riwayatnya akan terbaca sebagai jumlah barang, dan tidak
        // ada jalan balik yang aman kecuali mengonversinya lagi satu per satu.
        if ($panjangBaru === null) {
            return $this->purchaseDetails()->exists()
                ? 'Panjang standar tidak bisa dikosongkan karena bahan ini sudah punya riwayat stok dalam cm. Buat data bahan baru kalau bahannya memang bukan batangan.'
                : null;
        }

        if ($panjangLama !== null && ! $setelUlangLot && $this->purchaseDetails()->where('sisa', '>', 0)->exists()) {
            return 'Panjang standar tidak bisa diubah karena bahan ini masih punya sisa stok. Ubah lewat form edit bahan supaya angka stoknya ikut disetel ulang, atau habiskan stoknya dulu.';
        }

        return $this->alasanPanjangStandarTerkunci();
    }

    public function jenisBahan()
    {
        return $this->belongsTo(JenisBahan::class, 'jenis_bahan_id');
    }

    public function dataUnit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'bahan_supplier');
    }

    public function produksiDetails()
    {
        return $this->hasMany(ProduksiDetails::class, 'bahan_id');
    }

    public function bahanKeluarDetails()
    {
        return $this->hasMany(BahanKeluarDetails::class, 'bahan_id');
    }

    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class, 'bahan_id'); // adjust if necessary
    }

    public function bahanSetengahjadiDetails()
    {
        return $this->hasMany(BahanSetengahjadiDetails::class, 'bahan_id');
    }

    public function firstPurchaseDetail()
    {
        return $this->hasOne(PurchaseDetail::class)->oldestOfMany();
    }
}
