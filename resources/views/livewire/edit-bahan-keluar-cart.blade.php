<div>
    <div class="relative sm:rounded-lg pt-2">

    </div>
    <div class="border-gray-900/10 pt-2">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-1/5">Bahan</th>
                        <th scope="col" class="px-6 py-3 w-0.5">QTY</th>
                        @if ($status !== 'Disetujui')
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Sub Total</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grandTotal1 = 0;
                        $grandTotal2 = 0;
                    @endphp
                    @foreach ($bahanKeluarDetails as $detail)
                    {{-- @php
                        dd($bahanKeluarDetails );
                    @endphp --}}
                    <input type="hidden" name="bahanKeluarDetails" value="{{ json_encode($this->getCartItemsForStorage()) }}">
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                            {{ $detail['bahan']->nama_bahan ?? $detail['bahan']->nama_bahan ?? $detail['bahan']->nama_produk ?? null }}
                            @if (!empty($detail['serial_number']))
                                ({{ $detail['serial_number'] }})
                            @endif
                        </td>

                        <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                            <div class="flex items-center">
                                <input
                                    type="number"
                                    wire:model="qty.{{ $detail['bahan_id'] ?? $detail['produk_id'] ?? $detail['produk_jadis_id'] }}"
                                    class="bg-gray-50 w-20 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 border-transparent"
                                    placeholder="0" min="0"
                                    value="{{ old('qty.'.($detail['bahan_id'] ?? $detail['produk_id'] ?? $detail['produk_jadis_id']), $qty[$detail['bahan_id'] ?? $detail['produk_id'] ?? $detail['produk_jadis_id']] ?? 0) }}"
                                    readonly
                                />
                            </div>
                        </td>

                        @if ($status !== 'Disetujui')
                            <td class="px-6 py-4 font-semibold text-right text-gray-900 dark:text-white">
                                <span><strong>Rp.</strong> {{ number_format($subtotals[$detail['bahan']->id ?? $detail['bahan']->produk_id] ?? 0, 0, ',', '.') }}</span>
                            </td>
                            @php $grandTotal1 += $subtotals[$detail['bahan']->id ?? $detail['bahan']->produk_id] ?? 0; @endphp
                        @endif

                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
