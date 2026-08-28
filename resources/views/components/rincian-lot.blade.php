{{--
    Satu baris rincian lot: berapa yang diambil dari lot itu dan harganya.

    Bahan biasa tampil seperti sebelumnya, mis. "10 x 382.883". Bahan batangan
    punya dua angka yang dua-duanya perlu terlihat: jumlah batang yang jadi
    dasar keputusan orang gudang, dan angka cm yang benar-benar tersimpan di
    ledger. Yang pertama jadi baris utama, yang kedua jadi keterangan kecil di
    bawahnya supaya angka di layar bisa dicocokkan dengan data kalau ada selisih.

    Harga per batang hanya ditulis kalau angkanya pas sejumlah batang utuh.
    Untuk potongan seperti "6 Batang + 40 cm", mengalikannya dengan harga per
    batang akan menghasilkan angka yang salah - jadi perkaliannya dibiarkan di
    baris cm, satu-satunya satuan yang harganya berlaku persis.

    `qty` dan `unitPrice` selalu dalam satuan ledger: untuk bahan batangan
    berarti cm dan harga per cm. Harga per batang dihitung di sini, tidak
    disimpan, karena itu cuma cara lain menampilkan harga yang sama.
--}}
@props([
    'qty' => 0,
    'unitPrice' => 0,
    'panjangStandar' => null,
    'namaUnit' => null,
    'desimalHarga' => 0,
])
@php
    $panjangLot = \App\Helpers\SatuanBahanHelper::panjangStandar($panjangStandar);
    $pecahanLot = $panjangLot ? \App\Helpers\SatuanBahanHelper::pecah($qty, $panjangLot) : null;
    $batangUtuh = $panjangLot && \App\Helpers\SatuanBahanHelper::kelipatanBatang($qty, $panjangLot);
@endphp
@if ($panjangLot)
    <div class="leading-tight">
        {{-- Kurang dari satu batang tidak punya baris batang: angkanya sama
             persis dengan baris cm di bawahnya, jadi cuma jadi pengulangan. --}}
        @if ($pecahanLot['batang'] > 0)
            <p>
                {{ \App\Helpers\SatuanBahanHelper::format($qty, $panjangLot, $namaUnit) }}
                @if ($batangUtuh)
                    x {{ number_format((float) $unitPrice * $panjangLot, 0, ',', '.') }}
                @endif
            </p>
        @endif
        <p @class(['text-xs text-gray-500 dark:text-gray-400' => $pecahanLot['batang'] > 0])>
            {{ number_format((float) $qty, 0, ',', '.') }} cm x
            {{ number_format((float) $unitPrice, 2, ',', '.') }}/cm
        </p>
    </div>
@else
    <p>{{ $qty }} x {{ number_format((float) $unitPrice, $desimalHarga, ',', '.') }}</p>
@endif
