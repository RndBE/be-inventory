<?php

namespace App\Models;

use App\Helpers\SatuanBahanHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\MenampilkanQtyBahan;

class BahanReturDetails extends Model
{
    use HasFactory;
    use MenampilkanQtyBahan;

    protected $table = 'bahan_retur_details';
    protected $guarded = [];

    /**
     * Catat satu baris retur bahan dari proyek/produksi ke gudang.
     *
     * Retur di sini bukan pengembalian ke supplier: yang balik adalah sisa
     * bahan yang tidak terpakai. Untuk bahan batangan itu umumnya potongan,
     * tapi tidak selalu — batang utuh yang sama sekali tidak terpakai juga
     * pulang lewat jalur ini. Karena itu satuan inputnya bisa dipilih.
     *
     * `qty` tetap diterima dalam satuan ledger (cm untuk bahan batangan) dan
     * tidak dikonversi di sini. Konversinya dilakukan keranjang, karena
     * `sub_total` yang dikirim pemanggil sudah hasil `qty x unit_price` dengan
     * harga per cm — kalau angka batang yang masuk ke sini, subtotalnya ikut
     * salah 600 kali dan tidak ada tempat untuk membetulkannya.
     *
     * Dua argumen terakhir hanya merekam apa yang diketik orangnya — "1 batang"
     * atau "40 cm" — supaya riwayat dan cetakan bisa menampilkan angka yang
     * sama dengan yang dimasukkan. Tidak ada perhitungan stok yang boleh
     * mengambil angka dari situ. Pemanggil lama yang tidak mengirim keduanya
     * tetap tercatat sebagai cm, dan di sana angkanya memang sudah cm.
     *
     * Dipusatkan di satu method karena baris retur dibuat dari delapan
     * controller berbeda (produksi, projek, RnD, pengajuan, garansi, produk
     * sample, dan seterusnya) dengan bentuk yang persis sama.
     */
    public static function catatRetur(array $atribut, ?string $satuanInput = null, $qtyInput = null): self
    {
        $panjangStandar = isset($atribut['bahan_id'])
            ? SatuanBahanHelper::panjangStandar(Bahan::find($atribut['bahan_id']))
            : null;

        return self::create($atribut + [
            'qty_input' => $qtyInput ?? $atribut['qty'] ?? null,
            'satuan_input' => $panjangStandar ? SatuanBahanHelper::normalkanSatuan($satuanInput) : null,
        ]);
    }


    public function bahanRetur()
    {
        return $this->belongsTo(BahanRetur::class);
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function dataProduk()
    {
        return $this->belongsTo(BahanSetengahjadiDetails::class, 'produk_id');
    }

    public function dataProdukJadi()
    {
        return $this->belongsTo(ProdukJadiDetails::class, 'produk_jadis_id');
    }
}
