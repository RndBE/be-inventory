<div>
    <div class="relative sm:rounded-lg pt-2">

    </div>
    <div class="border-gray-900/10 pt-2">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-1/5">Nama</th>
                        <th scope="col" class="px-6 py-3 w-0.5">Spesifikasi</th>
                        <th scope="col" class="px-6 py-3 text-center w-0.5">QTY</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Harga Satuan</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Total Harga</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Ket Pembayaran</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grandTotal = 0;
                    @endphp
                    @foreach ($pembelianBahanDetails as $detail)
                        @php
                            // Tetapkan default nilai harga satuan dan jumlah bahan
                            $unitPrice = $unit_price[$detail['bahan']->id] ?? 0;
                            $jmlBahan = $detail['jml_bahan'] ?? 0;
                            // Hitung subtotal
                            $subTotal = $jmlBahan * $unitPrice;
                            // Tambahkan ke grand total
                            $grandTotal += $subTotal;
                        @endphp

                        <input type="hidden" name="pembelianBahanDetails" value="{{ json_encode($this->getCartItemsForStorage()) }}">
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                    {{ $detail['bahan']->nama_bahan }}
                            </td>
                            <td class="px-6 py-4 text-gray-900 dark:text-white">
                                <span>{!! nl2br(e($detail['spesifikasi'] ?? '')) !!}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                <span>{{ $detail['jml_bahan'] ?? 0 }}</span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @if($editingItemId === $detail['bahan']->id)
                                    <input
                                        autofocus
                                        wire:model="unit_price_raw.{{ $detail['bahan']->id }}"
                                        type="number"
                                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-right"
                                        placeholder="0"
                                        wire:blur="formatToRupiah({{ $detail['bahan']->id }})"
                                        @if($status_finance === 'Disetujui') disabled @endif
                                    />
                                @else
                                    @if($status_finance !== 'Disetujui')
                                        <span class="cursor-pointer" wire:click="editItem({{ $detail['bahan']->id }})">
                                            {{ number_format(
                                                $unit_price[$detail['bahan']->id] ??
                                                ($detail['details']['unit_price'] ?? 0), 0, ',', '.'
                                            ) }}
                                        </span>
                                    @else
                                        <span>
                                            {{ number_format(
                                                $unit_price[$detail['bahan']->id] ??
                                                ($detail['details']['unit_price'] ?? 0), 0, ',', '.'
                                            ) }}
                                        </span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                <span>{{ number_format($subTotal, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                <div class="flex justify-right items-right">
                                    <textarea
                                        wire:model="keterangan_pembayaran.{{ $detail['bahan']->id }}"
                                        wire:keyup="changeKeterangan({{ $detail['bahan']->id }})"
                                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        @if($status_finance === 'Disetujui') disabled @endif
                                    >{{ $detail['keterangan_pembayaran'] ?? '' }}</textarea>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                        <td class="px-6 py-4 text-right text-black"><strong>Total Anggaran</strong></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <span><strong>Rp.</strong> {{ number_format($grandTotal, 0, ',', '.') }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
