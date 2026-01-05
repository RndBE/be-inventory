@section('title', 'Info Projek RnD | BE INVENTORY')
<x-app-layout>
    @props(['variant' => ''])
    <x-app.secondary-header :variant="$attributes['headerVariant']">
        <div class="flex items-center space-x-3">
            <div class="p-1 flex items-center justify-end gap-x-2">
                <a href="{{ route('projek-rnd.index') }}" type="button"
                    class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Kembali</a>
            </div>
        </div>
    </x-app.secondary-header>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <div class="intro-y col-span-12 lg:col-span-12">
            <div class="bg-white shadow rounded-lg p-6 border mb-6">
                <div class="flex flex-wrap justify-between items-center border-b pb-3 gap-3">
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Histori Projek</h1>
                        <p class="text-sm text-gray-600">
                            Mulai projek:
                            {{ \Carbon\Carbon::parse($projekRnd->mulai_projek_rnd)->translatedFormat('d F Y H:i') }}
                            -
                            {{ \Carbon\Carbon::parse($projekRnd->selesai_projek_rnd)->translatedFormat('d F Y H:i') }}
                        </p>
                    </div>
                    <img src="{{ asset('images/logo_be2.png') }}" alt="Logo Be" class="h-10 w-auto">
                </div>

                <!-- Informasi Laporan -->
                <div class="mt-4 text-sm grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1">
                    <div class="space-y-1 text-sm">
                        <div class="flex">
                            <span class="w-40 font-semibold">Kode Produksi</span>
                            <span class="pr-1">:</span>
                            <span>{{ $projekRnd->kode_projek_rnd }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-40 font-semibold">Projek</span>
                            <span class="pr-1">:</span>
                            <span>{{ $projekRnd->nama_projek_rnd }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-40 font-semibold">Keterangan</span>
                            <span class="pr-1">:</span>
                            <span>{{ $projekRnd->keterangan }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-40 font-semibold">Petugas</span>
                            <span class="pr-1">:</span>
                            <span>{{ $projekRnd->pengaju }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6 border">
            <div class="flex flex-wrap justify-between items-center border-b pb-3 gap-3">
                <h2 class="text-lg font-bold text-gray-800">Detail Bahan Keluar</h2>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($projekRnd->bahanKeluar as $index => $bk)
                    <div x-data="{ open: false }" class="border rounded-lg shadow-sm">
                        <!-- Header Accordion Bahan Keluar -->
                        <button @click="open = !open"
                            class="w-full flex justify-between items-center px-4 py-3 bg-gray-100 hover:bg-gray-200 rounded-t-lg">
                            <div class="text-left">
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ $bk->kode_transaksi ?? '-' }}
                                </p>
                                <p class="text-xs text-gray-600">
                                    {{ \Carbon\Carbon::parse($bk->tgl_pengajuan)->translatedFormat('d F Y H:i') }}
                                    -
                                    {{ \Carbon\Carbon::parse($bk->tgl_keluar)->translatedFormat('d F Y H:i') }}
                                </p>
                            </div>
                            <svg :class="open ? 'rotate-180' : ''"
                                class="w-5 h-5 text-gray-600 transform transition-transform duration-200" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Content Accordion Bahan Keluar -->
                        <div x-show="open" x-collapse class="px-4 py-3 text-sm bg-white border-t space-y-3">
                            <div class="flex">
                                <span class="w-32 font-semibold">Tujuan</span>
                                <span class="pr-1">:</span>
                                <span>{{ $bk->tujuan ?? '-' }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-32 font-semibold">Keterangan</span>
                                <span class="pr-1">:</span>
                                <span>{{ $bk->keterangan ?? '-' }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-32 font-semibold">Pengaju</span>
                                <span class="pr-1">:</span>
                                <span>{{ $bk->dataUser->name ?? '-' }}</span>
                            </div>
                            <!-- Nested Accordion: Bahan Keluar Details -->
                            <div class="mt-4">
                                <h3 class="font-semibold text-gray-700 mb-2">Detail Bahan</h3>
                                @forelse($bk->bahanKeluarDetails as $detailIndex => $detail)
                                    <div x-data="{ openDetail: false }" class="border rounded mb-2">
                                        <button @click="openDetail = !openDetail"
                                            class="w-full flex justify-between items-center px-3 py-2 bg-gray-50 hover:bg-gray-100">
                                            <div class="text-sm">
                                                @if ($detail->dataBahan)
                                                    {{ $detail->dataBahan->nama_bahan }}
                                                @elseif($detail->dataProduk)
                                                    {{ $detail->dataProduk->nama_bahan }}
                                                @elseif($detail->dataProdukJadi)
                                                    {{ $detail->dataProdukJadi->nama_produk }}
                                                @else
                                                    {{ 'Bahan #' . ($detailIndex + 1) }}
                                                @endif
                                            </div>
                                            <svg :class="openDetail ? 'rotate-180' : ''"
                                                class="w-4 h-4 text-gray-500 transform transition-transform duration-200"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <div x-show="openDetail" x-collapse class="px-3 py-2 text-sm bg-white border-t">
                                            <div class="flex mb-1">
                                                <span class="w-28 font-medium">Qty</span>
                                                <span class="pr-1">:</span>
                                                <span>{{ $detail->qty }}</span>
                                            </div>
                                            <div class="flex mb-1">
                                                <span class="w-28 font-medium">Keterangan</span>
                                                <span class="pr-1">:</span>
                                                <span>
                                                    {{ $detail->projekRndDetails->keterangan_penanggungjawab ??
                                                        ($projekRnd->projekRndDetails->where('bahan_id', $detail->bahan_id)->where('produk_id', $detail->produk_id)->first()->keterangan_penanggungjawab ??
                                                            '-') }}
                                                </span>
                                                {{-- <span>{{ $detail->projekRndDetails->keterangan_penanggungjawab ?? '-' }}</span> --}}
                                                {{-- <span>{{ $detail->projekRndDetailsAktif->keterangan_penanggungjawab ?? '-' }}</span> --}}
                                            </div>
                                            <div class="flex mb-1">
                                                @php
                                                    $detailItems = json_decode($detail->details, true);
                                                @endphp

                                                <div class="mb-2">
                                                    <span class="w-28 font-medium inline-block">Details</span>
                                                    <span class="pr-1">:</span>
                                                </div>

                                                @if (is_array($detailItems) && count($detailItems) > 0)
                                                    <div class="overflow-x-auto">
                                                        <table class="min-w-full text-xs border">
                                                            <thead class="bg-gray-100">
                                                                <tr>
                                                                    <th class="px-2 py-1 border">Kode Transaksi</th>
                                                                    <th class="px-2 py-1 border">Qty</th>
                                                                    <th class="px-2 py-1 border">Unit Price</th>
                                                                    <th class="px-2 py-1 border">Sub Total</th>
                                                            </thead>
                                                            <tbody>
                                                                @php
                                                                    $grandTotal = 0;
                                                                @endphp

                                                                @foreach ($detailItems as $item)
                                                                    @php
                                                                        $qty = (float) ($item['qty'] ?? 0);
                                                                        $price = (float) ($item['unit_price'] ?? 0);
                                                                        $subTotal = $qty * $price;
                                                                        $grandTotal += $subTotal;
                                                                    @endphp
                                                                    <tr>
                                                                        <td>
                                                                            @php
                                                                                $trx =
                                                                                    $lookupTransaksi[
                                                                                        $item['kode_transaksi']
                                                                                    ] ?? null;
                                                                            @endphp

                                                                            @if ($trx && $trx['purchase'] && $trx['purchase']->qcBahanMasuk)
                                                                                <a href="{{ route('quality-page.qc-bahan-masuk.view', $trx['purchase']->qcBahanMasuk->id_qc_bahan_masuk) }}"
                                                                                    class="text-blue-600 hover:underline"
                                                                                    target="_blank">
                                                                                    {{ $trx['purchase']->qcBahanMasuk->kode_qc }}
                                                                                </a>
                                                                            @elseif($trx && $trx['produkSetengahJadi'] && $trx['produkSetengahJadi']->qcProdukSetengaJadi)
                                                                                <a href="{{ route('quality-page.qc-produk-setengah-jadi.view', $trx['produkSetengahJadi']->qcProdukSetengaJadi->id) }}"
                                                                                    class="text-blue-600 hover:underline"
                                                                                    target="_blank">
                                                                                    {{ $trx['produkSetengahJadi']->qcProdukSetengaJadi->kode_list }}
                                                                                </a>
                                                                            @elseif($trx && $trx['produkJadi'] && $trx['produkJadi']->qcProdukJadi)
                                                                                <a href="{{ route('quality-page.qc-produk-jadi.view', $trx['produkJadi']->qcProdukJadi->id) }}"
                                                                                    class="text-blue-600 hover:underline"
                                                                                    target="_blank">
                                                                                    {{ $trx['produkJadi']->qcProdukJadi->kode_list }}
                                                                                </a>
                                                                            @elseif($trx && $trx['purchase'])
                                                                                <a href="{{ route('purchases.show', $trx['purchase']->id) }}"
                                                                                    class="text-blue-600 hover:underline"
                                                                                    target="_blank">
                                                                                    {{ $item['kode_transaksi'] }}
                                                                                </a>
                                                                            @else
                                                                                {{ $item['kode_transaksi'] ?? '-' }}
                                                                            @endif

                                                                        </td>

                                                                        {{-- <td class="px-2 py-1 border">{{ $item['serial_number'] ?? '-' }}</td> --}}
                                                                        <td class="px-2 py-1 border text-right">
                                                                            {{ $qty }}</td>
                                                                        <td class="px-2 py-1 border text-right">
                                                                            {{ number_format($price, 2, ',', '.') }}
                                                                        </td>
                                                                        <td class="px-2 py-1 border text-right">
                                                                            {{ number_format($subTotal, 2, ',', '.') }}
                                                                        </td>
                                                                    </tr>
                                                                @endforeach

                                                                {{-- Baris Total --}}
                                                                <tr class="font-bold bg-gray-100">
                                                                    <td colspan="3"
                                                                        class="px-2 py-1 border text-right">Total</td>
                                                                    <td class="px-2 py-1 border text-right">
                                                                        {{ number_format($grandTotal, 2, ',', '.') }}
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @else
                                                    <p class="text-gray-500 text-xs">Tidak ada detail</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-500">Tidak ada detail bahan keluar</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-gray-500 border rounded-lg">
                        Tidak ada data bahan keluar
                    </div>
                @endforelse
            </div>

        </div>

        <div class="bg-white shadow rounded-lg p-6 mt-3 border">
            <div class="flex flex-wrap justify-between items-center border-b pb-3 gap-3">
                <h2 class="text-lg font-bold text-gray-800">Detail Bahan Retur</h2>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($projekRnd->dataBahanRetur as $index => $brtn)
                    <div x-data="{ open: false }" class="border rounded-lg shadow-sm">
                        <!-- Header Accordion Bahan Keluar -->
                        <button @click="open = !open"
                            class="w-full flex justify-between items-center px-4 py-3 bg-gray-100 hover:bg-gray-200 rounded-t-lg">
                            <div class="text-left">
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ $brtn->kode_transaksi ?? '-' }}
                                </p>
                                <p class="text-xs text-gray-600">
                                    {{ \Carbon\Carbon::parse($brtn->tgl_pengajuan)->translatedFormat('d F Y H:i') }}
                                    -
                                    {{ \Carbon\Carbon::parse($brtn->tgl_diterima)->translatedFormat('d F Y H:i') }}
                                </p>
                            </div>
                            <svg :class="open ? 'rotate-180' : ''"
                                class="w-5 h-5 text-gray-600 transform transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Content Accordion Bahan Keluar -->
                        <div x-show="open" x-collapse class="px-4 py-3 text-sm bg-white border-t space-y-3">
                            <div class="flex">
                                <span class="w-32 font-semibold">Tujuan</span>
                                <span class="pr-1">:</span>
                                <span>{{ $brtn->tujuan ?? '-' }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-32 font-semibold">Status</span>
                                <span class="pr-1">:</span>
                                <span>{{ $brtn->status ?? '-' }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-32 font-semibold">Pengaju</span>
                                <span class="pr-1">:</span>
                                <span>{{ $brtn->projek->pengaju ?? '-' }}</span>
                            </div>

                            <!-- Nested Accordion: Bahan Keluar Details -->
                            <div class="mt-4">
                                <h3 class="font-semibold text-gray-700 mb-2">Detail Bahan</h3>
                                @forelse($brtn->bahanReturDetails as $detailIndex => $detail)
                                    <div x-data="{ openDetail: false }" class="border rounded mb-2">
                                        <button @click="openDetail = !openDetail"
                                            class="w-full flex justify-between items-center px-3 py-2 bg-gray-50 hover:bg-gray-100">
                                            <div class="text-sm">
                                                @if ($detail->dataBahan)
                                                    {{ $detail->dataBahan->nama_bahan }}
                                                @elseif($detail->dataProduk)
                                                    {{ $detail->dataProduk->nama_bahan }}
                                                @elseif($detail->dataProdukJadi)
                                                    {{ $detail->dataProdukJadi->nama_produk }}
                                                @else
                                                    {{ 'Bahan #' . ($detailIndex + 1) }}
                                                @endif
                                            </div>
                                            <svg :class="openDetail ? 'rotate-180' : ''"
                                                class="w-4 h-4 text-gray-500 transform transition-transform duration-200"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <div x-show="openDetail" x-collapse
                                            class="px-3 py-3 text-sm bg-white border-t rounded-b space-y-2">
                                            <div class="flex justify-between">
                                                <span class="font-medium w-32">Serial Number</span>
                                                <span>{{ $detail->serial_number ?? '-' }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="font-medium w-32">Qty</span>
                                                <span>{{ $detail->qty ?? 0 }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="font-medium w-32">Unit Price</span>
                                                <span>Rp
                                                    {{ number_format($detail->unit_price ?? 0, 0, ',', '.') }}</span>
                                            </div>
                                            <div
                                                class="flex justify-between border-t pt-2 font-semibold text-gray-800">
                                                <span class="w-32">Sub Total</span>
                                                <span>Rp
                                                    {{ number_format($detail->sub_total ?? 0, 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-500">Tidak ada detail bahan retur</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-gray-500 border rounded-lg">
                        Tidak ada data bahan retur
                    </div>
                @endforelse
            </div>

        </div>

        <div class="bg-white shadow rounded-lg p-6 mt-3 border">
            <div class="flex flex-wrap justify-between items-center border-b pb-3 gap-3">
                <h2 class="text-lg font-bold text-gray-800">Detail Bahan Rusak</h2>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($projekRnd->dataBahanRusak as $index => $brrsk)
                    <div x-data="{ open: false }" class="border rounded-lg shadow-sm">
                        <!-- Header Accordion Bahan Keluar -->
                        <button @click="open = !open"
                            class="w-full flex justify-between items-center px-4 py-3 bg-gray-100 hover:bg-gray-200 rounded-t-lg">
                            <div class="text-left">
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ $brrsk->kode_transaksi ?? '-' }}
                                </p>
                                <p class="text-xs text-gray-600">
                                    {{ \Carbon\Carbon::parse($brrsk->tgl_pengajuan)->translatedFormat('d F Y H:i') }}
                                    -
                                    {{ \Carbon\Carbon::parse($brrsk->tgl_diterima)->translatedFormat('d F Y H:i') }}
                                </p>
                            </div>
                            <svg :class="open ? 'rotate-180' : ''"
                                class="w-5 h-5 text-gray-600 transform transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Content Accordion Bahan Keluar -->
                        <div x-show="open" x-collapse class="px-4 py-3 text-sm bg-white border-t space-y-3">
                            <div class="flex">
                                <span class="w-32 font-semibold">Status</span>
                                <span class="pr-1">:</span>
                                <span>{{ $brrsk->status ?? '-' }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-32 font-semibold">Pengaju</span>
                                <span class="pr-1">:</span>
                                <span>{{ $brrsk->projek->pengaju ?? '-' }}</span>
                            </div>

                            <!-- Nested Accordion: Bahan Keluar Details -->
                            <div class="mt-4">
                                <h3 class="font-semibold text-gray-700 mb-2">Detail Bahan</h3>
                                @forelse($brrsk->bahanRusakDetails as $detailIndex => $detail)
                                    <div x-data="{ openDetail: false }" class="border rounded mb-2">
                                        <button @click="openDetail = !openDetail"
                                            class="w-full flex justify-between items-center px-3 py-2 bg-gray-50 hover:bg-gray-100">
                                            <div class="text-sm">
                                                @if ($detail->dataBahan)
                                                    {{ $detail->dataBahan->nama_bahan }}
                                                @elseif($detail->dataProduk)
                                                    {{ $detail->dataProduk->nama_bahan }}
                                                @elseif($detail->dataProdukJadi)
                                                    {{ $detail->dataProdukJadi->nama_produk }}
                                                @else
                                                    {{ 'Bahan #' . ($detailIndex + 1) }}
                                                @endif
                                            </div>
                                            <svg :class="openDetail ? 'rotate-180' : ''"
                                                class="w-4 h-4 text-gray-500 transform transition-transform duration-200"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <div x-show="openDetail" x-collapse
                                            class="px-3 py-3 text-sm bg-white border-t rounded-b space-y-2">
                                            <div class="flex justify-between">
                                                <span class="font-medium w-32">Serial Number</span>
                                                <span>{{ $detail->serial_number ?? '-' }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="font-medium w-32">Qty</span>
                                                <span>{{ $detail->qty ?? 0 }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="font-medium w-32">Unit Price</span>
                                                <span>Rp
                                                    {{ number_format($detail->unit_price ?? 0, 0, ',', '.') }}</span>
                                            </div>
                                            <div
                                                class="flex justify-between border-t pt-2 font-semibold text-gray-800">
                                                <span class="w-32">Sub Total</span>
                                                <span>Rp
                                                    {{ number_format($detail->sub_total ?? 0, 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-500">Tidak ada detail bahan rusak</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-gray-500 border rounded-lg">
                        Tidak ada data bahan rusak
                    </div>
                @endforelse
            </div>

        </div>

    </div>
</x-app-layout>
