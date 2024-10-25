<div>
    <div class="border-b border-gray-900/10">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg pt-2">

            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-1/5">Bahan</th>
                        <th scope="col" class="px-6 py-3 text-center w-0.5">Stok</th>
                        <th scope="col" class="px-6 py-3 text-center w-0.5">Ambil Stok</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Sub Total</th>
                        <th scope="col" class="px-6 py-3 text-right w-1">Details</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Sub Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cart as $item)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                {{ $item->nama_bahan }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-center text-gray-900 dark:text-white">
                                <div class="flex justify-center">
                                    {{ $this->qty[$item->id] ?? 0 }}
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-center text-gray-900 dark:text-white">
                                <div class="flex justify-center">
                                    <input value="{{ old('qty.'.$item->id, $qty[$item->id] ?? 0) }}"
                                        type="number"
                                        wire:model="qty.{{ $item->id }}"
                                        wire:keyup="updateQuantity({{ $item->id }})"
                                        class="bg-gray-50 w-14 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        placeholder="0" min="0" required />
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-right text-gray-900 dark:text-white">
                                <span><strong>Rp.</strong> {{ number_format($subtotals[$item->id] ?? 0, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-right text-gray-900 dark:text-white"></td>
                        </tr>
                    @endforeach

                    @foreach($projekDetails as $detail)
                    <input type="hidden" name="projekDetails" value="{{ json_encode($projekDetails) }}">
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $detail['bahan']->nama_bahan }}</td>
                            <td class="items-center px-6 py-4 text-gray-900 dark:text-white text-center">
                                <div class="flex flex-col space-y-2">
                                    <div class="flex justify-center items-center">
                                        @php
                                            $stokSaatIni = 0;
                                            if ($detail['bahan']->jenisBahan->nama === 'Produksi') {
                                                $stokSaatIni = $detail['bahan']->bahanSetengahjadiDetails()->where('sisa', '>', 0)->sum('sisa');
                                            } else {
                                                $stokSaatIni = $detail['bahan']->purchaseDetails()->where('sisa', '>', 0)->sum('sisa');
                                            }
                                        @endphp
                                        {{ $stokSaatIni }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-center text-gray-900 dark:text-white">
                                <div class="flex justify-center">
                                    <input value="{{ old('qty.'.$detail['bahan']->id, $qty[$detail['bahan']->id] ?? 0) }}"
                                        type="number"
                                        wire:model="qty.{{ $detail['bahan']->id }}"
                                        wire:keyup="updateQuantity({{ $detail['bahan']->id }})"
                                        class="bg-gray-50 w-14 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        placeholder="0" min="0" required />
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-right text-gray-900 dark:text-white">
                                <span>{{ number_format($detail['sub_total'], 0, ',', '.') }}</span>
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
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </td>
                            <td class="px-6 py-4 font-semibold text-right text-gray-900 dark:text-white">
                                <span>{{ number_format($detail['sub_total'], 0, ',', '.') }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
{{--
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-1/5">Bahan</th>
                        <th scope="col" class="px-6 py-3 text-center w-0.5">Stok</th>
                        <th scope="col" class="px-6 py-3 text-center w-0.5">Ambil Stok</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Sub Total</th>
                        <th scope="col" class="px-6 py-3 text-right w-1">Details</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Sub Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grandTotal = 0;

                        // Initialize qty array for each bahan in projekDetails
                        foreach ($this->projekDetails as $detail) {
                            $bahanId = $detail['bahan']->id;
                            // Ensure that qty is initialized
                            if (!isset($this->qty[$bahanId])) {
                                $this->qty[$bahanId] = 0; // Set to default value of 0
                            }
                        }
                    @endphp
                    @foreach($projekDetails as $detail)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $detail['bahan']->nama_bahan }}</td>
                            <td class="items-center px-6 py-4 text-gray-900 dark:text-white text-center">
                                <div class="flex flex-col space-y-2">
                                    <div class="flex justify-center items-center">
                                        @php
                                            $stokSaatIni = 0;
                                            if ($detail['bahan']->jenisBahan->nama === 'Produksi') {
                                                $stokSaatIni = $detail['bahan']->bahanSetengahjadiDetails()->where('sisa', '>', 0)->sum('sisa');
                                            } else {
                                                $stokSaatIni = $detail['bahan']->purchaseDetails()->where('sisa', '>', 0)->sum('sisa');
                                            }
                                        @endphp
                                        {{ $stokSaatIni }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-center text-gray-900 dark:text-white">
                                <div class="flex justify-center">
                                    <input value="{{ old('qty.'.$detail['bahan']->id, isset($qty[$detail['bahan']->id]) ? $qty[$detail['bahan']->id] : 0) }}"
                                        type="number"
                                        wire:model="qty.{{ $detail['bahan']->id }}"
                                        wire:keyup="updateQuantity({{ $detail['bahan']->id }})"
                                        class="bg-gray-50 w-14 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        placeholder="0" min="0" required />
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-right text-gray-900 dark:text-white">
                                <span>{{ isset($subtotals[$detail['bahan']->id]) ? number_format($subtotals[$detail['bahan']->id], 0, ',', '.') : '0' }}</span>
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
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </td>
                            <td class="px-6 py-4 font-semibold text-right text-gray-900 dark:text-white">
                                <span>{{ number_format($detail['sub_total'], 0, ',', '.') }}</span>
                            </td>
                        </tr>
                    @endforeach
                    <!-- Rest of your table for totals -->
                </tbody>

            </table> --}}
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
