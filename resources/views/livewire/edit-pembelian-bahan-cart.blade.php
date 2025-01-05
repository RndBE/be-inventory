<div>
    <div class="border-gray-900/10 pt-2">
        @if($jenis_pengajuan === 'Pembelian Bahan/Barang/Alat Lokal')
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
                        // $biayaTambahan = ($ongkir ?? 0) + ($asuransi ?? 0) + ($layanan ?? 0) + ($jasa_aplikasi ?? 0);
                    @endphp

                    @foreach ($pembelianBahanDetails as $detail)
                        @php
                            $unitPrice = $unit_price[$detail['bahan']->id] ?? 0;
                            $jmlBahan = $detail['jml_bahan'] ?? 0;

                            $subTotal = $jmlBahan * $unitPrice;

                            $grandTotal += $subTotal;
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
                                @if($editingItemId === $detail['bahan']->id)
                                    <input
                                        autofocus
                                        wire:model="unit_price_raw.{{ $detail['bahan']->id }}"
                                        type="number"
                                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-right"
                                        placeholder="0"
                                        wire:blur="formatToRupiahPrice({{ $detail['bahan']->id }})"
                                        @if($status_finance === 'Disetujui') disabled @endif
                                    />
                                @else
                                    @if($status_finance !== 'Disetujui')
                                        <span class="cursor-pointer" wire:click="editItemPrice({{ $detail['bahan']->id }})">
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
                    {{-- @php
                        // Tambahkan biaya tambahan ke grand total
                        $grandTotal += $biayaTambahan;
                    @endphp --}}

                <!-- Baris Biaya Tambahan -->
                {{-- @foreach (['Ongkos Kirim' => 'ongkir', 'Asuransi' => 'asuransi', 'Layanan' => 'layanan', 'Jasa Aplikasi' => 'jasa_aplikasi'] as $label => $field)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td colspan="3"></td>
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
                @endforeach --}}

                <!-- Total Anggaran -->
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td colspan="3"></td>
                    <td class="px-6 py-4 text-right text-black"><strong>Total Anggaran</strong></td>
                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                        <span><strong>Rp.</strong> {{ number_format($grandTotal, 0, ',', '.') }}</span>
                    </td>
                </tr>
            </tbody>
            </table>
        </div>
        @endif
        @if($jenis_pengajuan === 'Pembelian Bahan/Barang/Alat Impor')
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-1/5">Nama</th>
                        <th scope="col" class="px-6 py-3 text-center w-0.5">QTY</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Harga Satuan (USD)</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Total Harga (USD)</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Harga Satuan (Rp)</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Total Harga (Rp)</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Ket Pembayaran</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grandTotal = 0;
                        $grandTotalUSD = 0;
                        $biayaTambahan = ($shipping_cost ?? 0) + ($full_amount_fee?? 0) + ($value_today_fee ?? 0);
                        $biayaTambahanUSD = ($shipping_cost_usd ?? 0) + ($full_amount_fee_usd?? 0) + ($value_today_fee_usd ?? 0);
                    @endphp

                    @foreach ($pembelianBahanDetails as $detail)
                        @php
                            $unitPrice = $unit_price[$detail['bahan']->id] ?? 0;
                            $unitPriceUSD = $unit_price_usd[$detail['bahan']->id] ?? 0;
                            $jmlBahan = $detail['jml_bahan'] ?? 0;

                            $subTotal = $jmlBahan * $unitPrice;
                            $subTotalUSD = $jmlBahan * $unitPriceUSD;

                            // Add the subTotal to the grand total
                            $grandTotal += $subTotal;
                            $grandTotalUSD += $subTotalUSD;
                        @endphp

                        <input type="hidden" name="pembelianBahanDetails" value="{{ json_encode($this->getCartItemsForStorage()) }}">
                        <input type="hidden" name="biaya" value="{{ json_encode($this->getCartItemsForStorageBiaya()) }}">
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                    {{ $detail['bahan']->nama_bahan }}
                            </td>
                            <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                <span>{{ $detail['jml_bahan'] ?? 0 }}</span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @if($editingItemId === 'usd_' . $detail['bahan']->id)
                                    <input
                                        autofocus
                                        wire:model="unit_price_usd_raw.{{ $detail['bahan']->id }}"
                                        type="number"
                                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-right"
                                        placeholder="0"
                                        wire:blur="formatToUSDPrice({{ $detail['bahan']->id }})"
                                        @if($status_finance === 'Disetujui') disabled @endif
                                    />
                                @else
                                    @if($status_finance !== 'Disetujui')
                                        <span class="cursor-pointer" wire:click="editItemPrice('usd',{{ $detail['bahan']->id }})">
                                            {{ number_format(
                                                $unit_price_usd[$detail['bahan']->id] ??
                                                ($detail['details_usd']['unit_price_usd'] ?? 0), 2, ',', '.'
                                            ) }}
                                        </span>
                                    @else
                                        <span>
                                            {{ number_format(
                                                $unit_price_usd[$detail['bahan']->id] ??
                                                ($detail['details_usd']['unit_price_usd'] ?? 0), 2, ',', '.'
                                            ) }}
                                        </span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                <span>{{ number_format($subTotalUSD, 2, ',', '.') }}</span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @if($editingItemId === 'idr_' . $detail['bahan']->id)
                                    <input
                                        autofocus
                                        wire:model="unit_price_raw.{{ $detail['bahan']->id }}"
                                        type="number"
                                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-right"
                                        placeholder="0"
                                        wire:blur="formatToRupiahPrice({{ $detail['bahan']->id }})"
                                        @if($status_finance === 'Disetujui') disabled @endif
                                    />
                                @else
                                    @if($status_finance !== 'Disetujui')
                                        <span class="cursor-pointer" wire:click="editItemPrice('idr',{{ $detail['bahan']->id }})">
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
                    @php
                        $grandTotal += $biayaTambahan;
                        $grandTotalUSD += $biayaTambahanUSD;
                    @endphp

                    <!-- Baris Biaya Tambahan -->
                    @foreach (['Shipping Cost' => ['shipping_cost_usd','shipping_cost'], 'Full Amount Fee' => ['full_amount_fee_usd','full_amount_fee'], 'Value Today Fee' => ['value_today_fee_usd','value_today_fee']] as $label => $fields)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 text-black"><strong>{{ $label }}</strong></td>
                            <td class="px-6 py-4 text-right text-black"></td>
                            <td class="px-6 py-4 text-right text-black"></td>

                            @foreach ($fields as $index => $field)
                                @if ($index == 1)
                                    <td class="px-6 py-4 text-right text-black"></td>
                                @endif

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
                                            @if(strpos($field, 'usd') !== false)
                                                <strong>$</strong> {{ number_format($this->$field, 2, ',', '.') }}
                                            @else
                                                <strong>Rp</strong> {{ number_format($this->$field, 0, ',', '.') }}
                                            @endif
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach

                    <!-- Total Anggaran -->
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td colspan="2"></td>
                        <td class="px-6 py-4 text-right text-black"><strong>Total Anggaran</strong></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <span><strong>$</strong> {{ number_format($grandTotalUSD, 2, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 text-right text-black"></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <span><strong>Rp</strong> {{ number_format($grandTotal, 0, ',', '.') }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
