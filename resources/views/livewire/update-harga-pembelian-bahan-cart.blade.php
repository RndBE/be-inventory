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
                            <th scope="col" class="px-6 py-3 text-right w-0.5">
                                <div class="flex justify-end items-start">
                                    <span class="text-right">Harga Satuan</span>
                                    <span class="text-[8px] bg-blue-100 me-2 px-2.5 py-0.5 rounded-full text-blue-800 dark:bg-gray-700 dark:text-blue-400 border border-blue-400">
                                        New
                                    </span>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Total Harga</th>
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

                                // Tentukan unit price yang akan digunakan (nilai baru jika ada, jika tidak gunakan nilai lama)
                                $finalUnitPrice = $newUnitPrice > 0 ? $newUnitPrice : $unitPrice;

                                $subTotal = $jmlBahan * $unitPrice;
                                $newSubTotal = $jmlBahan * $newUnitPrice;
                                $newSubTotalFinal = $jmlBahan * $finalUnitPrice;

                                // Tambahkan ke grand total
                                $grandTotal += $newSubTotalFinal;
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
                                @php
                                // Pastikan bahwa variabel-variabel memiliki nilai default jika tidak ada
                                $ongkir = $ongkir ?? 0;
                                $asuransi = $asuransi ?? 0;
                                $layanan = $layanan ?? 0;
                                $jasa_aplikasi = $jasa_aplikasi ?? 0;

                                // Hitung total dengan biaya tambahan
                                $totalWithExtras = $grandTotal + $ongkir + $asuransi + $layanan + $jasa_aplikasi;
                            @endphp
                            <strong>Rp.</strong> {{ number_format($totalWithExtras, 0, ',', '.') }}
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
                            <th scope="col" class="px-6 py-3 text-right w-0.5">
                                <div class="flex justify-end items-start">
                                    <span class="text-right">Harga Satuan (USD)</span>
                                    <span class="text-[8px] bg-blue-100 me-2 px-2.5 py-0.5 rounded-full text-blue-800 dark:bg-gray-700 dark:text-blue-400 border border-blue-400">
                                        New
                                    </span>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Total Harga (USD)</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Harga Satuan (Rp)</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">
                                <div class="flex justify-end items-start">
                                    <span class="text-right">Harga Satuan (Rp)</span>
                                    <span class="text-[8px] bg-blue-100 me-2 px-2.5 py-0.5 rounded-full text-blue-800 dark:bg-gray-700 dark:text-blue-400 border border-blue-400">
                                        New
                                    </span>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Total Harga (Rp)</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Ket Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $grandTotal = 0;
                            $grandTotalUSD = 0;
                            $newGrandTotal = 0;
                            $newGrandTotalUSD = 0;
                            $new_value = 0;
                            $biayaTambahan = ($new_shipping_cost > 0 ? $new_shipping_cost : $shipping_cost) +
                                            ($new_full_amount_fee > 0 ? $new_full_amount_fee : $full_amount_fee) +
                                            ($new_value_today_fee > 0 ? $new_value_today_fee : $value_today_fee);

                            $biayaTambahanUSD = ($new_shipping_cost_usd > 0 ? $new_shipping_cost_usd : $shipping_cost_usd) +
                                            ($new_full_amount_fee_usd > 0 ? $new_full_amount_fee_usd : $full_amount_fee_usd) +
                                            ($new_value_today_fee_usd > 0 ? $new_value_today_fee_usd : $value_today_fee_usd);
                        @endphp
                        @foreach ($pembelianBahanDetails as $detail)
                            @php
                                // Tetapkan default nilai harga satuan dan jumlah bahan
                                $unitPrice = $unit_price[$detail['bahan']->id] ?? 0;
                                $newUnitPrice = $new_unit_price[$detail['bahan']->id] ?? 0;
                                $unitPriceUSD = $unit_price_usd[$detail['bahan']->id] ?? 0;
                                $newUnitPriceUSD = $new_unit_price_usd[$detail['bahan']->id] ?? 0;
                                $jmlBahan = $detail['jml_bahan'] ?? 0;

                                // Tentukan unit price yang akan digunakan (nilai baru jika ada, jika tidak gunakan nilai lama)
                                $finalUnitPrice = $newUnitPrice > 0 ? $newUnitPrice : $unitPrice;
                                $finalUnitPriceUSD = $newUnitPriceUSD > 0 ? $newUnitPriceUSD : $unitPriceUSD;

                                $subTotal = $jmlBahan * $unitPrice;
                                $newSubTotal = $jmlBahan * $newUnitPrice;
                                $newSubTotalFinal = $jmlBahan * $finalUnitPrice;

                                $subTotalUSD = $jmlBahan * $unitPriceUSD;
                                $newSubTotalUSD = $jmlBahan * $newUnitPriceUSD;
                                $newSubTotalFinalUSD = $jmlBahan * $finalUnitPriceUSD;

                                // Tambahkan ke grand total
                                $grandTotal += $newSubTotalFinal;
                                $grandTotalUSD += $newSubTotalFinalUSD;
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
                                    <span>
                                        {{ number_format(
                                            $unit_price_usd[$detail['bahan']->id] ??
                                            ($detail['details_usd']['unit_price_usd'] ?? 0), 2, ',', '.'
                                        ) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                    @if($editingItemId === 'usd_' . $detail['bahan']->id)
                                        <input
                                            autofocus
                                            wire:model="new_unit_price_usd_raw.{{ $detail['bahan']->id }}"
                                            type="number"
                                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-right"
                                            placeholder="0"
                                            wire:blur="formatToUSDPrice({{ $detail['bahan']->id }})"
                                        />
                                    @else
                                        <span class="cursor-pointer" wire:click="editItemPriceUSD('usd',{{ $detail['bahan']->id }})">
                                            {{ number_format(
                                                $new_unit_price_usd[$detail['bahan']->id] ??
                                                ($detail['new_details_usd']['new_unit_price_usd'] ?? 0), 2, ',', '.'
                                            ) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                    {{-- <span>{{ number_format($subTotal, 0, ',', '.') }}</span> --}}
                                    @if($newSubTotalUSD == 0 || is_null($newSubTotalUSD))
                                        <span>{{ number_format($subTotalUSD, 2, ',', '.') }}</span>
                                    @else
                                        <span class="line-through text-red-500">{{ number_format($subTotalUSD, 2, ',', '.') }}</span>
                                        <span class="font-semibold ml-2">{{ number_format($newSubTotalUSD, 2, ',', '.') }}</span>
                                    @endif
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
                                    @if($editingItemId === 'idr_' . $detail['bahan']->id)
                                        <input
                                            autofocus
                                            wire:model="new_unit_price_raw.{{ $detail['bahan']->id }}"
                                            type="number"
                                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-right"
                                            placeholder="0"
                                            wire:blur="formatToRupiahPriceNew({{ $detail['bahan']->id }})"
                                        />
                                    @else
                                        <span class="cursor-pointer" wire:click="editItemPriceUSD('idr',{{ $detail['bahan']->id }})">
                                            {{ number_format(
                                                $new_unit_price[$detail['bahan']->id] ??
                                                ($detail['details']['new_unit_price'] ?? 0), 0, ',', '.'
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
                            $grandTotal += $biayaTambahan;
                            $grandTotalUSD += $biayaTambahanUSD;
                        @endphp

                        <!-- Baris Biaya Tambahan -->
                        @foreach ([
                            'Shipping Cost' => ['new_shipping_cost_usd', 'new_shipping_cost'],
                            'Full Amount Fee' => ['new_full_amount_fee_usd', 'new_full_amount_fee'],
                            'Value Today Fee' => ['new_value_today_fee_usd', 'new_value_today_fee']
                        ] as $label => $fields)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <!-- Kolom Label -->
                                <td class="px-6 py-4 text-black"><strong>{{ $label }}</strong></td>
                                <td class="px-6 py-4 text-right text-black"></td>
                                <td class="px-6 py-4 text-right text-black"></td>

                                <!-- Iterasi Fields -->
                                @foreach ($fields as $index => $field)
                                    @if ($index == 1)
                                        <td class="px-6 py-4 text-right text-black"></td>
                                    @endif

                                    <!-- Kolom Input untuk Nilai Baru -->
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                        @if($editingItemId === $field)
                                            <!-- Input untuk nilai baru -->
                                            <input
                                                autofocus
                                                wire:model="{{ $field }}_raw"
                                                type="number"
                                                class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-right"
                                                placeholder="0"
                                                wire:blur="formatToRupiahNew('{{ $field }}')"
                                            />
                                        @else
                                            <!-- Tampilkan nilai baru (new) di sini -->
                                            <span class="cursor-pointer" wire:click="editItemNew('{{ $field }}')">
                                                @php
                                                    $new_value = $this->$field;
                                                @endphp

                                                @if ($new_value === 0)
                                                    <!-- Tampilkan nilai baru 0 -->
                                                    <span class="font-semibold">0</span>
                                                @else
                                                    @if(strpos($field, 'usd') !== false)
                                                        <strong></strong> {{ number_format($new_value, 2, ',', '.') }}
                                                    @else
                                                        <strong></strong> {{ number_format($new_value, 0, ',', '.') }}
                                                    @endif
                                                @endif
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Kolom Nilai Lama di Sampingnya -->
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                        @php
                                            $old_value = $this->{str_replace('new_', '', $field)};
                                            $new_value = $this->$field;  // Nilai baru yang di-input
                                        @endphp

                                        @if ($new_value && $new_value !== $old_value)
                                            <!-- Jika nilai baru berbeda dengan nilai lama, coret nilai lama -->
                                            <span class="line-through text-red-500">
                                                @if(strpos($field, 'usd') !== false)
                                                    <strong></strong> {{ number_format($old_value, 2, ',', '.') }}
                                                @else
                                                    <strong></strong> {{ number_format($old_value, 0, ',', '.') }}
                                                @endif
                                            </span>

                                            <!-- Tampilkan nilai baru -->
                                            <span class="font-semibold">
                                                @if(strpos($field, 'usd') !== false)
                                                    <strong></strong> {{ number_format($new_value, 2, ',', '.') }}
                                                @else
                                                    <strong></strong> {{ number_format($new_value, 0, ',', '.') }}
                                                @endif
                                            </span>
                                        @else
                                            <!-- Jika nilai baru sama dengan nilai lama, hanya tampilkan nilai lama tanpa coretan -->
                                            @if(strpos($field, 'usd') !== false)
                                                <strong></strong> {{ number_format($old_value, 2, ',', '.') }}
                                            @else
                                                <strong></strong> {{ number_format($old_value, 0, ',', '.') }}
                                            @endif
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach


                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td colspan="3"></td>
                            <td class="px-6 py-4 text-right text-black"><strong>Total Anggaran</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                <span><strong>$</strong> {{ number_format($grandTotalUSD, 2, ',', '.') }}</span>
                            </td>
                            <td class="px-6 py-4 text-right text-black"></td>
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
