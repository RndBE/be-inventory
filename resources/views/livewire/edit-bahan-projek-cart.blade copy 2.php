<div>

    <!-- Tabel Kanan -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg pt-2">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3 w-1/5">Bahan</th>
                    <th scope="col" class="px-6 py-3">Ambil Stok</th>
                    <th scope="col" class="px-6 py-3 text-right">Sub Total</th>
                    <th scope="col" class="px-6 py-3 text-right">Jumlah</th>
                    <th scope="col" class="px-6 py-3 text-right">Detail</th>
                    <th scope="col" class="px-6 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $grandTotal = 0;
                @endphp
                @foreach($cartItems as $item)
                <input type="hidden" name="cartItems" value="{{ json_encode($item) }}">

                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $item->nama_bahan }}</td>
                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                        <input value="{{ old('qty.'.$item->id, 0) }}"
                            type="number"
                            wire:model="qtyInput.{{ $item->id }}"
                            wire:keyup="updateQuantity({{ $item->id }})"
                            class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    </td>
                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                        <span><strong>Rp.</strong> {{ number_format($subtotals[$item->id] ?? 0, 0, ',', '.') }}</span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        {{ $qty[$item->id] ?? 0 }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col text-right">
                            @if(isset($item->details) && is_array($item->details))
                                @foreach ($item->details as $detail)

                                    <div class="flex flex-col space-y-2">
                                        <div class="flex justify-end items-center">
                                            <p>{{ $detail->qty ?? 0 }} x {{ number_format($detail->unit_price ?? 0, 0, ',', '.') }}</p>
                                            @if($produksiStatus !== 'Selesai')
                                            <button wire:click="decreaseQuantityPerPrice({{ $item->id }}, {{ $detail->unit_price }})"
                                                class="inline-flex items-center justify-center p-1 text-sm font-medium h-6 w-6 text-gray-500 bg-white border border-gray-300 rounded-full focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                                                type="button">
                                                <span class="sr-only">Decrease Quantity</span>
                                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16"/></svg>
                                            </button>
                                                </button>
                                                <button wire:click="returQuantityPerPrice({{ $detail->bahan->id ?? 0 }}, {{ $detail->unit_price }})"
                                                        class="inline-flex items-center justify-center p-1 text-sm font-medium h-6 w-6 text-gray-500 bg-white border border-gray-300 rounded-full focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                                                        type="button">
                                                    <span class="sr-only">Retur Quantity</span>
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-back">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M9 11l-4 4l4 4m-4 -4h11a4 4 0 0 0 0 -8h-1" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p>No details available</p>
                            @endif
                        </div>
                    </td>



                    <td class="px-6 py-4">
                        <div class="flex justify-center items-center">
                            <a href="#" class="font-medium text-red-600 dark:text-red-500 hover:underline" wire:click.prevent="removeItem({{ $item->id }})">
                                <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd"/>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach

            </tbody>
        </table>
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
