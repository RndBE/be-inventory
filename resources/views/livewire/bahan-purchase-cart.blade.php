
<div>
    <div class="border-gray-900/10">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Bahan</th>
                        <th scope="col" class="px-6 py-3">Unit Price</th>
                        {{-- <th scope="col" class="px-6 py-3">Stok</th> --}}
                        <th scope="col" class="px-6 py-3 text-center">Qty</th>
                        <th scope="col" class="px-6 py-3">Sub Total</th>
                        <th scope="col" class="px-6 py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cartItems as $item)
                        <input type="hidden" name="cartItems" value="{{ json_encode($this->getCartItemsForStorage()) }}">
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $item->nama }}</td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                @if($editingItemId === $item->bahan_id)
                                    <input
                                        autofocus
                                        {{-- value="{{ old('unit_price_raw.' . $item->bahan_id, isset($unit_price[$item->bahan_id]) ? $unit_price[$item->bahan_id] : '') }}" --}}
                                        value="{{ old('unit_price_raw.' . $item->bahan_id, isset($unit_price[$item->bahan_id]) ? number_format($unit_price[$item->bahan_id], 2, '.', '') : '') }}"

                                        wire:model="unit_price_raw.{{ $item->bahan_id }}"
                                        type="text"
                                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        placeholder="0"
                                        required
                                        wire:blur="formatToRupiah({{ $item->bahan_id }})"
                                    />
                                @else
                                    {{-- <span class="cursor-pointer" wire:click="editItem({{ $item->bahan_id }})">Rp. {{ number_format($unit_price[$item->bahan_id] ?? 0, 0, ',', '.') }}</span> --}}
                                    <span class="cursor-pointer" wire:click="editItem({{ $item->bahan_id }})">
                                        Rp. {{ number_format($unit_price[$item->bahan_id] ?? 0, 2, ',', '.') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center items-center">

                                    <input value="{{ old('qty.'.$item->bahan_id, $qty[$item->bahan_id] ?? 0) }}"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        wire:model.lazy="qty.{{ $item->bahan_id }}"
                                        wire:change="updateQuantity({{ $item->bahan_id }})"
                                        class="bg-gray-50 w-32 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        placeholder="0.00"
                                        required />
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                {{-- <span><strong>Rp.</strong> {{ number_format($subtotals[$item->bahan_id] ?? 0, 0, ',', '.') }}</span> --}}
                                <span><strong>Rp.</strong> {{ number_format($subtotals[$item->bahan_id] ?? 0, 2, ',', '.') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="#" class="font-medium text-red-600 dark:text-red-500 hover:underline" wire:click.prevent="removeItem({{ $item->bahan_id }})">
                                    <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
