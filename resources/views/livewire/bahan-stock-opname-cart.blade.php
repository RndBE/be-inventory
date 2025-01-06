
<div>
    <div class="border-gray-900/10">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Kode</th>
                        <th scope="col" class="px-6 py-3">Nama Barang</th>
                        <th scope="col" class="px-6 py-3 text-center">Satuan</th>
                        <th scope="col" class="px-6 py-3">Tersedia(Sistem)</th>
                        <th scope="col" class="px-6 py-3">Tersedia(Fisik)</th>
                        <th scope="col" class="px-6 py-3">Selisih</th>
                        <th scope="col" class="px-6 py-3">Keterangan</th>
                        <th scope="col" class="px-6 py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cartItems as $item)
                        <input type="hidden" name="cartItems" value="{{ json_encode($this->getCartItemsForStorage()) }}">
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $item->kode_bahan }}</td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $item->nama_bahan }}</td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $item->dataUnit->nama }}</td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                <input
                                    value="{{ old('tersedia_sistem.'.$item->id, $tersedia_sistem[$item->id] ?? 0) }}"
                                    type="number"
                                    wire:model="tersedia_sistem.{{ $item->id }}"
                                    class="text-right bg-gray-50 w-20 text-gray-900 text-sm rounded-lg focus:ring-transparent focus:border-none block px-2.5 py-1 dark:bg-gray-700 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 border-transparent"
                                    placeholder="0"
                                    readonly
                                    required
                                />
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center items-center">
                                    @if($editingItemId === $item->id)
                                        <input
                                            autofocus
                                            value="{{ old('tersedia_fisik_raw.' . $item->id, isset($tersedia_fisik[$item->id]) ? $tersedia_fisik[$item->id] : '') }}"
                                            wire:model="tersedia_fisik_raw.{{ $item->id }}"
                                            type="text"
                                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-900 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="0"
                                            required wire:blur="format({{ $item->id }})"
                                        />
                                    @else
                                        <span class="cursor-pointer text-gray-900 " wire:click="editItem({{ $item->id }})">{{ $tersedia_fisik[$item->id] ?? 0 }}</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                <span><strong></strong> {{ $this->getSelisih($item->id) }}</span>
                            </td>

                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                <div class="flex justify-right items-right">
                                    <textarea
                                        wire:model="keterangan.{{ $item->id }}"
                                        wire:keyup="updateQuantity({{ $item->id }})"
                                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        >{{ old('keterangan.'.$item->id, $keterangan[$item->id] ?? '') }}</textarea>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <a href="#" class="font-medium text-red-600 dark:text-red-500 hover:underline" wire:click.prevent="removeItem({{ $item->id }})">
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
