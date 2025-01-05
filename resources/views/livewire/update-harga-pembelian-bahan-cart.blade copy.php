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
                        <th scope="col" class="px-6 py-3 text-right w-0.5">
                            <div class="flex justify-end items-start">
                                <span class="text-right">Harga Satuan</span>
                                <span class="text-[8px] bg-blue-100 me-2 px-2.5 py-0.5 rounded-full text-blue-800 dark:bg-gray-700 dark:text-blue-400 border border-blue-400">
                                    New
                                </span>
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Total Harga</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">
                            <div class="flex justify-end items-start">
                                <span class="text-right">Total Harga</span>
                                <span class="text-[8px] bg-blue-100 me-2 px-2.5 py-0.5 rounded-full text-blue-800 dark:bg-gray-700 dark:text-blue-400 border border-blue-400">
                                    New
                                </span>
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Ket Pembayaran</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grandTotal = 0;
                        $newGrandTotal = 0;
                        $biayaTambahan = ($ongkir ?? 0) + ($asuransi ?? 0) + ($layanan ?? 0) + ($jasa_aplikasi ?? 0);
                    @endphp
                    @foreach ($pembelianBahanDetails as $detail)
                        @php
                            // Tetapkan default nilai harga satuan dan jumlah bahan
                            $unitPrice = $unit_price[$detail['bahan']->id] ?? 0;
                            $newUnitPrice = $new_unit_price[$detail['bahan']->id] ?? 0;
                            $jmlBahan = $detail['jml_bahan'] ?? 0;

                            // Hitung subtotal
                            $newSubTotal = $jmlBahan * $newUnitPrice;
                            $subTotal = $jmlBahan * $unitPrice;
                            // Tambahkan ke grand total
                            $grandTotal += $subTotal;
                            $newGrandTotal += $newSubTotal;
                        @endphp

                        <input type="hidden" name="pembelianBahanDetails" value="{{ json_encode($this->getCartItemsForStorage()) }}">
                        <input type="hidden" name="biaya" value="{{ json_encode($this->getCartItemsForStorageBiaya()) }}">
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
                                <span>
                                    {{ number_format(
                                        $unit_price[$detail['bahan']->id] ??
                                        ($detail['details']['unit_price'] ?? 0), 0, ',', '.'
                                    ) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @if($editingItemId === $detail['bahan']->id)
                                    <input
                                        autofocus
                                        wire:model="new_unit_price_raw.{{ $detail['bahan']->id }}"
                                        type="number"
                                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-right"
                                        placeholder="0"
                                        wire:blur="formatToRupiahPrice({{ $detail['bahan']->id }})"
                                    />
                                @else
                                    <span class="cursor-pointer" wire:click="editItemPrice({{ $detail['bahan']->id }})">
                                        {{ number_format(
                                            $new_unit_price[$detail['bahan']->id] ??
                                            ($new_detail['new_details']['new_unit_price'] ?? 0), 0, ',', '.'
                                        ) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                {{-- <span>{{ number_format($subTotal, 0, ',', '.') }}</span> --}}
                                @if($newSubTotal == 0 || is_null($newSubTotal))
                                    <span>{{ number_format($subTotal, 0, ',', '.') }}</span>
                                @else
                                    <span class="line-through text-red-500">{{ number_format($subTotal, 0, ',', '.') }}</span>
                                    <span class="font-semibold ml-2">{{ number_format($newSubTotal, 0, ',', '.') }}</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                {{-- <span>{{ number_format($newSubTotal, 0, ',', '.') }}</span> --}}
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                <div class="flex justify-right items-right">
                                    <textarea
                                        wire:model="keterangan_pembayaran.{{ $detail['bahan']->id }}"
                                        wire:keyup="changeKeterangan({{ $detail['bahan']->id }})"
                                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    >{{ $detail['keterangan_pembayaran'] ?? '' }}</textarea>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @php
                        // Tambahkan biaya tambahan ke grand total
                        $grandTotal += $biayaTambahan;
                    @endphp
                    @foreach (['Ongkos Kirim' => 'ongkir', 'Asuransi' => 'asuransi', 'Layanan' => 'layanan', 'Jasa Aplikasi' => 'jasa_aplikasi'] as $label => $field)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td colspan="3"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <td class="px-6 py-4 text-right text-black"><strong>{{ $label }}</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @if($editingItemId === $field)
                                    <input
                                        autofocus
                                        wire:model="{{ $field }}_raw"
                                        type="number"
                                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-right"
                                        placeholder="0"
                                        wire:blur="formatToRupiah('{{ $field }}')"
                                    />
                                @else
                                    <span class="cursor-pointer" wire:click="editItem('{{ $field }}')">
                                        {{ number_format($this->$field, 0, ',', '.') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                        <td class="px-6 py-4 text-right text-black"><strong>Total Anggaran</strong></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            @if($newGrandTotal === 0 || $newGrandTotal === null)
                                @php
                                    $totalWithExtras = $grandTotal + $ongkir + $asuransi + $layanan + $jasa_aplikasi;
                                @endphp
                                <span><strong>Rp.</strong> {{ number_format($totalWithExtras, 0, ',', '.') }}</span>
                            @else
                                @php
                                    $oldTotalWithExtras = $grandTotal + $ongkir + $asuransi + $layanan + $jasa_aplikasi;
                                    $totalWithExtras = $newGrandTotal + $ongkir + $asuransi + $layanan + $jasa_aplikasi;
                                @endphp
                                <span style="text-decoration: line-through; color: red;">
                                    <strong>Rp.</strong> {{ number_format($oldTotalWithExtras, 0, ',', '.') }}
                                </span>
                                <br>
                                <span><strong>Rp.</strong> {{ number_format($totalWithExtras, 0, ',', '.') }}</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
