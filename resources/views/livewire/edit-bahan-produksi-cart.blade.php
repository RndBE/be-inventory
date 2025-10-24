<div>

    <div class=" border-gray-900/10 pt-4">
        <div class="relative sm:rounded-lg pt-0">
            @if (!$isFirstTimePengajuan && $bahanKeluars->isEmpty())
                <div id="alert-2" class="flex items-center p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800" role="alert">
                    <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                    </svg>
                    <span class="sr-only">Info</span>
                    <div class="ms-3 text-sm font-medium">
                        Tidak ada pengajuan bahan produksi!
                    </div>
                    <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700" data-dismiss-target="#alert-2" aria-label="Close">
                        <span class="sr-only">Close</span>
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                    </button>
                </div>
            @endif
            @if ($isFirstTimePengajuan && $bahanKeluars->isNotEmpty())
                @php
                    $firstTimeMaterials = $bahanKeluars->flatMap(function($keluar) {
                        return $keluar->bahanKeluarDetails->map(function($detail) {
                            return $detail->dataBahan->nama_bahan . ': ' . $detail->qty;
                        });
                    })->implode(' , ');

                    $firstTimeStatus = $bahanKeluars->first()->status ?? 'Status tidak ditemukan';
                @endphp
                <div id="alert-additional-content-3" class="p-4 mb-4 text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
                    <div class="flex items-center">
                        <svg class="flex-shrink-0 w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                        </svg>
                        <span class="sr-only">Info</span>
                        <h3 class="text-lg font-medium">Informasi Pengajuan Bahan Produksi {{ $firstTimeStatus }}</h3>
                    </div>
                    <div class="mt-2 mb-4 text-sm">
                        Berikut adalah daftar bahan yang diajukan untuk produksi ini:
                    </div>
                    {{ $firstTimeMaterials }}
                    <div class="flex justify-end mt-3">
                        <button type="button" class="text-red-800 bg-transparent border border-red-800 hover:bg-red-900 hover:text-white focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-xs px-3 py-1.5 text-center dark:hover:bg-red-600 dark:border-red-600 dark:text-red-400 dark:hover:text-white dark:focus:ring-red-800" data-dismiss-target="#alert-additional-content-3" aria-label="Close">
                            Tutup
                        </button>
                    </div>
                </div>
            @endif
            @if (!$isFirstTimePengajuan && $bahanKeluars->isNotEmpty())
                <div id="alert-additional-content-3" class="p-4 mb-4 text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
                    @foreach($bahanKeluars as $keluar)
                        <div class="flex items-center">
                            <svg class="flex-shrink-0 w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                            </svg>
                            <span class="sr-only">Info</span>
                            <h3 class="text-lg font-medium">Informasi Pengajuan Bahan Produksi {{ $keluar->status }}</h3>
                        </div>
                        <div class="mt-2 mb-4 text-sm">
                            Berikut adalah daftar bahan yang diajukan untuk produksi ini:
                        </div>
                        @php
                            $materials = $keluar->bahanKeluarDetails->map(function($detail) {
                                return $detail->dataBahan->nama_bahan . ': ' . $detail->qty;
                            })->implode(' , ');
                        @endphp
                        {{ $materials }}
                    @endforeach
                    <div class="flex justify-end mt-3">
                        <button type="button" class="text-red-800 bg-transparent border border-red-800 hover:bg-red-800 hover:text-white focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-xs px-3 py-1.5 text-center dark:hover:bg-red-600 dark:border-red-600 dark:text-red-400 dark:hover:text-white dark:focus:ring-red-800" data-dismiss-target="#alert-additional-content-3" aria-label="Close">
                            Tutup
                        </button>
                    </div>
                </div>
            @endif
            @if (!$isBahanReturPending && !$isBahanRusakPending)
                <!-- No pending submissions -->
                <div id="alert-3" class="flex items-center p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800" role="alert">
                    <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                    </svg>
                    <span class="sr-only">Info</span>
                    <div class="ms-3 text-sm font-medium">
                        Tidak ada pengajuan bahan retur atau bahan rusak!
                    </div>
                    <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700" data-dismiss-target="#alert-3" aria-label="Close">
                        <span class="sr-only">Close</span>
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                    </button>
                </div>
            @else
                <!-- Display pending submissions count -->
                <div id="alert-3" class="flex items-center p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
                    <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                    </svg>
                    <span class="sr-only">Info</span>
                    <div class="ms-3 text-sm font-medium">
                        @if ($isBahanReturPending)
                            Ada {{ $pendingReturCount }} pengajuan retur yang belum disetujui!
                        @endif
                        @if ($isBahanRusakPending)
                            Ada {{ $pendingRusakCount }} pengajuan bahan rusak yang belum disetujui!
                        @endif
                    </div>
                    <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700" data-dismiss-target="#alert-3" aria-label="Close">
                        <span class="sr-only">Close</span>
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                    </button>
                </div>
            @endif


        </div>



        <div class="relative overflow-x-auto shadow-md sm:rounded-lg pt-0">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-1/5">Bahan</th>
                        <th scope="col" class="px-6 py-3 text-center w-0.5">Kebutuhan</th>
                        <th scope="col" class="px-6 py-3 text-center w-0.5">Kekurangan</th>
                        <th scope="col" class="px-6 py-3 text-center w-0.5">Stok</th>
                        <th scope="col" class="px-6 py-3 text-center w-0.5">Ambil Stok</th>
                        {{-- <th scope="col" class="px-6 py-3 text-right w-0.5">Sub Total</th> --}}
                        <th scope="col" class="px-6 py-3 text-right w-1">Details</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Sub Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grandTotal = 0;
                    @endphp
                    @foreach($produksiDetails as $detail)
                    <input type="hidden" name="produksiDetails" value="{{ json_encode($this->getCartItemsForStorage()) }}">
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $detail['bahan']->nama_bahan }}</td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                            {{ $detail['jml_bahan'] }}
                        </td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                            {{ $detail['jml_bahan'] - $detail['used_materials'] }}
                        </td>
                        <td class="items-center px-6 py-4 text-gray-900 dark:text-white text-cente">
                            <div class="flex flex-col space-y-2">
                                <div class="flex justify-center items-center">
                                    @php
                                        $usedMaterials = 0;
                                        $stokSaatIni = 0;
                                        $unitPrices = []; // Inisialisasi array untuk menyimpan unit price
                                        $totalPrice = 0; // Inisialisasi total price

                                        // Hitung jumlah maksimum yang diizinkan berdasarkan detail produksi
                                        $maxAllowedQty = $detail['jml_bahan'] - $detail['used_materials'];

                                        // Tentukan jumlah yang tersedia dan harga satuan yang sesuai
                                        if ($detail['bahan']->jenisBahan->nama === 'Produksi') {
                                            $bahanSetengahjadiDetails = $detail['bahan']->bahanSetengahjadiDetails()
                                                ->where('sisa', '>', 0)
                                                ->with(['bahanSetengahjadi' => function ($query) {
                                                    $query->orderBy('tgl_masuk', 'asc');
                                                }])->get();

                                            // Hitung stok dan gunakan materials
                                            $stokSaatIni = $bahanSetengahjadiDetails->sum('sisa');

                                            // Iterasi melalui bahan setengah jadi untuk menghitung used materials dan unit price
                                            foreach ($bahanSetengahjadiDetails as $bahan) {
                                                if ($usedMaterials < $maxAllowedQty) {
                                                    $qtyToUse = min($bahan->sisa, $maxAllowedQty - $usedMaterials);
                                                    $usedMaterials += $qtyToUse;
                                                    $unitPrices[] = $bahan->unit_price; // Simpan unit price

                                                    // Jika unit price sudah disimpan, kita tambahkan total price
                                                    // $totalPrice += $qtyToUse * $bahan->unit_price; // Hitung total price per transaksi
                                                }
                                            }
                                        } else {
                                            $purchaseDetails = $detail['bahan']->purchaseDetails()
                                                ->where('sisa', '>', 0)
                                                ->with(['purchase' => function ($query) {
                                                    $query->orderBy('tgl_masuk', 'asc');
                                                }])->get();

                                            // Hitung stok dan gunakan materials
                                            $stokSaatIni = $purchaseDetails->sum('sisa');

                                            // Iterasi melalui purchase details untuk menghitung used materials dan unit price
                                            foreach ($purchaseDetails as $purchase) {
                                                if ($usedMaterials < $maxAllowedQty) {
                                                    $qtyToUse = min($purchase->sisa, $maxAllowedQty - $usedMaterials);
                                                    $usedMaterials += $qtyToUse;
                                                    $unitPrices[] = $purchase->unit_price; // Simpan unit price

                                                    // Jika unit price sudah disimpan, kita tambahkan total price
                                                    // $totalPrice += $qtyToUse * $purchase->unit_price; // Hitung total price per transaksi
                                                }
                                            }
                                        }

                                        // Akumulasi grand total
                                        // $grandTotal += $totalPrice; // Update grand total
                                    @endphp
                                    {{ $stokSaatIni }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-semibold text-center text-gray-900 dark:text-white">
                            {{ $usedMaterials }}
                        </td>
                        {{-- <td class="px-6 py-4 font-semibold text-right text-gray-900 dark:text-white">
                            <span>
                                <strong></strong>
                                {{ $totalPrice > 0 ? number_format($totalPrice, 0, ',', '.') : 0 }}
                            </span>
                        </td> --}}
                        <td class="items-right px-6 py-4 text-right">
                            @foreach($detail['details'] as $d)
                            <div class="flex flex-col space-y-2">
                                <div class="flex justify-end items-center">
                                    <p>{{ $d['qty'] }} x {{ number_format($d['unit_price'], 0, ',', '.') }}</p>
                                    @if($produksiStatus !== 'Selesai')
                                        <button wire:click="decreaseQuantityPerPrice({{ $detail['bahan']->id }}, {{ $d['unit_price'] }})"
                                            class="inline-flex items-center justify-center p-1 text-sm font-medium h-6 w-6 text-gray-500 bg-white border border-gray-300 rounded-full focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                                            type="button">
                                            {{-- <span class="sr-only">Decrease Quantity</span> --}}
                                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16"/>
                                            </svg>
                                        </button>
                                        <button wire:click="returQuantityPerPrice({{ $detail['bahan']->id }}, {{ $d['unit_price'] }})"
                                            class="inline-flex items-center justify-center p-1 text-sm font-medium h-6 w-6 text-gray-500 bg-white border border-gray-300 rounded-full focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                                            type="button">
                                            {{-- <span class="sr-only">Retur Quantity</span> --}}
                                            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-back"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11l-4 4l4 4m-4 -4h11a4 4 0 0 0 0 -8h-1" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </td>
                        <td class="px-6 py-4 font-semibold text-right text-gray-900 dark:text-white">
                            <span><strong></strong> {{ number_format($detail['sub_total'], 0, ',', '.') }}</span>
                        </td>
                    </tr>
                    @endforeach
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white"></td>
                        <td class="px-6 py-4 text-right text-black"></td>
                        <td class="px-6 py-4 text-right text-black"></td>
                        <td class="px-6 py-4 text-right text-black"></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                        <td class="px-6 py-4 font-semibold text-center text-black">Total Harga</td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <span><strong>Rp.</strong> {{ number_format($produksiTotal, 0, ',', '.') }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
        @if($produksiStatus !== 'Selesai')
        <div class=" border-gray-900/10">
            <h1 class="mt-6"><strong>Bahan Rusak</strong></h1>
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg pt-0">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3" style="width: 30%;">Bahan</th>
                            <th scope="col" class="px-6 py-3 text-right">Qty</th>
                            <th scope="col" class="px-6 py-3 text-right">Sub Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bahanRusak as $index => $rusak)
                        <input type="hidden" name="bahanRusak" value="{{ json_encode($this->getCartItemsForBahanRusak()) }}">
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ App\Models\Bahan::find($rusak['id'])->nama_bahan ?? 'Unknown' }}</td>
                                {{-- <td class="px-6 py-4">
                                    <div class="flex justify-end items-center">
                                        {{ $rusak['qty'] }} x {{ number_format($rusak['unit_price'], 0, ',', '.') }}
                                        <button type="button" wire:click="returnToProduction({{ $rusak['id'] }}, {{ $rusak['unit_price'] }}, 1)" class="text-blue-600 hover:underline">
                                            <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16 10 3-3m0 0-3-3m3 3H5v3m3 4-3 3m0 0 3 3m-3-3h14v-3"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td> --}}
                                {{-- <td class="px-6 py-4 text-right">
                                    {{ number_format($rusak['unit_price'] * $rusak['qty'], 0, ',', '.') }}
                                </td> --}}
                                <td class="px-6 py-4">
                                    <div class="flex justify-end items-center gap-2">
                                        {{-- Input manual qty --}}
                                        <input type="text" pattern="[0-9]+([,\.][0-9]+)?" inputmode="decimal"
                                            class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            wire:model.defer="bahanRusak.{{ $index }}.qty"
                                            wire:change="updateRusakQty({{ $rusak['id'] }}, {{ $rusak['unit_price'] }}, $event.target.value)">

                                        x {{ number_format($rusak['unit_price'] ?? 0, 0, ',', '.') }} <br>

                                        {{-- Tombol hapus/cancel rusak --}}
                                        <button type="button"
                                            wire:click="returnToProduction({{ $rusak['id'] }}, {{ $rusak['unit_price'] }})"
                                            class="text-blue-600 hover:underline">
                                            <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="m16 10 3-3m0 0-3-3m3 3H5v3m3 4-3 3m0 0 3 3m-3-3h14v-3"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    {{-- {{ number_format(($rusak['unit_price'] ?? 0) * floatval($rusak['qty'] ?? 0), 0, ',', '.') }} --}}
                                    {{ number_format(round(($rusak['unit_price'] ?? 0) * floatval($rusak['qty'] ?? 0), 0), 0, ',', '.') }}

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        @if($produksiStatus !== 'Selesai')
        <div class=" border-gray-900/10">
            <h1 class="mt-6"><strong>Bahan Retur</strong></h1>
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg pt-0">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3" style="width: 30%;">Bahan</th>
                            <th scope="col" class="px-6 py-3 text-right">Qty</th>
                            <th scope="col" class="px-6 py-3 text-right">Sub Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bahanRetur as $index => $retur)
                        <input type="hidden" name="bahanRetur" value="{{ json_encode($this->getCartItemsForBahanRetur()) }}">
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ App\Models\Bahan::find($retur['id'])->nama_bahan ?? 'Unknown' }}</td>
                                {{-- <td class="px-6 py-4">
                                    <div class="flex justify-end items-center">
                                        {{ $retur['qty'] }} x {{ number_format($retur['unit_price'], 0, ',', '.') }}
                                        <button type="button" wire:click="returnReturToProduction({{ $retur['id'] }}, {{ $retur['unit_price'] }}, 1)" class="text-blue-600 hover:underline">
                                            <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16 10 3-3m0 0-3-3m3 3H5v3m3 4-3 3m0 0 3 3m-3-3h14v-3"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    {{ number_format($retur['unit_price'] * $retur['qty'], 0, ',', '.') }}
                                </td> --}}
                                <td class="px-6 py-4">
                                    <div class="flex justify-end items-center gap-2">
                                        {{-- Input manual qty --}}
                                        <input type="text" pattern="[0-9]+([,\.][0-9]+)?" inputmode="decimal"
                                            class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            wire:model.defer="bahanRetur.{{ $index }}.qty"
                                            wire:change="updateReturQty({{ $retur['id'] }}, {{ $retur['unit_price'] }}, $event.target.value)">

                                        x {{ number_format($retur['unit_price'] ?? 0, 0, ',', '.') }} <br>

                                        {{-- Tombol hapus/cancel retur --}}
                                        <button type="button"
                                            wire:click="returnReturToProduction({{ $retur['id'] }}, {{ $retur['unit_price'] }})"
                                            class="text-blue-600 hover:underline">
                                            <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="m16 10 3-3m0 0-3-3m3 3H5v3m3 4-3 3m0 0 3 3m-3-3h14v-3"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    {{-- {{ number_format(($retur['unit_price'] ?? 0) * floatval($retur['qty'] ?? 0), 0, ',', '.') }} --}}
                                    {{ number_format(round(($retur['unit_price'] ?? 0) * floatval($retur['qty'] ?? 0), 0), 0, ',', '.') }}

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>






</div>
