<?php

namespace App\Models;

use App\Helpers\SatuanBahanHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseDetail extends Model
{
    use HasFactory;
    protected $table = 'purchase_details';
    protected $guarded = [];


    /**
     * Catat satu lot stok masuk, sekaligus mengonversi satuannya.
     *
     * Ini satu-satunya tempat angka satuan dokumen (batang) jadi angka satuan
     * ledger (cm). Dipusatkan di sini karena lot dibuat dari beberapa alur yang
     * berbeda — bahan masuk manual, hasil QC bahan masuk, retur dari proyek,
     * dan endpoint API — dan kalau konversinya ditulis ulang di masing-masing,
     * satu yang terlewat akan menghasilkan stok yang salah ratusan kali.
     *
     * `qty` dan `unit_price` diberikan dalam satuan dokumen sumbernya:
     * pembelian memakai batang, retur dari proyek memakai cm. `sub_total`
     * sengaja diminta dari pemanggil, bukan dihitung ulang dari harga per cm,
     * supaya nilai yang masuk pembukuan tetap eksak.
     */
    public static function catatLot(array $atribut, ?string $satuanInput = null): self
    {
        $bahan = $atribut['bahan_id'] instanceof Bahan
            ? $atribut['bahan_id']
            : Bahan::find($atribut['bahan_id']);

        $panjangStandar = SatuanBahanHelper::panjangStandar($bahan);
        $qtyDasar = SatuanBahanHelper::keSatuanDasar($atribut['qty'], $satuanInput, $panjangStandar);

        return self::create([
            'purchase_id' => $atribut['purchase_id'],
            'bahan_id' => $bahan?->id ?? $atribut['bahan_id'],
            'panjang_standar' => $panjangStandar,
            'qty' => $qtyDasar,
            'sisa' => $atribut['sisa'] ?? $qtyDasar,
            'unit_price' => SatuanBahanHelper::keHargaSatuanDasar($atribut['unit_price'], $satuanInput, $panjangStandar),
            'sub_total' => $atribut['sub_total'] ?? SatuanBahanHelper::subTotal($atribut['qty'], $atribut['unit_price']),
        ]);
    }

    /**
     * Ubah lot lama bahan batangan dari satuan batang ke satuan ledger.
     *
     * Dipakai saat panjang standar baru diisi pada bahan yang lotnya dibuat
     * sebelum fitur dwi-satuan ada. Lot seperti itu menyimpan `qty` dalam
     * batang dan `unit_price` per batang, sementara mulai saat itu aplikasi
     * membaca kolomnya sebagai cm - tanpa konversi, "2 batang" akan terbaca
     * "2 cm".
     *
     * Yang disentuh hanya lot yang `panjang_standar`-nya masih null. Lot yang
     * sudah punya salinan panjang berarti sudah dicatat dalam cm sejak awal,
     * dan mengonversinya lagi akan menggandakan angkanya.
     *
     * `sub_total` sengaja tidak diubah: nilainya tidak ikut berubah oleh
     * pergantian satuan, jadi membiarkannya justru menjaga angka pembukuan
     * tetap sama persis.
     *
     * Pemanggilnya yang wajib memastikan tidak ada sisa berjalan. Method ini
     * tidak memeriksanya sendiri supaya pesan penolakannya bisa dirakit di
     * tempat yang tahu konteks formnya.
     */
    public static function konversiLotLama($bahan, int $panjangStandar): int
    {
        $bahanId = $bahan instanceof Bahan ? $bahan->id : $bahan;

        $lots = self::where('bahan_id', $bahanId)
            ->whereNull('panjang_standar')
            ->get();

        foreach ($lots as $lot) {
            $lot->qty = SatuanBahanHelper::keSatuanDasar($lot->qty, SatuanBahanHelper::SATUAN_BATANG, $panjangStandar);
            $lot->sisa = SatuanBahanHelper::keSatuanDasar($lot->sisa, SatuanBahanHelper::SATUAN_BATANG, $panjangStandar);
            $lot->unit_price = SatuanBahanHelper::keHargaSatuanDasar($lot->unit_price, SatuanBahanHelper::SATUAN_BATANG, $panjangStandar);
            $lot->panjang_standar = $panjangStandar;
            $lot->save();
        }

        return $lots->count();
    }

    /**
     * Setel ulang lot bahan batangan ke panjang standar yang baru.
     *
     * Dipakai saat panjang standar yang sudah terisi diperbaiki - biasanya
     * karena angkanya salah ketik, mis. pipa 400 cm sempat tercatat 600. Yang
     * dipertahankan adalah jumlah batangnya, bukan angka cm-nya: 10 batang
     * tetap 10 batang, panjangnya saja yang berubah. Jadi sisa 6.000 cm pada
     * panjang 600 menjadi 4.000 cm pada panjang 400.
     *
     * Harga digeser berbanding balik supaya harga per batang tidak berubah:
     * 291,6667 per cm pada batang 600 cm menjadi 437,50 per cm pada batang 400
     * cm - dua-duanya Rp 175.000 per batang. Dengan begitu nilai rupiah sisa
     * stok (`sisa` x `unit_price`) juga tidak bergeser, dan `sub_total` yang
     * sudah masuk pembukuan tidak perlu ikut diubah.
     *
     * Yang disentuh hanya lot yang salinan panjangnya sama dengan panjang lama.
     * Lot yang dibekukan pada ukuran lain memang pernah dibeli dalam ukuran itu,
     * jadi angkanya benar apa adanya. Lot yang salinannya masih null ditangani
     * konversiLotLama(), bukan di sini - angkanya masih dalam batang.
     *
     * Pemanggilnya yang wajib menjalankan ini dalam satu transaksi bersama
     * perubahan `bahan.panjang_standar`.
     */
    public static function setelUlangPanjangLot($bahan, int $panjangLama, int $panjangBaru): int
    {
        if ($panjangLama === $panjangBaru || $panjangLama < 1 || $panjangBaru < 1) {
            return 0;
        }

        $bahanId = $bahan instanceof Bahan ? $bahan->id : $bahan;

        $lots = self::where('bahan_id', $bahanId)
            ->where('panjang_standar', $panjangLama)
            ->get();

        $rasio = $panjangBaru / $panjangLama;

        foreach ($lots as $lot) {
            $lot->qty = round((float) $lot->qty * $rasio, 2);
            $lot->sisa = round((float) $lot->sisa * $rasio, 2);
            $lot->unit_price = round((float) $lot->unit_price / $rasio, 4);
            $lot->panjang_standar = $panjangBaru;
            $lot->save();
        }

        return $lots->count();
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function dataBahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    /**
     * Panjang standar yang berlaku untuk lot ini.
     *
     * Yang dipakai adalah salinan di baris lot-nya sendiri. Nilai dari tabel
     * `bahan` hanya jadi cadangan untuk lot lama yang dibuat sebelum kolom ini
     * ada; begitu salinannya terisi, mengedit master bahan tidak lagi mengubah
     * arti angka lot yang sudah tercatat.
     */
    public function panjangStandarEfektif(): ?int
    {
        return SatuanBahanHelper::panjangStandar($this->panjang_standar)
            ?? SatuanBahanHelper::panjangStandar($this->dataBahan);
    }

    /**
     * Angka lot sebagai teks siap tampil, mis. "6 Batang + 40 cm".
     *
     * Nama unit diambil lewat null-safe chain karena bahan bisa saja sudah
     * terhapus sementara barisnya masih ada untuk kebutuhan riwayat.
     */
    public function formatQty($qtyDasar): string
    {
        return SatuanBahanHelper::format(
            $qtyDasar,
            $this->panjangStandarEfektif(),
            $this->dataBahan->dataUnit->nama ?? null
        );
    }

    /**
     * Sisa lot sebagai teks siap tampil.
     */
    public function formatSisa(): string
    {
        return $this->formatQty($this->sisa);
    }

    /**
     * Nilai rupiah dari sejumlah satuan dasar pada lot ini.
     *
     * `unit_price` sudah dalam satuan ledger (per cm untuk bahan batangan),
     * jadi tidak ada prorata lagi di sini — cukup dikalikan.
     */
    public function nilaiUntuk($qtyDasar): float
    {
        return SatuanBahanHelper::nilaiSatuanDasar($qtyDasar, $this->unit_price);
    }

    /**
     * Harga lot ini dinyatakan per batang, untuk ditampilkan ke user.
     */
    public function hargaPerBatang(): float
    {
        return SatuanBahanHelper::dariHargaSatuanDasar(
            $this->unit_price,
            SatuanBahanHelper::SATUAN_BATANG,
            $this->panjangStandarEfektif()
        );
    }
}
