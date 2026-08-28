<?php

namespace App\Models;

use App\Helpers\SatuanBahanHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\MenampilkanQtyBahan;

class BahanRusakDetails extends Model
{
    use HasFactory;
    use MenampilkanQtyBahan;

    protected $table = 'bahan_rusak_details';
    protected $guarded = [];

    /**
     * Catat satu baris bahan rusak dari proyek/produksi.
     *
     * Kembarannya BahanReturDetails::catatRetur, dan aturannya sama: `qty`
     * diterima dalam satuan ledger (cm untuk bahan batangan) karena `sub_total`
     * yang dikirim pemanggil sudah memakai harga per cm. Konversi dari angka
     * yang diketik user dilakukan keranjang, bukan di sini.
     *
     * Dipusatkan karena baris rusak dibuat dari delapan controller dengan
     * bentuk yang persis sama, dan sebelum ini tidak ada satu pun yang mengisi
     * kolom jejak satuannya.
     */
    public static function catatRusak(array $atribut, ?string $satuanInput = null, $qtyInput = null): self
    {
        $panjangStandar = isset($atribut['bahan_id'])
            ? SatuanBahanHelper::panjangStandar(Bahan::find($atribut['bahan_id']))
            : null;

        return self::create($atribut + [
            'qty_input' => $qtyInput ?? $atribut['qty'] ?? null,
            'satuan_input' => $panjangStandar ? SatuanBahanHelper::normalkanSatuan($satuanInput) : null,
        ]);
    }

    public function bahanRusak()
    {
        return $this->belongsTo(BahanRusak::class);
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
