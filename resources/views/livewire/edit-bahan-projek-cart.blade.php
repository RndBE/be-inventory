<div>
    <div class="border-b border-gray-900/10 pt-2">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg pt-2">
            <div class="relative sm:rounded-lg pt-0">
                @if (!$isFirstTimePengajuan && $bahanKeluars->isEmpty())
                    <div class="p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800">
                        Tidak ada pengajuan!
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
                            <h3 class="text-lg font-medium">Informasi Pengajuan Bahan Projek {{ $firstTimeStatus }}</h3>
                        </div>
                        <div class="mt-2 mb-4 text-sm">
                            Berikut adalah daftar bahan yang diajukan untuk projek ini:
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
                                <h3 class="text-lg font-medium">Informasi Pengajuan Bahan Projek {{ $keluar->status }}</h3>
                            </div>
                            <div class="mt-2 mb-4 text-sm">
                                Berikut adalah daftar bahan yang diajukan untuk projek ini:
                            </div>
                            @php
                                $materials = $keluar->bahanKeluarDetails->map(function($detail) {
                                    return $detail->dataBahan->nama_bahan . ': ' . $detail->qty;
                                })->implode(' , ');
                            @endphp
                            {{ $materials }}
                        @endforeach
                        <div class="flex justify-end mt-3">
                            <button type="button" class="text-red-800 bg-transparent border border-red-800 hover:bg-red-900 hover:text-white focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-xs px-3 py-1.5 text-center dark:hover:bg-red-600 dark:border-red-600 dark:text-red-400 dark:hover:text-white dark:focus:ring-red-800" data-dismiss-target="#alert-additional-content-3" aria-label="Close">
                                Tutup
                            </button>
                        </div>
                    </div>
                @endif
            </div>
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-1/5">Bahan</th>
                        <th scope="col" class="px-6 py-3 w-0.5">Kebutuhan</th>
                        <th scope="col" class="px-6 py-3 w-0.5">Ambil Stok</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Sub Total 1</th>
                        <th scope="col" class="px-6 py-3 text-right w-1">Details</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Sub Total 2</th>
                        <th scope="col" class="px-6 py-3 text-center w-0.5">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grandTotal = 0;
                    @endphp
                    @foreach ($projekDetails as $detail)
                    <input type="hidden" name="projekDetails" value="{{ json_encode($this->getCartItemsForStorage()) }}">
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $detail['bahan']->nama_bahan }}</td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                            <input value="{{ old('jml_bahan.'.$detail['bahan']->id, $this->jml_bahan[$detail['bahan']->id] ?? 0) }}"
                                type="number"
                                wire:model="jml_bahan.{{ $detail['bahan']->id }}"
                                wire:keyup="updateQuantity({{ $detail['bahan']->id }})"
                                class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                placeholder="0" min="0" required @if($this->produksiStatus === 'Selesai') disabled @endif/>
                        </td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                            <div class="flex items-center">
                                <input value="{{ old('qty.'.$detail['bahan']->id, $qty[$detail['bahan']->id] ?? 0) }}"
                                    type="number"
                                    wire:model="qty.{{ $detail['bahan']->id }}"
                                    wire:keyup="updateQuantity({{ $detail['bahan']->id }})"
                                    class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    placeholder="0" min="0" required @if($this->produksiStatus === 'Selesai') disabled @endif/>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-semibold text-right text-gray-900 dark:text-white">
                            <span><strong>Rp.</strong> {{ number_format($subtotals[$detail['bahan']->id] ?? 0, 0, ',', '.') }}</span>
                        </td>

                        <td class="items-right px-6 py-4 text-right">
                            @foreach($detail['details'] as $d)

                            <div class="flex flex-col space-y-2">
                                <div class="flex justify-end items-center">
                                    <p>{{ $d['qty'] }} x {{ number_format($d['unit_price'], 0, ',', '.') }}</p>
                                    @if($produksiStatus !== 'Selesai')
                                        <button wire:click="decreaseQuantityPerPrice({{ $detail['bahan']->id }}, {{ $d['unit_price'] }})"
                                            class="inline-flex items-center justify-center p-1 text-sm font-medium h-6 w-6 text-gray-500 bg-white border border-gray-300 rounded-full focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                                            type="button">
                                            <span class="sr-only">Decrease Quantity</span>
                                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16"/>
                                            </svg>
                                        </button>
                                        <button wire:click="returQuantityPerPrice({{ $detail['bahan']->id }}, {{ $d['unit_price'] }})"
                                            class="inline-flex items-center justify-center p-1 text-sm font-medium h-6 w-6 text-gray-500 bg-white border border-gray-300 rounded-full focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                                            type="button">
                                            <span class="sr-only">Retur Quantity</span>
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
                        <td class="px-6 py-4 flex justify-center items-center">
                            @if(isset($detail['newly_added']) && $detail['newly_added'])
                                <a href="#" class="font-medium text-red-600 dark:text-red-500 hover:underline" wire:click.prevent="removeItem({{ $detail['bahan']->id }})">
                                    <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd"/>
                                    </svg>
                                </a>
                            @endif
                        </td>
                    </tr>
                    @php
                        $subtotal = $subtotals[$detail['bahan']->id] ?? 0;
                        $grandTotal += $subtotal;
                    @endphp
                    @endforeach
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white"></td>
                        <td class="px-6 py-4 text-right text-black"></td>
                        <td class="px-6 py-4 text-right text-black"></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"><strong>Rp.</strong> {{ number_format($grandTotal, 0, ',', '.') }}</span></td>
                        <td class="px-6 py-4 text-center text-black">+</td>

                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <span><strong>Rp.</strong> {{ number_format($produksiTotal, 0, ',', '.') }}</span>
                        </td>
                    </tr>
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white"></td>
                        <td class="px-6 py-4 text-right text-black"></td>
                        <td class="px-6 py-4 text-right text-black"></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                        <td class="px-6 py-4 text-right text-black"><strong>Total Harga</strong></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <span><strong>Rp.</strong> {{ number_format($produksiTotal+$grandTotal, 0, ',', '.') }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if($produksiStatus !== 'Selesai')
        <div class="border-b border-gray-900/10">
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
                        @foreach($bahanRusak as $rusak)
                        <input type="hidden" name="bahanRusak" value="{{ json_encode($this->getCartItemsForBahanRusak()) }}">
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ App\Models\Bahan::find($rusak['id'])->nama_bahan ?? 'Unknown' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end items-center">
                                        {{ $rusak['qty'] }} x {{ number_format($rusak['unit_price'], 0, ',', '.') }}
                                        <button type="button" wire:click="returnToProduction({{ $rusak['id'] }}, {{ $rusak['unit_price'] }}, 1)" class="text-blue-600 hover:underline">
                                            <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16 10 3-3m0 0-3-3m3 3H5v3m3 4-3 3m0 0 3 3m-3-3h14v-3"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    {{ number_format($rusak['unit_price'] * $rusak['qty'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif


    @if($produksiStatus !== 'Selesai')
        <div class="border-b border-gray-900/10">
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
                        @foreach($bahanRetur as $retur)
                        <input type="hidden" name="bahanRetur" value="{{ json_encode($this->getCartItemsForBahanRetur()) }}">
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ App\Models\Bahan::find($retur['id'])->nama_bahan ?? 'Unknown' }}</td>
                                <td class="px-6 py-4">
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
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
