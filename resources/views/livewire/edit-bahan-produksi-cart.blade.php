<div>
    @if($produksiStatus !== 'Selesai')
    <div class="border-b border-gray-900/10">
        <h1><strong>Kebutuhan Bahan</strong></h1>
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg pt-0">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-2/4">Bahan</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Qty</th>
                        <th scope="col" class="px-6 py-3 text-right w-1/4">Sub Total</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cartItems as $item)
                        <input type="hidden" name="cartItems" value="{{ json_encode($this->getCartItemsForStorage()) }}">

                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $item->nama ?? $item->nama_bahan }}</td>
                            <td class="px-6 py-4 text-right flex justify-end">
                                <div class="flex items-center">
                                    <button wire:click="decreaseQuantity({{ $item->id }})"
                                        class="inline-flex items-center justify-center p-1 text-sm font-medium h-6 w-6 text-gray-500 bg-white border border-gray-300 rounded-full focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                                        type="button">
                                        <span class="sr-only">Decrease Quantity</span>
                                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16"/></svg>
                                    </button>

                                    <input value="{{ old('qty.'.$item->id, $qty[$item->id] ?? 0) }}"
                                        type="number"
                                        wire:model="qty.{{ $item->id }}"
                                        wire:keyup="updateQuantity({{ $item->id }})"
                                        class="bg-gray-50 w-14 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        placeholder="0" min="0" required />

                                    <button wire:click="increaseQuantity({{ $item->id }})"
                                        class="inline-flex items-center justify-center h-6 w-6 p-1 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-full focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                                        type="button">
                                        <span class="sr-only">Increase Quantity</span>
                                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/></svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"><span><strong></strong> {{ number_format($subtotals[$item->id] ?? 0, 0, ',', '.') }}</span></td>
                            <td class="px-6 py-4 text-right flex justify-end">
                                <a href="#" class="font-medium text-red-600 dark:text-red-500 hover:underline" wire:click.prevent="removeItem({{ $item->id }})">
                                    <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white"></td>
                        <td class="px-6 py-4 text-right text-black">
                            <strong>Total Harga</strong>
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <span><strong>Rp.</strong> {{ number_format($totalharga, 0, ',', '.') }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="border-b border-gray-900/10">
        <h1 class="mt-6"><strong>Bahan Saat Ini</strong></h1>
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg pt-0">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-2/3">Bahan</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Details</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Sub Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($produksiDetails as $detail)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $detail['bahan']->nama_bahan }}</td>
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
                        <td class="px-6 py-4 text-right text-black">
                            <strong>Total Harga</strong>
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <span><strong>Rp.</strong> {{ number_format($produksiTotal, 0, ',', '.') }}</span>
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



</div>
