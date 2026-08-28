{{--
    Chip stok yang memisahkan jumlah batang dari sisa potongan.

    Bahan batangan punya dua angka dan keduanya tidak sederajat: jumlah batang
    itu yang dipakai orang mengambil keputusan, sisa potongan cuma pengecualian.
    Ditulis jadi satu ("490 Batang + 50 cm") keduanya jadi terbaca seperti satu
    angka panjang, dan chip-nya melebar sampai menabrak elemen di sebelahnya.
    Jadi batangnya saja yang masuk chip, sisanya turun jadi baris kecil.

    `qty` selalu dalam satuan ledger - cm untuk bahan batangan. Angka cm penuh
    tetap bisa dilihat lewat tooltip chip, untuk mencocokkan dengan data.

    Bahan biasa tidak berubah sama sekali: chip berisi angka dan nama unitnya.
    Kelas chip diserahkan pemanggil lewat `chipClass` karena tiap layar punya
    warna sendiri untuk membedakan bahan, setengah jadi, dan produk jadi.
--}}
@props([
    'qty' => 0,
    'panjangStandar' => null,
    'namaUnit' => null,
    'chipClass' => '',
    'align' => 'start',
    'labelBiasa' => null,
    // Sewarna dengan chip supaya dua angkanya terbaca sebagai satu kelompok,
    // tapi tanpa background dan border: yang membedakan tingkatannya adalah
    // berat tulisannya, bukan warnanya. Kalau ikut dikotaki, keduanya kembali
    // sederajat dan pemisahannya jadi tidak ada gunanya.
    'sisaClass' => 'text-xs font-medium text-green-700 dark:text-green-400',
])
@php
    $panjangChip = \App\Helpers\SatuanBahanHelper::panjangStandar($panjangStandar);
    $pecahChip = $panjangChip ? \App\Helpers\SatuanBahanHelper::pecah($qty, $panjangChip) : null;
    // Stok yang belum genap satu batang tidak punya angka batang untuk
    // ditampilkan, jadi cm-nya yang naik jadi chip utama. Stok kosong tetap
    // ditulis "0 Batang", bukan "0 cm" - yang habis barangnya, bukan panjangnya.
    $pakaiBatangChip = $pecahChip && ($pecahChip['batang'] > 0 || (float) $qty <= 0);
@endphp
<div class="inline-flex flex-col gap-0.5 {{ $align === 'end' ? 'items-end' : 'items-start' }}">
    <span class="{{ $chipClass }}" @if ($panjangChip) title="{{ number_format((float) $qty, 0, ',', '.') }} cm" @endif>
        {{ $slot ?? '' }}
        @if ($panjangChip)
            {{ $pakaiBatangChip
                ? $pecahChip['batang'] . ' ' . ($namaUnit ?: 'Batang')
                : \App\Helpers\SatuanBahanHelper::format($qty, $panjangChip) }}
        @else
            {{ $labelBiasa ?? trim($qty . ' ' . ($namaUnit ?? '')) }}
        @endif
    </span>
    @if ($pakaiBatangChip && $pecahChip['sisa'] > 0)
        <span class="{{ $sisaClass }}">
            + {{ \App\Helpers\SatuanBahanHelper::format($pecahChip['sisa'], $panjangChip) }}
        </span>
    @endif
</div>
